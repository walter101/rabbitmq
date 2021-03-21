<?php

namespace App\Command;

use App\Service\ProcessEmailMessageService;
use App\Service\ProcessMessageFanoutService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessMessageFanoutExchangeCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'processMessageFanout';

    /** @var ProcessEmailMessageService */
    private $processMessageFanoutService;

    public function __construct(ProcessMessageFanoutService $processEmailMessageService)
    {
        parent::__construct();
        $this->processMessageFanoutService = $processEmailMessageService;
    }

    /**
     * Each consumer (this) that is started will get the messages send from the fanout exchange
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     *
     * @throws \ErrorException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting service:');
        $this->processMessageFanoutService->processMessagesFanout();
    }
}
