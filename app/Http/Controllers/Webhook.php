<?php

namespace App\Http\Controllers;

use App\Gateway\EventLogGateway;
use App\Gateway\MemoryGateway;
use App\Gateway\UserGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Logger;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

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
     * @var MemoryGateway
     */
    private $memoryGateway;
    /**
     * @var array
     */
    private $user;
    private $help = "How To Use\n\n\fUntuk menyimpan catatan: Gunakan \".note [catatan kamu]\"\n\fUntuk menghapus catatan: Gunakan \".forget [nomor catatan di list]\"\n\fUntuk melihat list catatan: Gunakan \".show\"\n\fUntuk melihat bantuan: Gunakan \".help\"";

    public function __construct(
        Request $request,
        Response $response,
        Logger $logger,
        EventLogGateway $logGateway,
        UserGateway $userGateway,
        MemoryGateway $memoryGateway
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->logGateway = $logGateway;
        $this->userGateway = $userGateway;
        $this->memoryGateway = $memoryGateway;

        // create bot object
        $httpClient = new CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
    }

    public function __invoke()
    {
        // get request
        $body = $this->request->all();

        // debug data
        $this->logger->debug('Body', $body);

        // save log
        $signature = $this->request->server('HTTP_X_LINE_SIGNATURE') ?: '-';
        $this->logGateway->saveLog($signature, json_encode($body, true));

        return $this->handleEvents();
    }

    private function handleEvents()
    {
        $data = $this->request->all();

        if (is_array($data['events'])) {
            foreach ($data['events'] as $event) {
                // skip group and room event
                if (!isset($event['source']['userId'])) {
                    if ($event['type'] == "join") {
                        $this->joinCallback($event);
                    }
                    continue;
                }

                // get user data from database
                $this->user = $this->userGateway->getUser($event['source']['userId']);

                // if user not registered
                if (!$this->user) $this->followCallback($event);
                else {
                    // respond event
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

            // create welcome message
            $message = "Halo, " . $profile['displayName'] . "!";

            // send reply message
            $this->bot->replyMessage($event['replyToken'], $this->welcomeMessage($message));

            // save user data
            $this->userGateway->saveUser(
                $profile['userId'],
                $profile['displayName']
            );

            // BETA TEST
            $this->memoryGateway->up($profile['userId']);
        }
    }

    private function joinCallback($event)
    {
        // create welcome message
        $message = "Hai " . "Gaes!";
        // $haloMessage = new TextMessageBuilder($message);
        // $textMessaegeBuilder2 = new TextMessageBuilder($this->introduction);

        // // merge all messages
        // $multiMessageBuilder = new MultiMessageBuilder();
        // $multiMessageBuilder->add($haloMessage);
        // $multiMessageBuilder->add($textMessaegeBuilder2);

        // send reply message
        $this->bot->replyMessage($event['replyToken'], $this->welcomeMessage($message));
    }

    private function welcomeMessage($message)
    {
        $introduction = "Aku adalah bot yang bisa mengingat catatan kamu supaya kamu tidak lupa.";

        // prepare help button
        $helpButton[] = new MessageTemplateActionBuilder("How To Use", ".help");

        // prepare button template
        $buttonTemplate = new ButtonTemplateBuilder(null, $introduction, null, $helpButton);

        // build message
        $haloMessage = new TextMessageBuilder($message);
        $introductionMessage = new TemplateMessageBuilder($message, $buttonTemplate);

        // merge all messages
        $multiMessageBuilder = new MultiMessageBuilder();
        $multiMessageBuilder->add($haloMessage);
        $multiMessageBuilder->add($introductionMessage);
        return $multiMessageBuilder;
    }

    private function textMessage($event)
    {
        $message = "Sorry, there's something wrong";
        $text = $event['message']['text'];
        $trim = trim($text);
        $words = preg_split("/[\s,]+/", $trim);
        $intent = $words[0];

        // create the right words
        if (isset($words[1])) {
            array_splice($words, 0, 1);
            $note = implode(" ", $words);
        }

        // BETA TEST
        $source = $event['source']['type'];
        $res = $this->bot->getProfile($event['source']['userId']);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();
            // if (strtolower($intent) == '#~delete') {
            //     $this->memoryGateway->down($profile['userId']);
            //     $message = "You have deleted all the memories";
            // } else 
            if (strtolower($intent) == ".note") {
                if (isset($note) && $note) {
                    $message = $this->memoryGateway->rememberThis($profile['userId'], $note);
                } else {
                    $message = "What should I remember?\nUse \".note [your note]\"";
                }
            } else if (strtolower($intent) == ".forget") {
                if (isset($note) && $note) {
                    $message = $this->memoryGateway->forgetMemory($profile['userId'], $note);
                } else {
                    $message = "What should I forget?\nUse \".forget [note number]\"";
                }
            } else if (strtolower($intent) == ".show") {
                $message = $this->remembering($profile['userId']);
            } else if (strtolower($intent) == ".help") {
                $message = $this->help;
            } else {
                if ($source == "user") {
                    $message = "Sorry, I don't understand";
                } else {
                    return;
                }
            }
        }

        // send response
        $haloMessage = new TextMessageBuilder($message);
        $this->bot->replyMessage($event['replyToken'], $haloMessage);
    }

    private function remembering($tableName)
    {
        $total = $this->memoryGateway->count($tableName);
        if ($total != 0) {
            $list = array("Here's what you should remember:");
            $no = 1;
            for ($i = 0; $i < $total; $i++) {
                $memory = $this->memoryGateway->getMemory($tableName, $i + 1);

                if (!$memory) {
                    $total++;
                    continue;
                }
                array_push($list, $no . ". " . $memory['remember']);
                $no++;
            }

            $theMessage = implode("\n", $list);
        } else {
            $theMessage = "There's nothing to be remembered";
        }

        return $theMessage;
    }
}
