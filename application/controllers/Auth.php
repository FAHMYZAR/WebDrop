<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('AuthService');
        $this->load->helper('form');
    }

    public function index()
    {
        if ($this->user()) {
            redirect('dashboard');
        }

        redirect('login');
    }

    public function login()
    {
        $this->guestOnly();

        if ($this->input->method() === 'post') {
            try {
                $this->authservice->attempt(
                    $this->input->post('username', true),
                    (string) $this->input->post('password')
                );

                $this->session->set_flashdata('success', 'Login berhasil.');
                redirect('dashboard');
            } catch (Throwable $exception) {
                $this->session->set_flashdata('error', $exception->getMessage());
                redirect('login');
            }
        }

        $this->render('auth/login', array(
            'page_title' => 'Login',
            'layout_variant' => 'auth',
            'body_class' => 'auth-body',
        ));
    }

    public function register()
    {
        $this->guestOnly();

        if ($this->input->method() === 'post') {
            $password = (string) $this->input->post('password');
            $passwordConfirm = (string) $this->input->post('password_confirm');

            try {
                $this->validateRegister($password, $passwordConfirm);
                $this->authservice->register(
                    $this->input->post('email', true),
                    $this->input->post('username', true),
                    $password
                );

                $this->session->set_flashdata('success', 'Register berhasil. Silakan login.');
                redirect('login');
            } catch (Throwable $exception) {
                $this->session->set_flashdata('error', $exception->getMessage());
                redirect('register');
            }
        }

        $this->render('auth/register', array(
            'page_title' => 'Register',
            'layout_variant' => 'auth',
            'body_class' => 'auth-body',
        ));
    }

    public function logout()
    {
        $user = $this->user();
        $this->authservice->logout($user ? $user->id_user : null);
        redirect('login');
    }

    protected function validateRegister($password, $passwordConfirm)
    {
        $email = trim((string) $this->input->post('email', true));
        $username = trim((string) $this->input->post('username', true));

        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email tidak valid.');
        }

        if ( ! preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            throw new RuntimeException('Username hanya boleh huruf, angka, underscore, minimal 3 karakter.');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('Password minimal 6 karakter.');
        }

        if ($password !== $passwordConfirm) {
            throw new RuntimeException('Konfirmasi password tidak sama.');
        }
    }
}
