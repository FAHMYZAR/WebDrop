<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    protected $table = 'users';

    public function findActiveById($id)
    {
        return $this->db
            ->where('id_user', (int) $id)
            ->where('is_active', 1)
            ->get($this->table)
            ->row();
    }

    public function findByUsername($username)
    {
        return $this->db
            ->where('username', $username)
            ->get($this->table)
            ->row();
    }

    public function findByEmail($email)
    {
        return $this->db
            ->where('email', $email)
            ->get($this->table)
            ->row();
    }

    public function create(array $data)
    {
        $payload = array(
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => $data['password'],
        );

        $this->db->insert($this->table, $payload);

        return $this->findActiveById($this->db->insert_id());
    }

    public function updateProfile($id, array $data)
    {
        return $this->db
            ->where('id_user', (int) $id)
            ->update($this->table, array(
                'email' => $data['email'],
            ));
    }

    public function updatePassword($id, $passwordHash)
    {
        return $this->db
            ->where('id_user', (int) $id)
            ->update($this->table, array('password' => $passwordHash));
    }
}
