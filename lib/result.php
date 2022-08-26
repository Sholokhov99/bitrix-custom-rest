<?php

namespace DS\Rest;

use Bitrix\Main;

class Result extends Main\Result
{
    /**
     * Добавление ошибки
     * @param string $message
     * @param int $code
     * @return self
     */
    public function setError(string $message, int $code = 0): self
    {
        return $this->addError(new Main\Error($message, $code));
    }
}