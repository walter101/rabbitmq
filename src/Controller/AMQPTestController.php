<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AMQPTestController extends AbstractController
{

    /**
     * @Route("amqptest")
     */
    public function testAqmp()
    {
        die('amqp');
    }
}