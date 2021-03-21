<?php
require_once '../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('host.docker.internal:5672', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('email.message', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    $connection = new AMQPStreamConnection('host.docker.internal:5672', 5672, 'guest', 'guest');

    echo ' [x] Received ', $msg->body, "\n";

    sleep(10);

    $rand = rand(1, 100);
    echo 'rand = ' . $rand . '\n';

    if ($rand < 50) {
        // return the msg to the queue (cause of error or smth)
        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
        echo 'Message not ok, NACK, back to queue';
    } else {
        // Send ack, remove form queue
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        echo 'Message successfully processed: send ACK ';
    }
};

$channel->basic_consume('email.message', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}