<?php
require_once 'vendor/autoload.php';

use Predis\Client;
use AndrewBreksa\RSMQ\RSMQClient;

    $predis = new Client([
        'host' => 'redis',
        'port' => 6379
    ]);

    $rsmq = new RSMQClient($predis);

    function insertQueue($message, $rsmq, $messageResponse){
        
        $id = $rsmq->sendMessage('myqueue', $message);

        header('Location:index.php?messageResponse='.$messageResponse);
    }

    function retrieve($rsmq){
        $message = $rsmq->popMessage('myqueue');

        if(!empty($message)){
            $rs['FIFO'] = [
                'id' => $message->getId(),
                'message' => $message->getMessage(),
            ];
        }else{
            $rs = ['message' => 'Queue have been cleared !'];
        }

        header("Content-Type: application/json");
        echo json_encode($rs);
    }

    function total($rsmq){
        $attributes =  $rsmq->getQueueAttributes('myqueue');

        $rs = ['Current No of Messages in Queue' => $attributes->getMessageCount()];

        header("Content-Type: application/json");
        echo json_encode($rs);
    }

    function all($rsmq){
        $message['All Message in queue'] =  $rsmq->displayMessage('myqueue');

        header("Content-Type: application/json");
        echo json_encode($message);
    }

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);

        return $data;
    }

    if(isset($_GET['retrieve'])){
        retrieve($rsmq);
    }elseif(isset($_GET['total'])){
        total($rsmq);
    }elseif(isset($_GET['all'])){
        all($rsmq);
    }else{
        if(empty($_POST["message"])) {
            $messageResponse = '<span class="error">* SMS Message is required !</span>';

            header('Location:index.php?messageResponse='.$messageResponse);
            exit();
        }else{
            $message = test_input($_POST["message"]);
            $messageResponse = '<span class="success">* SMS Message succesfully been queued</span>';

            insertQueue($message, $rsmq, $messageResponse);
        }
    }