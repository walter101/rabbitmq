<?php
namespace App\Message;

use Assert\Assertion;

class Message
{
    /** @var string */
    private $name;

    private function __construct(array $data)
    {
        $this->name = $data['name'];
    }

    public static function create(array $data)
    {
        self::validate($data);
        return new self($data);
    }

    private static function validate(array $data)
    {
        Assertion::string($data['name']);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}