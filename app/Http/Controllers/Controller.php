<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function jsonResponse($data, $errorCode=422)
    {
        return response()->json($data, $errorCode);
    }
}
