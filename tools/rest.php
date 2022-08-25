<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use DS\Rest\Source;
use Bitrix\Main\Loader;

function showRestError(array $data)
{
    die(json_encode(array(
        "success" => false,
        "data" => $data
    )));
}

if (Loader::includeModule('ds.rest') === false) {
    header('HTTP/1.0 403 Forbidden');
    showRestError(['error load module']);
}

$rest = new Source\Rest();