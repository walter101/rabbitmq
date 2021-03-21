<?php

namespace App\Controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AmpqSendMessage extends AbstractController
{

    /**
     * @Route("/sendMessageBasic")
     */
    public function sendMessage()
    {
        $connection = new AMQPStreamConnection('host.docker.internal:5672', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('hello', false, false, false, false);

        $msg = new AMQPMessage('hallo ik ben walter');
        $channel->basic_publish($msg, '', 'hello');

        die('We did send message to rabbitMQ');
    }

}