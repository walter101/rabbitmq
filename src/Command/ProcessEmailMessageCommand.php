<?php

namespace App\Command;

use App\Service\ProcessEmailMessageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessEmailMessageCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'processEmailMessage';

    /** @var ProcessEmailMessageService */
    private $processEmailMessageService;

    public function __construct(ProcessEmailMessageService $processEmailMessageService)
    {
        parent::__construct();
        $this->processEmailMessageService = $processEmailMessageService;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting listening to EmailMessages:');
        $this->processEmailMessageService->processEmailMessages();
    }
}
