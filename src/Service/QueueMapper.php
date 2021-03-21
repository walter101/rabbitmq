<?php

namespace App\Service;

use App\Message\DLXEmailMessage;
use App\Message\EmailMessage;
use App\Message\ProcessorMessage;

class QueueMapper
{

    public static $QueueMapper = [
            EmailMessage::class => 'email.message',
            DLXEmailMessage::class => 'DLX.email.message',
            ProcessorMessage::class => 'processor.message'
        ];
}