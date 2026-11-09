<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SwooleTestController extends Controller
{
    public function index(Request $request)
    {

        dd();

        return response()->json([
            'get' => $request->all(),
            'server' => 'laravel-s swoole http',
        ]);
    }

}
