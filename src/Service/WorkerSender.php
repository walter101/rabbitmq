<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class WorkerSender
{

    /** @var AMQPStreamConnection */
    private $connection;

    /** @var SerializerInterface */
    private $serializer;

    /** @var array */
    private $testExchange = [
        'exchange' => 'test.exchange.name',
        'queue' => 'test.queue.name',
        'routing_key' => 'test.routing.key',
        'type' => 'direct',
        'ttl' => 30000
    ];
    /** @var array */
    private $deadLetterExchange = [
        'exchange' => 'DLX.dead.exchange',
        'queue' => 'DLX.dead.queue',
        'routing_key' => 'DLX.dead.routing.key',
        'type' => 'direct',
        'ttl' => 1000000
    ];
    /** @var array */
    private $processorExchange = [
        'exchange' => 'processor.exchange',
        'queue' => 'processor.queue',
        'routing_key' => 'processor.routing.key',
        'type' => 'direct',
        'ttl' => 50000000
    ];

    public function __construct(
        AMQPStreamConnection $connection,
        SerializerInterface $serializer
    ) {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * Basic: send message to queue
     * Message is simple string
     * Queue-name is hardcoded in the queue_declare: 'hello'
     *
     * @param $message
     */
    public function publishMessage($message)
    {
        $channel = $this->connection->channel();

        // Declare 'hello' queue
        $channel->queue_declare('hello', false, false, false, false);

        $messageSerialized = $this->serializer->serialize($message, 'json');
        $msg = new AMQPMessage($messageSerialized);

        // Send message to 'hello' queue
        $channel->basic_publish($msg, '', 'hello');
    }

    /**
     * Publish message to an exchange
     * The exchange sends the incoming messages to one or more queues
     * The type of the exchange determines how the messages are sned: to one queue or to all queues
     * The exchange in this example will be of type: fanout -> sends the same message to all consumers
     */
    public function publishToFanoutExchange($message)
    {
        $channel = $this->connection->channel();

        // De clare the 'logs' exchange with type = fanout
        $channel->exchange_declare('logs', 'fanout', false, false, false);

        $messageSerialized = $this->serializer->serialize($message, 'json');
        $msg = new AMQPMessage($messageSerialized);

        // Publish the message to the 'logs' exchange (it will send out the message to all consumers listening at this moment)
        $channel->basic_publish($msg, 'logs');
    }

    /**
     * Publish a MessageObject
     * Before publishing we need to serialise the object (rabbitmq accepts only strings)
     * The listener needs to deserialize the message back into the object
     *
     * @param $message
     */
    public function publishMessageByType($emailMessage)
    {
        $queueName = QueueMapper::$QueueMapper[get_class($emailMessage)];

        $channel = $this->connection->channel();

        // Declare and exchange of type direct
        // with AMQPTable configuration we set the dead letter exchange
        // if we nack the message in the processor it will be send to that dead letter exchange
        $channel->exchange_declare($this->testExchange['exchange'], 'direct', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false, false , new AMQPTable([
            'x-dead-letter-exchange' => $this->deadLetterExchange['exchange'],
            'x-dead-letter-routing-key' => $this->deadLetterExchange['routing_key'],
            'requeue' => false,
            'x-message-ttl' => $this->testExchange['ttl']

        ]));
        // Bind this queue to the '' Verbind $queueName aan de DLX.exchange.name
        $channel->queue_bind($queueName, $this->testExchange['exchange'], $this->testExchange['routing_key']);

        $messageSerialized = $this->serializer->serialize($emailMessage, 'json');

        $rabbitmqMessage = new AMQPMessage($messageSerialized);

        $channel->basic_publish($rabbitmqMessage, '', $queueName);
    }


    public function publishMessageToDeadLetterExchange($emailMessage)
    {
        $channel = $this->connection->channel();
        // Create the DLX exchange Type: direct
        $channel->exchange_declare($this->deadLetterExchange['exchange'], 'direct', false, true, false);
        // Create queue set the routing-key
        $channel->queue_declare($this->deadLetterExchange['queue'], false, true, false, false, false, new AMQPTable([
            'requeue' => true,
        ]));
        // Bind this queue to the DLX exchange and set routing-key
        $channel->queue_bind($this->deadLetterExchange['queue'], $this->deadLetterExchange['exchange'], $this->deadLetterExchange['routing_key']);

        $messageSerialized = json_encode($emailMessage);

        $rabbitmqMessage = new AMQPMessage($messageSerialized);

        $channel->basic_publish($rabbitmqMessage, $this->deadLetterExchange['exchange'], $this->deadLetterExchange['routing_key']);
    }


    public function publishMessageByTypeTest($message)
    {
        $queueName = QueueMapper::$QueueMapper[get_class($message)];

        $channel = $this->connection->channel();
        $channel->exchange_declare($this->processorExchange['exchange'], 'direct', false, true, false);

        $channel->queue_declare($queueName, false, true, false, false, false , new AMQPTable([
            'x-dead-letter-exchange' => $this->deadLetterExchange['exchange'],
            'x-dead-letter-routing-key' => $this->deadLetterExchange['routing_key'],
            'requeue' => false,
            'x-message-ttl' => $this->processorExchange['ttl']

        ]));
        // Bind queueuName to exchange and routingKey
        $channel->queue_bind($queueName, $this->processorExchange['exchange'], $this->processorExchange['routing_key']);

        $messageSerialized = $this->serializer->serialize($message, 'json');

        $rabbitmqMessage = new AMQPMessage($messageSerialized);

        $channel->basic_publish($rabbitmqMessage, '', $queueName);
    }


    // Publish message to normal exchange with a dlx configurerd,
    // so when message is nacked it should

    /**
     *
     * Wat ga ik doen:
     * Ik publiseer 5 berichten naar de 'test.step.one.queue'
     *
     * Dan maak ik een processor die die berichten uit 'test.step.one.queue' ophaalt en verwerkt
     * In die verwerking gebruitk ik een try catch
     * Met een random number bepaal ik dan 75% van de berichten uitvalt:
     *
     *Als een bericht uitvalt: moet je met zie Sivi: $this->>rejectAttempt($msg) >= dan 5 dan ...
     *basic->ack
     */
}