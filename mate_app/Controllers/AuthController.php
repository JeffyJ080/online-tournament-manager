<?php

require_once __DIR__ . '/../Core/Controller.php';

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title' => 'Register',
            'heading' => 'Create your Mate Tournaments account',
        ]);
    }

    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Login',
            'heading' => 'Login to Mate Tournaments',
        ]);
    }
}