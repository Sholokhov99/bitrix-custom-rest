<?php

namespace DS\Rest\Source;

use DS\Rest\Http;

class Rest
{
    protected Http\Routs $route;

    protected Http\Request $request;

    public function __construct()
    {
        $request = new Http\Request();
        $this->route = new Http\Routs($request);

        $this->run();
    }

    public function run(): bool
    {
        $result = $this->route->call();

        /**
         * @todo Добавить логику
         */

        return $result->isSuccess();
    }
}