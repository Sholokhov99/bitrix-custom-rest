<?php

namespace DS\Rest\Http;

use Bitrix\Main\Diag\Debug;
use DS\Rest\Actions\Base as ActionBase;
use DS\Rest\Application;
use DS\Rest\Result;

class Routs
{
    public const SPLICE_METHOD = "@";

    public const SPLICE_PATH = ":";

    public const DEFAULT_FOLDER_ACTIONS = "actions";

    public const DEFAULT_METHOD_ACTION = "run";

    public bool $callValidate = true;

    public ?ActionBase $action;

    public array $request;

    public string $namespaceAction;

    public string $methodNameCheckAccess;

    public string $methodAction;

    public string $keyNamespaceAction;

    public bool $outerAction;

    public function __construct(array $request)
    {
        $this->request = $request;
        $this->keyNamespaceAction = "action";
        $this->outerAction = $this->isOuterAction();
        $this->methodNameCheckAccess = "checkAccess";

        $this->parsingAction();
    }

    public function isOuterAction(): bool
    {
        return strripos($this->getActionValue(), static::SPLICE_METHOD) !== false;
    }

    public function getDefaultNamespace(): string
    {
        $reflectionClass = new \ReflectionClass(Application::class);
        return $reflectionClass->getNamespaceName() . "\\" . static::DEFAULT_FOLDER_ACTIONS;
    }

    public function parsingAction(): string
    {
        $actionPath = $this->getActionValue();

        $namespaceCollection = array_diff(explode(static::SPLICE_METHOD, $actionPath), ['']);

        $this->methodAction = count($namespaceCollection) < 2
            ? static::DEFAULT_METHOD_ACTION
            : end($namespaceCollection);

        if ($this->isOuterAction()) {
            $this->namespaceAction = reset($namespaceCollection);
        } else {
            $this->namespaceAction = $this->getDefaultNamespace() . '\\' . reset($namespaceCollection);
        }
        $this->namespaceAction = str_replace(static::SPLICE_PATH, '\\', $this->namespaceAction);

        return $this->namespaceAction;
    }

    public function isMethodExits(): bool
    {
        return class_exists($this->namespaceAction) && method_exists($this->namespaceAction, $this->methodAction);
    }

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

    public function getMethodAction(): string
    {
        return $this->methodAction ?? "";
    }

    public function getAction(): ?ActionBase
    {
        return $this->action ?? null;
    }

    public function getActionValue(): string
    {
        if (isset($this->request[$this->keyNamespaceAction]) === false || is_string($this->request[$this->keyNamespaceAction]) === false) {
            $this->request[$this->keyNamespaceAction] = "";
        }

        return $this->request[$this->keyNamespaceAction];
    }

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
            $result->setError('Отсутствует декларированный исполнитель');
            return $result;
        }

        $this->action = new $this->namespaceAction;

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
        return $result;
        $action = $this->getAction();

        if (is_null($action)) {
            $result->setError('Не зарегистрирован экземпляр класаа исполнителя');
            return $result;
        }

        if (method_exists(get_class($this->action), $this->methodNameCheckAccess) === false) {
            $result->setError('Отсутствует защита веб-хука');
            return $result;
        }

        if ($this->action->{$this->methodNameCheckAccess}() === false) {
            $result->setError('Ошибка доступа');
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
            $result->setError('Отсутствует валидация у метода');
            return $result;
        }

        if ($this->getAction()->{$method}() === false) {
            $result->setError('Ошибка валидации');
            return $result;
        }

        return $result;
    }
}