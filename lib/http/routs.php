<?php

namespace DS\Rest\Http;

use DS\Rest\Actions\Base as ActionBase;
use DS\Rest\Application;
use DS\Rest\Result;
use DS\Rest\Interfaces\Http\RouteInterface;
use DS\Rest\Interfaces\BaseInterface;
use Bitrix\Main\Localization\Loc;

class Routs implements RouteInterface, BaseInterface
{
    /**
     * Разделитель пространства имен от метода обработки rest запроса
     * @var string
     */
    public const SPLICE_METHOD = "@";

    /**
     * Разделитель пути до класса обработчика rest запроса
     * @var string
     */
    public const SPLICE_PATH = ":";

    /**
     * Наименование стандартной папки хранения огбработчиков rest запроса
     * @var string
     */
    public const DEFAULT_FOLDER_ACTIONS = "Actions";

    /**
     * Метод стандартной точки входа
     * @var string
     */
    public const DEFAULT_METHOD_ACTION = "run";

    /**
     * Производить валидацию метода
     * @var bool
     */
    public bool $callValidate = true;

    /**
     * Обработчик rest запроса
     * @var ActionBase|null
     */
    protected ?ActionBase $action;

    /**
     * HTTP запрос
     * @var Request
     */
    protected Request $request;

    /**
     * Пространство имен в котором происходит обработка rest апроса
     * @var string
     */
    protected string $namespaceAction;

    /**
     * Название метода, в котором происходит проверка доступа к обработчику rest запроса
     * @var string
     */
    protected string $methodNameCheckAccess;

    /**
     * Название метода, в котором происходит обработка rest запроса
     * @var string
     */
    protected string $methodAction;

    /**
     * Ключ массива в котором хранится путь обработчика rest запроса
     * @var string
     */
    protected string $keyNamespaceAction;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->keyNamespaceAction = "action";
        $this->methodNameCheckAccess = "checkAccess";

        $this->run();
    }

    /**
     * Проверка на использование стандартного метода обработки rest запроса
     * @return bool
     */
    public function isDefaultMethod(): bool
    {
        return strripos($this->getActionValue(), static::SPLICE_METHOD) === false;
    }

    /**
     * Получение стандартного пространства имен
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        $reflectionClass = new \ReflectionClass(Application::class);
        return $reflectionClass->getNamespaceName() . "\\" . static::DEFAULT_FOLDER_ACTIONS;
    }

    /**
     * Запуск механизма определения обработчика
     * @return bool
     */
    public function run(): bool
    {
        $actionPath = $this->getActionValue();

        $namespaceCollection = array_diff(explode(static::SPLICE_METHOD, $actionPath), ['']);

        $this->methodAction = count($namespaceCollection) < 2
            ? static::DEFAULT_METHOD_ACTION
            : end($namespaceCollection);

        if ($this->isDefaultMethod()) {
            $this->namespaceAction = $this->getDefaultNamespace() . '\\' . reset($namespaceCollection);
        } else {
            $this->namespaceAction = reset($namespaceCollection);
        }

        $this->namespaceAction = str_replace(static::SPLICE_PATH, '\\', $this->namespaceAction);

        return true;
    }

    /**
     * Проверка на существование обработчика rest запроса
     * @return bool
     */
    public function isMethodExits(): bool
    {
        return class_exists($this->getNamespaceActionName()) && method_exists($this->getNamespaceActionName(), $this->methodAction);
    }

    /**
     * Вызов метода обработчика rest запроса с его валидацией
     * @return Result
     */
    public function call(): Result
    {
        $result = new Result();

        $resultValidate = $this->validateCall();

        if ($resultValidate->isSuccess() === false) {
            return $resultValidate;
        }
        unset($resultValidate);

        $resultMethod = $this->getAction()->{$this->methodAction}();

        if ($resultMethod === false) {
            $errors = $this->getAction()->getAnswer()->getErrorByArray();
            foreach ($errors as $error) {
                $result->setError($error);
            }
        } else {
            $result->setData($this->getAction()->getAnswer()->getAnswerByArray());
        }

        return $result;
    }

    /**
     * Получение названия метода обработки rest запроса
     * @return string
     */
    public function getMethodAction(): string
    {
        return $this->methodAction ?? '';
    }

    /**
     * Получение пространства имен обработчика rest запроса
     * @return string
     */
    public function getNamespaceActionName(): string
    {
        return $this->namespaceAction ?? '';
    }

    /**
     * Получение обработчика rest запроса
     * @return ActionBase|null
     */
    public function getAction(): ?ActionBase
    {
        return $this->action ?? null;
    }

    /**
     * Получение строки маршрутизации rest запроса
     * @return string
     */
    public function getActionValue(): string
    {
        $requestData = $this->request->getData();
        if (isset($requestData[$this->keyNamespaceAction]) === false || is_string($requestData[$this->keyNamespaceAction]) === false) {
            return '';
        }

        return $requestData[$this->keyNamespaceAction];
    }

    /**
     * @todo Потом переделать механизм проверки, чтобы она проходила в классу security
     * Указание названия функции, которая проверяет доступа к обработчику
     * @param string $method
     * @return bool
     */
    public function setMethodNameCheckAccess(string $method): bool
    {
        $method = trim($method);
        if ($method <> '') {
            $this->methodNameCheckAccess = $method;
            return true;
        }

        return false;
    }

    /**
     * Валидация вызова исполнителя
     * @return Result
     */
    protected function validateCall(): Result
    {
        $result = new Result();

        if ($this->isMethodExits() === false) {
            $result->setError(Loc::getMessage('REST_ROUTE_ERROR_NOT_FOUND_METHOD_ACTION'));
            return $result;
        }

        $this->action = new $this->getNamespaceActionName();

        $resultCheckAccess = $this->checkAccess();
        if ($resultCheckAccess->isSuccess() === false) {
            return $resultCheckAccess;
        }
        unset($resultCheckAccess);

        if ($this->callValidate && ($validateMethod = $this->validateMethod())->isSuccess() === false) {
            return $validateMethod;
        }

        return $result;
    }

    /**
     * Проверка доступа к веб-хуку
     * @return Result
     */
    protected function checkAccess(): Result
    {
        $result = new Result();

        if (is_null($this->getAction())) {
            $result->setError(Loc::getMessage('REST_ROUTE_ERROR_NOT_FOUND_ACTION'));
            return $result;
        }

        if (method_exists(get_class($this->action), $this->methodNameCheckAccess) === false) {
            $result->setError(Loc::getMessage('REST_ROUTE_ERROR_NOT_FOUND_SECURE_ACTION'));
            return $result;
        }

        if ($this->action->{$this->methodNameCheckAccess}() === false) {
            $result->setError(Loc::getMessage('REST_ROUTE_ERROR_FORBIDDEN'));
            return $result;
        }

        return $result;
    }

    /**
     * Валидация метода
     * @return Result
     */
    protected function validateMethod(): Result
    {
        $result = new Result();
        $method = "validate{$this->getMethodAction()}";

        if (method_exists(get_class($this->getAction()), $method) === false) {
            $result->setError(Loc::getMessage('REST_ROUTE_ERROR_EMPTY_VALIDATE_METHOD'));
            return $result;
        }

        if ($this->getAction()->{$method}() === false) {
            $result->setError(Loc::getMessage('REST_ROUTE_ERROR_VALIDATE'));
            return $result;
        }

        return $result;
    }
}