<?php

namespace DS\Rest\Actions;

class TestAction extends Base
{
    public function run(): bool
    {
        return true;
    }

    public function validateRun(): bool
    {
        return true;
    }
}