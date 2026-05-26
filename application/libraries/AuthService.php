<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('User_model', 'Activity_log_model'));
    }

    public function register($email, $username, $password)
    {
        $email = strtolower(trim($email));
        $username = trim($username);

        if ($this->CI->User_model->findByEmail($email)) {
            throw new RuntimeException('Email sudah dipakai.');
        }

        if ($this->CI->User_model->findByUsername($username)) {
            throw new RuntimeException('Username sudah dipakai.');
        }

        $user = $this->CI->User_model->create(array(
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ));

        $this->CI->Activity_log_model->create($user->id_user, 'register', 'User baru terdaftar.');

        return $user;
    }

    public function attempt($username, $password)
    {
        $user = $this->CI->User_model->findByUsername(trim($username));

        if ( ! $user || (int) $user->is_active !== 1 || ! password_verify($password, $user->password)) {
            throw new RuntimeException('Username atau password salah.');
        }

        $this->CI->session->set_userdata(array(
            'id_user' => (int) $user->id_user,
            'username' => $user->username,
        ));

        $this->CI->Activity_log_model->create($user->id_user, 'login', 'User login.');

        return $user;
    }

    public function logout($userId = null)
    {
        if ($userId) {
            $this->CI->Activity_log_model->create($userId, 'logout', 'User logout.');
        }

        $this->CI->session->sess_destroy();
    }
}
