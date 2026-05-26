<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
    }

    public function index()
    {
        if ($this->input->method() === 'post') {
            try {
                $email = strtolower(trim((string) $this->input->post('email', true)));

                if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Email tidak valid.');
                }

                $existsByEmail = $this->User_model->findByEmail($email);

                if ($existsByEmail && (int) $existsByEmail->id_user !== (int) $this->user()->id_user) {
                    throw new RuntimeException('Email sudah dipakai.');
                }

                $this->User_model->updateProfile($this->user()->id_user, array(
                    'email' => $email,
                ));
                $this->session->set_flashdata('success', 'Profil berhasil diperbarui.');
                redirect('profile');
            } catch (Throwable $exception) {
                $this->session->set_flashdata('error', $exception->getMessage());
                redirect('profile');
            }
        }

        $this->render('profile/index', array(
            'page_title' => 'Profile',
            'profile_user' => $this->User_model->findActiveById($this->user()->id_user),
            'layout_variant' => 'app',
            'body_class' => 'app-body',
        ));
    }

    public function password()
    {
        if ($this->input->method() !== 'post') {
            redirect('profile');
        }

        try {
            $currentPassword = (string) $this->input->post('current_password');
            $newPassword = (string) $this->input->post('new_password');
            $confirmPassword = (string) $this->input->post('confirm_password');
            $user = $this->User_model->findActiveById($this->user()->id_user);

            if ( ! password_verify($currentPassword, $user->password)) {
                throw new RuntimeException('Password lama salah.');
            }

            if (strlen($newPassword) < 6) {
                throw new RuntimeException('Password baru minimal 6 karakter.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('Konfirmasi password tidak sama.');
            }

            $this->User_model->updatePassword($user->id_user, password_hash($newPassword, PASSWORD_DEFAULT));
            $this->session->set_flashdata('success', 'Password berhasil diganti.');
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
        }

        redirect('profile');
    }
}
