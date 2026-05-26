<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $currentUser;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->currentUser = $this->resolveCurrentUser();
    }

    protected function resolveCurrentUser()
    {
        $userId = (int) $this->session->userdata('id_user');

        if ($userId < 1) {
            return null;
        }

        return $this->User_model->findActiveById($userId);
    }

    public function user()
    {
        return $this->currentUser;
    }

    protected function guestOnly()
    {
        if ($this->user()) {
            redirect('dashboard');
        }
    }

    protected function requireAuth()
    {
        if ($this->user()) {
            return;
        }

        $this->session->set_flashdata('error', 'Silakan login dulu.');
        redirect('login');
    }

    protected function render($view, array $data = array())
    {
        $data['auth_user'] = $this->user();
        $data['flash_error'] = $this->session->flashdata('error');
        $data['flash_success'] = $this->session->flashdata('success');
        $data['content_view'] = $view;
        $this->load->view('layouts/app', $data);
    }

    protected function backTo($fallback)
    {
        $target = $this->input->server('HTTP_REFERER');
        redirect($target ?: $fallback);
    }
}

class Public_Controller extends MY_Controller
{
}

class Auth_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }
}

class App_Controller extends Auth_Controller
{
}
