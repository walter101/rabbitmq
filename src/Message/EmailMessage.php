<?php
namespace App\Message;

use Assert\Assertion;

class EmailMessage
{

    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var string */
    private $emailAddress;

    private function __construct(array $data)
    {
        $this->firstName = $data['firstName'];
        $this->lastName = $data['lastName'];
        $this->emailAddress = $data['emailAddress'];
    }

    public static function create(array $data)
    {
        self::validate($data);
        return new self($data);
    }

    private static function validate(array $data)
    {
        Assertion::string($data['firstName']);
        Assertion::string($data['lastName']);
        Assertion::string($data['emailAddress']);
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
}