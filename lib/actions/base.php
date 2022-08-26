<?php

namespace DS\Rest\Actions;

abstract class Base
{
    public function __construct()
    {
    }

    abstract public function run(): bool;

    abstract public function validateRun(): bool;

    public function getAnswer(): \stdClass
    {
        return new \stdClass();
    }
}