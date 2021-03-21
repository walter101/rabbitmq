<?php

namespace App\Controller;

use App\Message\DLXEmailMessage;
use App\Message\EmailMessage;
use App\Message\ProcessorMessage;
use App\Service\WorkerSender;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class WorkerSenderProcessorController extends AbstractController
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
     * @Route("/sendProcessorMessage")
     */
    public function publishProcessorMessage()
    {
        $data = [
            'name' => 'production',
            'processor' => 'processor'
        ];

        for ($i=1;$i<=1;$i++) {
            $data['name'] = 'production' . $i;
            $message = ProcessorMessage::create($data);

            $this->logger->info('Published processor message (' . $i . ')');
            $this->workerSender->publishMessageByTypeTest($message);
        }

        return $this->render('rabbitmq/rabbitmq.publish.5.processor.messages.html.twig');
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