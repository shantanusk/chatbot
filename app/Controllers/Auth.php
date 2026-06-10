<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session('user_id')) {
            return redirect()->to('/chatbot');
        }

        if ($this->request->getMethod() === 'POST') {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            $model = new UserModel();
            $user = $model->where('username', $username)->orWhere('email', $username)->first();

            if ($user && password_verify($password, $user->password)) {
                session()->set([
                    'user_id'  => $user->id,
                    'username' => $user->username,
                ]);
                return redirect()->to('/chatbot');
            }

            return view('auth/login', ['error' => 'Invalid username or password']);
        }

        return view('auth/login');
    }

    public function register()
    {
        if (session('user_id')) {
            return redirect()->to('/chatbot');
        }

        if ($this->request->getMethod() === 'POST') {
            $username = trim($this->request->getPost('username'));
            $email    = trim($this->request->getPost('email'));
            $password = $this->request->getPost('password');

            $model = new UserModel();

            if ($model->where('username', $username)->first()) {
                return view('auth/register', ['error' => 'Username already taken']);
            }
            if ($model->where('email', $email)->first()) {
                return view('auth/register', ['error' => 'Email already registered']);
            }
            if (strlen($password) < 4) {
                return view('auth/register', ['error' => 'Password must be at least 4 characters']);
            }

            $model->save([
                'username' => $username,
                'email'    => $email,
                'password' => $password,
            ]);

            $user = $model->where('username', $username)->first();
            session()->set([
                'user_id'  => $user->id,
                'username' => $user->username,
            ]);

            return redirect()->to('/chatbot');
        }

        return view('auth/register');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login');
    }
}
