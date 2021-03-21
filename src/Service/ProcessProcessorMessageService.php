<?php

namespace App\Service;

use App\Message\ProcessorMessage;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessProcessorMessageService
{
    /** @var WorkerReciever */
    private $workerReciever;
    /** @var SerializerInterface */
    private $serializer;
    /** @var WorkerSender */
    private $workerSender;
    /** @var LoggerInterface */
    private $logger;

    /**
     * ProcessEmailMessageService constructor.
     * @param WorkerReciever $workerReciever
     * @param SerializerInterface $serializer
     * @param WorkerSender $workerSender
     * @param LoggerInterface $markdownLogger
     */
    public function __construct(
        WorkerReciever $workerReciever,
        SerializerInterface $serializer,
        WorkerSender $workerSender,
        LoggerInterface $markdownLogger
    ) {
        $this->workerReciever = $workerReciever;
        $this->serializer = $serializer;
        $this->workerSender = $workerSender;
        $this->logger = $markdownLogger;
    }

    /**
     * @throws \ErrorException
     */
    public function processProcessorMessages()
    {
        /** @var ProcessorMessage $message */
        $this->workerReciever->listenToProcessorQueue(ProcessorMessage::class, function(AMQPMessage $message) {

            $test = $message->getBody();
            $test2 = json_decode($message->getBody());

            //$emailMessageString = $this->serializer->deserialize($message->getBody(), ProcessorMessage::class, 'json');
            //$messageBody = $message->getBody();

            $random = rand(1, 40);

            //---------------------------------------
            // Bepaal hoe vaak dit bericht langs geweest is??
            $deliveryTag = $message->getDeliveryTag();
            $isRedelivered = $message->isRedelivered();
            // Try to read the properties to check the application_headers to find out how many times x-death is present?
            $properties = $message->get_properties();
            if (isset($properties['application_headers'])) {
                $applicationHeaders = $properties['application_headers'];
            }
            $messageCount = $message->getMessageCount();
            //---------------------------------------


            /**
             * Random number <= 20: processing message succesful -> send ACK
             * Random number > 20: processing message fails -> send NACK
             */
            if ($random <= 20) {
                $this->logger->alert('EmailMessageService succefully processed message');
                $message->ack(false);

            } else {
                // requeue the message to the DLX
                //$this->workerSender->publishMessageToDeadLetterExchange($message);
                $this->logger->error('EmailMessageService failed to process message. (sending message to the DLX.email.message queue');

                // Door te NACK-en verwacht ik dat het bericht niet meer naar de normale email.message queue terug gaat, maar naar de DLX.exchange
                // Hierboven handmatig een bericht naar de DLX sturen lijkt me niet de bedoeling? Waarom anders een dlx argument configureren?
                $message->nack(false);

                //$message->basic_nack($message, false, false);
            }

            // ACK message: success: done OR fail -> re-queue to DLX
        });
    }
}