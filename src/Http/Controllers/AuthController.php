<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use \TCG\Voyager\Contracts\VoyagerAuthContract;

class AuthController extends Controller
{
    protected $auth;

    public function __construct(VoyagerAuthContract $auth) {
        $this->auth = $auth;

        // $this->middleware('voyager.guest')->except('logout');
    }

    public function __call($name, $arguments)
    {
        if (count($arguments) > 0) {
            return $this->auth->{$name}(Request $arguments[0]);
        }

        return $this->auth->{$name}();
    }
}

