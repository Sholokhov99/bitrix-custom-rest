<?php

namespace DS\Rest\Http;

use DS\Rest\Heplers;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ArgumentException;

class Request
{
    public HttpRequest $request;

    protected array $dataCollection;

    public function __construct()
    {
        $this->request = Application::getInstance()->getContext()->getRequest();
        $this->dataCollection = $this->parsingData();
    }

    public function getData(): array
    {
        if (isset($this->dataCollection) === false) {
            $this->dataCollection = [];
        }

        return $this->dataCollection;
    }

    protected function parsingData(): array
    {
        $result = [];

        $result = $this->request->isPost()
            ? $this->request->getPostList()->toArray()
            : $this->request->getQueryList()->toArray();

        $resultJsonValidation = Heplers\Str::jsonValidate(HttpRequest::getInput());
        if ($resultJsonValidation->isSuccess()) {
            array_merge($result, $resultJsonValidation->getData());
        }
        unset($resultJsonValidation);

        return $result;
    }
}