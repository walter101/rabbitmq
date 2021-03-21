<?php

namespace App\Service;

use App\Message\DLXEmailMessage;
use App\Message\EmailMessage;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessDLXEmailMessageService
{
    /** @var int */
    const MAX_NUMBER_RETRIES = 5;

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
    public function processDeadLetterExchangeEmailMessages()
    {
        /** @var EmailMessage $message */
        $this->workerReciever->listenToDeadLetterExchange(EmailMessage::class, function(AMQPMessage $message) {


            // Bepaal hoe vaak dit bericht langs geweest is??

            // this message is re-processor for the  ..th time
            $deliveryTag = $message->getDeliveryTag();

            $isRedelivered = $message->isRedelivered();

            $attempts = $this->rejectAttempts($message);

            // Try to read the properties to check the application_headers to find out how many times x-death is present?
            $properties = $message->get_properties();
            if (isset($properties['application_headers'])) {
                $applicationHeaders = $properties['application_headers'];
            }

            $messageCount = $message->getMessageCount();

            $a = 10;
            //$body = json_decode($message->body);
            //$body->try_attempt = !empty($body->try_attempt)? $body->try_attempt+1 : 1;

            //$maxRedelivered = $message->getMessageCount();
            //$message->setMessageCount($maxRedelivered+1);






            $emailMessageString = $this->serializer->deserialize($message->getBody(), EmailMessage::class, 'json');
            $messageBody = $message->getBody();

            $random = rand(1, 100);
            //$random = 50;
            /**
             * Random number <= 20: processing message succesful -> send ACK
             * Random number > 20: processing message fails -> send NACK
             */


            // Als <= 45 dan is bericht goed verwerkt: ack het bericht, DONE!
            if ($random <= 25) {
                $message->ack($message);
                $this->logger->error('DLXEmailMessageService succefully processed message after deliveryTag:'. $message->getDeliveryTag() . ' / deliveryInfo '.$message->delivery_info['delivery_tag'].' times');
            } else {
                // Requeue the DLXEmailMessage up to 5 times
                $attempts = $this->rejectAttempts($message);

                //if ($attempts <= self::MAX_NUMBER_RETRIES) {
                $this->logger->error('Processing failed. redelivering the message for the ' . $message->getDeliveryTag() . 'th time');


                //if ($deliveryTag >= 5) {
                if ($attempts >= 5) {
                    // ACK, redeliver: false, We're done!
                    $this->logger->error('Failed to process after 5 times we ack the message. Message wont be processed no more');

                    $message->ack(false);
                } else {
                    // Ik NACK de message en set redeliver op true
                    // Ik verwacht dat bericht weer terug komt op de queue: dat klopt
                    // Ik verwacht ook dat ik na de NACK meerdere rejects kan zien in de x-death array in de method rejectAttempts.. en dat is niet zo
                    // Het is er altijd maar 1, dus ik kan zien: hij is hier voor de tweede keer, maar ik wil na 5 keer ACK-en en niet meer requeuen ...
                    $message->nack(true);
                }
            }
        });
    }

    private function rejectAttempts(AMQPMessage $message)
    {
        $attemps = 0;

        $properties = $message->get_properties();

        if (isset($properties['application_headers'])) {
            $applicationHeaders = $properties['application_headers'];
            $xdeath = !isset($applicationHeaders->getNativeData()['x-death']) ?: $applicationHeaders->getNativeData()['x-death'];

            if (!is_array($xdeath)) {
                return $attemps;
            }

            $attemps = count(array_filter($xdeath, function ($data) {
                return in_array($data['reason'], ['rejected', 'expired']);
            }));

            return $attemps;
        }
    }
}