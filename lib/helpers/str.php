<?php

namespace DS\Rest\Heplers;

use Bitrix\Main\Localization\Loc;
use DS\Rest\Result;

class Str
{
    /**
     * Валидация строки на соответствие синтаксису json
     * @param string $json
     * @param int $params
     * @return Result
     */
    public static function jsonValidate(string $json, int $params = 0): Result
    {
        $result = new Result();
        $error = '';

        if ($json <> '') {
            $decode = json_decode($json, true, $params);

            if (is_null($decode)) {
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        break;
                    case JSON_ERROR_DEPTH:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_DEPTH');
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_MISMATCH');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_CHAR');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_SYNTAX');
                        break;
                    case JSON_ERROR_UTF8:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_UTF8');
                        break;
                    case JSON_ERROR_RECURSION:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_RECURSION');
                        break;
                    case JSON_ERROR_INF_OR_NAN:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_NAN');
                        break;
                    case JSON_ERROR_UNSUPPORTED_TYPE:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_TYPE');
                        break;
                    default:
                        $error = Loc::getMessage('DS_REST_HELPER_JSON_ERROR_OCCURED');
                        break;
                }

                if ($error <> '') {
                    $result->setError($error);
                }
            }

            if (is_array($decode)) {
                $result = $decode;
            }
        }

        return $result;
    }
}