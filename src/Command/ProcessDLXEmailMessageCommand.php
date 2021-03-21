<?php

namespace App\Command;

use App\Service\ProcessDLXEmailMessageService;
use App\Service\ProcessEmailMessageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessDLXEmailMessageCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'processDLXEmailMessage';

    /** @var ProcessDLXEmailMessageService */
    private $processDLXEmailMessageService;

    public function __construct(ProcessDLXEmailMessageService $processDLXEmailMessageService)
    {
        parent::__construct();
        $this->processDLXEmailMessageService = $processDLXEmailMessageService;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('start listening tot DLX.exchange');
        $this->processDLXEmailMessageService->processDeadLetterExchangeEmailMessages();
    }
}
