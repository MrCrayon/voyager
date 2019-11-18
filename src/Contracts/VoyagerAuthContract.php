<?php

namespace TCG\Voyager\Contracts;

use Illuminate\Http\Request;

interface VoyagerAuthContract 
{
    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm();

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request);

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)

    /**
     * Where to redirect user after authetication
     *
     * @return string
     */
    public function redirectTo()
}

