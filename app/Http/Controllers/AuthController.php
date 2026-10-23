<?php

namespace App\Http\Controllers;

/** Login screen MOCKUP. There is no authentication in Phase 1. */
class AuthController extends Controller
{
    public function login()
    {
        return view('pages.auth.login', ['roles' => config('portal.roles')]);
    }
}
