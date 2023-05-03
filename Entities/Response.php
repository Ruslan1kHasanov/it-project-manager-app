<?php

class Response
{
    public bool $error;
    public string $message;
    public string $raw_data;

    function __construct(bool $error, string $message, string $raw_data = "")
    {
        $this->error = $error;
        $this->message = $message;
        $this->raw_data = $raw_data;
    }
}