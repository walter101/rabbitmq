<?php

namespace App\Controller;

use App\Message\DLXEmailMessage;
use App\Message\EmailMessage;
use App\Message\Message;
use App\Service\WorkerSender;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class WorkerSenderController extends AbstractController
{

    /** @var WorkerSender */
    private $workerSender;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        WorkerSender $workerSender,
        LoggerInterface $markdownLogger
    ) {
        $this->workerSender = $workerSender;
        $this->logger = $markdownLogger;
    }

    /**
     * Send a message to a queue name: 'hello'
     *
     * @Route("/sendMessage")
     */
    public function publishMessage()
    {
        $data = [
            'name' => 'basic message'
        ];
        $message = Message::create($data);
        $this->workerSender->publishMessage($message);

        return $this->render('rabbitmq/rabbitmq.publish.1.to.named.queue.html.twig');
    }

    /**
     * Send a message to a exchange type fanout
     * If no consumers are listening to this exchange the message is lost
     * one consumers listen: each consumer will receive the message publised by the fanout exchange
     *
     * @Route("/sendMessageToFanoutExchange")
     */
    public function publishMessagetoFanoutExchange()
    {
        $data = [
            'name' => 'basic message'
        ];
        $message = Message::create($data);
        $this->workerSender->publishToFanoutExchange($message);

        return $this->render('rabbitmq/rabbitmq.publish.1.to.exchange.fanout.html.twig');
    }

    /**
     * @Route("/sendEmailMessage")
     */
    public function publishEmailMessage()
    {
        $data = [
            'firstName' => 'Walter',
            'lastName' => 'Pothof',
            'emailAddress' => 'walterpothof@gmail.com'
        ];

        for ($i=1;$i<=1;$i++) {
            $data['firstName'] = 'Walter' . $i;
            $emailMessage = EmailMessage::create($data);

            $this->logger->info('Published message (' . $i . ')');
            $this->workerSender->publishMessageByType($emailMessage);
        }

        return $this->render('rabbitmq/rabbitmq.publish.5.messages.html.twig');
    }

    /**
     * @Route("/sendDeadLetterExchangeEmailMessage")
     */
    public function publishOneToDealLetterExchangeEmailMessage()
    {
        $data = [
            'firstName' => 'Walter',
            'lastName' => 'Pothof',
            'emailAddress' => 'walterpothof@gmail.com'
        ];
        $emailMessage = DLXEmailMessage::create($data);

        $this->logger->info('Published DLXEmailMessage');
        $this->workerSender->publishMessageToDeadLetterExchange($emailMessage);

        return $this->render('rabbitmq/rabbitmq.publish.1.to.dlx.html.twig');
    }


}