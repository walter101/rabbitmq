<?php

namespace App\Command;

use App\Service\ProcessProcessorMessageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessProcessorMessageCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'processProcessorMessage';

    /** @var ProcessProcessorMessageService */
    private $processProcessorMessageService;

    public function __construct(ProcessProcessorMessageService $processEmailMessageService)
    {
        parent::__construct();
        $this->processProcessorMessageService = $processEmailMessageService;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting service:');
        $this->processProcessorMessageService->processProcessorMessages();
    }
}
