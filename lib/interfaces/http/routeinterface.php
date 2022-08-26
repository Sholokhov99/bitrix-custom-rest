<?php

namespace DS\Rest\Interfaces\Http;

use DS\Rest\Actions\Base as ActionBase;
use DS\Rest\Result;

interface RouteInterface
{
    /**
     * Проверка на использование стандартного метода обработки rest запроса
     * @return bool
     */
    public function isDefaultMethod(): bool;

    /**
     * Получение стандартного пространства имен
     * @return string
     */
    public function getDefaultNamespace(): string;

    /**
     * Проверка на существование обработчика rest запроса
     * @return bool
     */
    public function isMethodExits(): bool;

    /**
     * Вызов метода обработчика rest запроса с его валидацией
     * @return Result
     */
    public function call(): Result;

    /**
     * Получение названия метода обработки rest запроса
     * @return string
     */
    public function getMethodAction(): string;

    /**
     * Получение пространства имен обработчика rest запроса
     * @return string
     */
    public function getNamespaceActionName(): string;

    /**
     * Получение обработчика rest запроса
     * @return ActionBase|null
     */
    public function getAction(): ?ActionBase;

    /**
     * Получение строки маршрутизации rest запроса
     * @return string
     */
    public function getActionValue(): string;

    /**
     * @todo Потом переделать механизм проверки, чтобы она проходила в классу security
     * Указание названия функции, которая проверяет доступа к обработчику
     * @param string $method
     * @return bool
     */
    public function setMethodNameCheckAccess(string $method): bool;
}