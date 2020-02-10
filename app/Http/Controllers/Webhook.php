<?php

namespace App\Http\Controllers;

use App\Gateway\EventLogGateway;
use App\Gateway\MemoryGateway;
use App\Gateway\TableGateway;
use App\Gateway\UserGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Logger;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class Webhook extends Controller
{
    /**
     * @var LINEBot
     */
    private $bot;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var EventLogGateway
     */
    private $logGateway;
    /**
     * @var UserGateway
     */
    private $userGateway;
    /**
     * @var TableGateway
     */
    private $tableGateway;
    /**
     * @var MemoryGateway
     */
    private $memoryGateway;
    /**
     * @var array
     */
    private $user;

    public function __construct(
        Request $request,
        Response $response,
        Logger $logger,
        EventLogGateway $logGateway,
        UserGateway $userGateway,
        TableGateway $tableGateway,
        MemoryGateway $memoryGateway
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->logGateway = $logGateway;
        $this->userGateway = $userGateway;
        $this->tableGateway = $tableGateway;
        $this->memoryGateway = $memoryGateway;

        // creating bot object
        $httpClient = new CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
    }

    public function __invoke()
    {
        // getting request
        $body = $this->request->all();

        // debugging data
        $this->logger->debug('Body', $body);

        // saving log
        $signature = $this->request->server('HTTP_X_LINE_SIGNATURE') ?: '-';
        $this->logGateway->saveLog($signature, json_encode($body, true));

        return $this->handleEvents();
    }

    private function handleEvents()
    {
        $data = $this->request->all();

        if (is_array($data['events'])) {
            foreach ($data['events'] as $event) {
                // skipping group and room event
                if (!isset($event['source']['userId'])) continue;

                // getting user data from database
                $this->user = $this->userGateway->getUser($event['source']['userId']);

                // if user not registered
                if (!$this->user) $this->followCallback($event);
                else {
                    // responding event
                    if ($event['type'] == 'message') {
                        if (method_exists($this, $event['message']['type'] . 'Message')) {
                            $this->{$event['message']['type'] . 'Message'}($event);
                        }
                    } else {
                        if (method_exists($this, $event['type'] . 'Callback')) {
                            $this->{$event['type'] . 'Callback'}($event);
                        }
                    }
                }
            }
        }

        $this->response->setContent("No event found!");
        $this->response->setStatusCode(200);
        return $this->response;
    }

    private function followCallback($event)
    {
        $res = $this->bot->getProfile($event['source']['userId']);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();

            // creating welcome message
            $message = "Halo, " . $profile['displayName'] . "!";
            $textMessaegeBuilder = new TextMessageBuilder($message);

            // merging all messages
            $multiMessageBuilder = new MultiMessageBuilder();
            $multiMessageBuilder->add($textMessaegeBuilder);

            // sending reply message
            $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);

            // saving user data
            $this->userGateway->saveUser(
                $profile['userId'],
                $profile['displayName']
            );

            // BETA TEST
            $this->tableGateway->up($profile['userId']);
        }
    }

    private function textMessage($event)
    {
        $message = $event['message']['text'];

        // BETA TEST
        $res = $this->bot->getProfile($event['source']['userId']);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();
            if (strtolower($message) == '##delete') {
                $this->tableGateway->down($profile['userId']);
                $message = "You have deleted all the memories";
            } else if (strtolower($message) == "remember") {
                $this->tableGateway->rememberThis($profile['userId'], "JUST REMEMBER");
                $message = "Ok, I remember that.";
            } else if (strtolower($message) == "forget") {
                $this->remembering($profile['userId'], $event['replyToken']);
                $message = "Sorry, there's nothing to be remembered.";
            }

            $textMessaegeBuilder = new TextMessageBuilder($message);
            $this->bot->replyMessage($event['replyToken'], $textMessaegeBuilder);
        }
    }

    private function remembering($tableName, $replyToken)
    {
        $total = $this->tableGateway->count($tableName);
        $mem = array("test1");
        for ($i=1; $i <= $total; $i++) { 
            $memory = $this->memoryGateway->getMemory($tableName, $i);
            // if ($i < $total) {
            //     array_push($mem, $memory['remember'] . '\n');
            // } else {
                array_push($mem, $memory['remember']);
            // }
            
        }
        $messageBuilder = new TextMessageBuilder($mem);

        // send message
        $response = $this->bot->replyMessage($replyToken, $messageBuilder);
    }
}
