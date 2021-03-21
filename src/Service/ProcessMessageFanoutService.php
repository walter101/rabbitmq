<?php

namespace App\Service;

use App\Message\EmailMessage;
use App\Message\Message;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessMessageFanoutService
{
    /** @var WorkerRecieverFanout */
    private $workerRecieverFanout;
    /** @var SerializerInterface */
    private $serializer;
    /** @var WorkerSender */
    private $workerSender;
    /** @var LoggerInterface */
    private $logger;

    /**
     * ProcessEmailMessageService constructor.
     * @param WorkerRecieverFanout $workerRecieverFanout
     * @param SerializerInterface $serializer
     * @param WorkerSender $workerSender
     * @param LoggerInterface $markdownLogger
     */
    public function __construct(
        WorkerRecieverFanout $workerRecieverFanout,
        SerializerInterface $serializer,
        WorkerSender $workerSender,
        LoggerInterface $markdownLogger
    ) {
        $this->workerRecieverFanout = $workerRecieverFanout;
        $this->serializer = $serializer;
        $this->workerSender = $workerSender;
        $this->logger = $markdownLogger;
    }

    /**
     * @throws \ErrorException
     */
    public function processMessagesFanout()
    {
        /** @var Message $message */
        $this->workerRecieverFanout->listen(Message::class, function(AMQPMessage $message) {

            $emailMessageString = $this->serializer->deserialize($message->getBody(), Message::class, 'json');
            $messageBody = $message->getBody();

            $messageDecoded = json_decode($messageBody);
            echo $messageDecoded->name;
            //$message->ack(false);
        });
    }
}