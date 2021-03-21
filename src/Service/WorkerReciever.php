<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

class WorkerReciever
{

    /** @var AMQPStreamConnection */
    private $connection;
    /** @var array */
    private $testExchange = [
        'exchange' => 'test.exchange.name',
        'queue' => 'test.queue.name',
        'routing_key' => 'test.routing.key',
        'type' => 'direct',
        'ttl' => 30000
    ];
    /** @var array  */
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

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * ListenMethod used by ProcessEmailMessageService
     *
     * @param $messageClass
     * @param callable $callback
     * @throws \ErrorException
     */
    public function listen($messageClass, callable $callback)
    {
        $queueName = QueueMapper::$QueueMapper[$messageClass];
        $channel = $this->connection->channel();
        $channel->exchange_declare($this->testExchange['exchange'], 'direct', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false, false , new AMQPTable([
            'x-dead-letter-exchange' => $this->deadLetterExchange['exchange'],
            'x-dead-letter-routing-key' => $this->deadLetterExchange['routing_key'],
            'requeue' => false,
            'x-message-ttl' => $this->testExchange['ttl']
        ]));
        // Bind this queue to the '' Verbind $queueName aan de DLX.exchange.name
        $channel->queue_bind($queueName, $this->testExchange['exchange'], $this->testExchange['routing_key']);

        $channel->basic_consume($queueName, '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
    }

    public function listenToDeadLetterExchange($messageClass, callable $callback)
    {
        $channel = $this->connection->channel();
        // Create the DLX exchange
        $channel->exchange_declare($this->deadLetterExchange['exchange'], 'direct', false, true, false);
        // Create queue on DLX exchange and set the routing-key
        $channel->queue_declare($this->deadLetterExchange['queue'], false, true, false, false, false, new AMQPTable([
            'requeue' => true,
        ]));
        // Bind this queue to the DLX exchange and set routing-key
        $channel->queue_bind($this->deadLetterExchange['queue'], $this->deadLetterExchange['exchange'], $this->deadLetterExchange['routing_key']);

        $channel->basic_consume($this->deadLetterExchange['queue'], '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
    }

    public function listenToProcessorQueue($messageClass, callable $callback)
    {
        $queueName = QueueMapper::$QueueMapper[$messageClass];
        $channel = $this->connection->channel();
        $channel->exchange_declare($this->processorExchange['exchange'], 'direct', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false, false , new AMQPTable([
            'x-dead-letter-exchange' => $this->deadLetterExchange['exchange'],
            'x-dead-letter-routing-key' => $this->deadLetterExchange['routing_key'],
            'requeue' => false,
            'x-message-ttl' => $this->processorExchange['ttl']
        ]));
        // Bind this queue to the '' Verbind $queueName aan de DLX.exchange.name
        $channel->queue_bind($queueName, $this->processorExchange['exchange'], $this->processorExchange['routing_key']);

        $channel->basic_consume($queueName, '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
    }
}