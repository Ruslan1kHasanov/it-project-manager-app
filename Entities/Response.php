<?php

class Response {
    public bool $error;
    public string $message;

    function __construct(bool $error, string $message) {
        $this->error = $error;
        $this->message = $message;
    }
}