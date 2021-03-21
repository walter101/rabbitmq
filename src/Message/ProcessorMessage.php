<?php

namespace App\Message;

use Assert\Assertion;

class ProcessorMessage
{
    /** @var string */
    private $name;
    /** @var string */
    private $process;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->process = $data['processor'];
    }

    public static function create(array $data)
    {
        self::validate($data);
        return new self($data);
    }

    private static function validate(array $data)
    {
        Assertion::string($data['name'] );
        Assertion::string($data['processor']);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getProcess(): string
    {
        return $this->process;
    }


}