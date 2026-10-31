<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . BASE . '/');
            exit;
        }
        Response::view('auth/login', [], false);
    }

    public function apiLogin(): void
    {
        $input = Request::json();
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Correo y contraseña son requeridos'], 422);
            return;
        }

        $user = Auth::login($email, $password);
        if (!$user) {
            Response::json(['error' => 'Credenciales inválidas'], 401);
            return;
        }

        Response::json(['user' => $user]);
    }

    /** Login de la app móvil: devuelve un token Bearer en vez de abrir sesión. */
    public function apiToken(): void
    {
        $input = Request::json();
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Correo y contraseña son requeridos'], 422);
            return;
        }

        $user = Auth::verificar($email, $password);
        if (!$user) {
            Response::json(['error' => 'Credenciales inválidas'], 401);
            return;
        }

        $token = Auth::crearToken((int) $user['id'], Request::str($input['dispositivo'] ?? null));
        Response::json(['token' => $token, 'user' => $user], 201);
    }

    public function apiRevocarToken(): void
    {
        $token = Auth::bearerToken();
        if ($token !== null) {
            Auth::revocarToken($token);
        }
        Response::json(['ok' => true]);
    }

    public function apiYo(): void
    {
        Auth::requireLogin();
        Response::json(['user' => Auth::user()]);
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . BASE . '/login');
        exit;
    }
}
