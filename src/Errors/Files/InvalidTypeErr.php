<?php

namespace Symphograph\Bicycle\Errors\Files;

class InvalidTypeErr extends FileErr
{
    public function __construct(
        private readonly string $expected,
        private readonly string $given
    )
    {
        $message = $this->buildMsg();
        parent::__construct($message);
    }

    private function buildMsg(): string
    {
        return "Invalid file Type. Expected: $this->expected, Given: $this->given.";
    }
}