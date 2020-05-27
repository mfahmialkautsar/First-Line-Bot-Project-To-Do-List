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
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
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

                // handle group and room event
                if (!isset($event['source']['userId'])) {
                    if ($event['type'] == "join") {
                        switch ($event['source']['type']) {
                            case 'room':
                                $this->user = $this->userGateway->getRoom($event['source']['roomId']);
                                break;
                            case 'group':
                                $this->user = $this->userGateway->getGroup($event['source']['groupId']);
                                break;
                            default:
                                continue;
                        }

                        // if (!$this->user) $this->joinCallback($event);
                        // else {
                        // respond event
                        $this->respondEvent($event);
                        // }
                    }
                    continue;
                }

                // get user data from database
                $this->user = $this->userGateway->getUser($event['source']['userId']);

                // if user not registered
                // if (!$this->user) $this->followCallback($event);
                // else {
                // respond event
                $this->respondEvent($event);
                // }
            }
        }

        $this->response->setContent("No event found!");
        $this->response->setStatusCode(200);
        return $this->response;
    }

    private function respondEvent($event)
    {
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

    private function followCallback($event)
    {
        $res = $this->bot->getProfile($event['source']['userId']);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();

            // create welcome message
            $message = "Hello, " . $profile['displayName'] . "!";
            $messageBuilder = $this->welcomeMessage($message);

            // save user data
            $this->userGateway->saveUser(
                $profile['userId'],
                $profile['displayName']
            );
        }

        if (isset($messageBuilder)) {

            // send reply message
            $this->bot->replyMessage($event['replyToken'], $messageBuilder);
        }
    }

    private function joinCallback($event)
    {
        $source = $event['source']['type'];

        // determine the table database
        if ($source == "room") {
            $roomId = $event['source']['roomId'];
        } else if ($source == "group") {
            $groupId = $event['source']['groupId'];
        }

        // create welcome message
        $message = "Hello, " . "Everyone!";

        // send reply message
        $this->bot->replyMessage($event['replyToken'], $this->welcomeMessage($message));

        if ($source == "room") {
            // save room data
            $this->userGateway->saveRoom(
                $roomId
            );
        } else if ($source == "group") {
            // save group data
            $this->userGateway->saveGroup(
                $groupId
            );
        }
    }

    private function welcomeMessage($message)
    {
        $introduction = "I'm a bot that will help you remember your To-Do List so you'll never forget that.";
        $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626494);

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
        $multiMessageBuilder->add($stickerMessageBuilder);
        $multiMessageBuilder->add($introductionMessage);
        return $multiMessageBuilder;
    }

    private function textMessage($event)
    {
        // $help2 = "Tips: kamu bisa hapus beberapa note sekaligus " . $this->emojiBuilder('10007F') . "\nContoh mau hapus note nomor 2, 5, dan 11. Kamu bisa tulis \".del 2 5 11\"";

        $help = "\tHow To Use\n‣To save Note: Type \".add [your note]\"\n‣To delete Note: Type \".del [note number]\"\n ^You can delete multiple notes, e.g. \".del 2 1 3\" will delete notes num 2, 1, and 3.\n‣To view Note List: Type \".show\"\n‣For Help: Type \".help\"\n\n*The Notes saved in this To-Do List will be different for each prvate chat, multi chat, and group chat. So, you can create personal To-Do List and To-Do List for team.";
        $message = "Oops, there's something wrong.";
        $text = $event['message']['text'];
        $trim = trim($text);
        $words = preg_split("/[\s,]+/", $trim);
        $intent = $words[0];
        $note = null;

        $additionalMessage = null;

        // create the right words
        if (isset($words[1])) {
            array_splice($words, 0, 1);
            $note = implode(" ", $words);
        }

        $source = $event['source']['type'];
        $res = $this->bot->getProfile($event['source']['userId']);
        $profile = $res->getJSONDecodedBody();

        if ($res->isSucceeded()) {
            $tableName = null;

            // determine the table database
            if ($source == "user") {
                $userId = $profile['userId'];
                $tableName = $userId;
            } else if ($source == "room") {
                $roomId = $event['source']['roomId'];
                $tableName = $roomId;
            } else if ($source == "group") {
                $groupId = $event['source']['groupId'];
                $tableName = $groupId;
            }

            if ($tableName) {

                // if bot needs to leave
                if (strtolower($text) == "bot leave") {
                    if ($source != "user") {
                        $message = "bye ges " . $this->emojiBuilder('10007C');
                        $textMessageBuilder = new TextMessageBuilder($message);
                        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
                        if ($source == "room") {
                            $this->bot->leaveRoom($roomId);
                        } else if ($source == "group") {
                            $this->bot->leaveGroup($groupId);
                        }
                    }
                }

                switch (strtolower($intent)) {
                    case '.add':
                        if ($note) {
                            $reply = "Note Saved " . $this->emojiBuilder('100041');
                            $message = $this->memoryGateway->rememberThis($tableName, $note, $reply);
                        } else {
                            $message = "What note do wanna add?\nType \".add [your note]\"!";
                        }
                        break;
                    case '.del':
                        if ($note) {

                            // test
                            // $message = "OOPS";
                            // $words = ["2", 3, 1, 2, 6];
                            
                            $deleteCount = count($words);
                            $newWords = array();

                            // check input
                            for ($i = 0; $i < $deleteCount; $i++) {

                                // check if the input is one digit integer only
                                if (strlen($words[$i]) != 1 || !is_numeric($words[$i])) {
                                    $isPassed = false;
                                    break;
                                }
                                if (!is_int($words[$i] + 0)) {
                                    $isPassed = false;
                                    break;
                                }
                                $number = (int) $words[$i];
                                
                                // check if there's same input
                                if (in_array($number, $newWords)) {
                                    $message = "Oops, you can't delete the same number";
                                    $isPassed = false;
                                break;
                                }

                                // check if input is not out of bound
                                if ($number > $this->memoryGateway->count($tableName)) {
                                    $isPassed = false;
                                break;
                                } else {
                                    array_push($newWords, $number);
                                    $isPassed = true;
                                }
                            }

                            // delete note
                            if (isset($isPassed) && $isPassed) {
                                // set reply
                                $reply = "Note Deleted " . $this->emojiBuilder('10008F');

                                // algorithm for deleting table item based on array
                                $deletes = array();
                                $temp = 0;
                                $smallest = $words[0];
                                foreach ($words as $value) {
                                    $final = $value;
                                    if ($value > $smallest) {
                                        $final = $value - $temp;
                                    } elseif ($value < $smallest) {
                                        $smallest = $value;
                                    }
                                    $temp++;
                                    array_push($deletes, $final);
                                }

                                for ($i = 0; $i < count($deletes); $i++) {
                                    // echo ($deletes[$i]);
                                    $this->memoryGateway->forgetMemory($tableName, $deletes[$i]);
                                }
                                $message = $reply;
                            }
                        } else {
                            $message = "What note do you wanna delete?\nType \".del [note number]\"";
                        }
                        break;
                    case '.show':
                        $message = $this->remembering($tableName);
                        if ($note) {
                            $additionalMessage = new TextMessageBuilder("Just type \".show\" to see your To-Do List.");
                        }
                        break;
                    case '.help':
                        $message = $help;
                        if ($note) {
                            $additionalMessage = new TextMessageBuilder("Just type \".help\" for help.");
                        }
                        break;

                    default:
                        if ($source == "user") {
                            $message = "Sorry, mate. I don't understand. " . $this->emojiBuilder('100084') . "\nType \".help\" for help.";
                        } else {
                            return;
                        }
                        break;
                }
                // if (strtolower($intent) == '#~delete') {
                //     $this->memoryGateway->down($profile['userId']);
                //     $message = "You have deleted all the memories";
                // }
            }
        } else {
            $mustAddMessage = "Hi, add me as friend first so I can help you remember your To-Do List " . $this->emojiBuilder('10007A');
            switch (strtolower($intent)) {
                case '.add':
                case '.del':
                case '.show':
                case '.help':
                    $message = $mustAddMessage;
                    break;

                default:
                    if (strtolower($text) != "bot leave") {
                        return;
                    } else {
                        $message = $mustAddMessage;
                        break;
                    }
            }
        }

        // send response
        $multiMessageBuilder = new MultiMessageBuilder();
        if ($additionalMessage) {
            $multiMessageBuilder->add($additionalMessage);
        }
        $textMessageBuilder = new TextMessageBuilder($message);
        $multiMessageBuilder->add($textMessageBuilder);
        $response = $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);
    }

    private function remembering($tableName)
    {
        $total = $this->memoryGateway->count($tableName);
        if ($total != 0) {
            $emoji = $this->emojiBuilder('10006C');
            $list = array("$emoji To-Do List:");
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
            $theMessage = "Yeay. No list you have to do.";
        }

        return $theMessage;
    }

    private function emojiBuilder($code)
    {
        $bin = hex2bin(str_repeat('0', 8 - strlen($code)) . $code);
        return mb_convert_encoding($bin, 'UTF-8', 'UTF-32BE');
    }
}
