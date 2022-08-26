<?php

namespace DS\Rest\Http;

use DS\Rest\Helpers;
use DS\Rest\Interfaces\BaseInterface;
use Bitrix\Main\Application;
use Bitrix\Main\HttpRequest;

class Request implements BaseInterface
{
    /**
     * Http запрос
     * @var HttpRequest
     */
    public HttpRequest $request;

    /**
     * Входные данные http запроса
     * @var array
     */
    protected array $dataCollection;

    public function __construct()
    {
        $this->request = Application::getInstance()->getContext()->getRequest();
        $this->run();
    }

    /**
     * Получение массива данных входящего http запроса
     * @return array
     */
    public function getData(): array
    {
        return $this->dataCollection ?? [];
    }

    /**
     * Получение массива данных запроса
     * @return bool
     */
    public function run(): bool
    {
        $result = [];

        $result = $this->request->isPost()
            ? $this->request->getPostList()->toArray()
            : $this->request->getQueryList()->toArray();

        $resultJsonValidation = Helpers\Str::jsonValidate(HttpRequest::getInput());
        if ($resultJsonValidation->isSuccess()) {
            $result = array_merge($result, $resultJsonValidation->getData());
        }
        unset($resultJsonValidation);
        $this->dataCollection = $result;

        return true;
    }
}