<?php

namespace DS\Rest\Interfaces;

interface BaseInterface
{
    /**
     * Начальная точка входа
     * @return bool
     */
    public function run(): bool;
}