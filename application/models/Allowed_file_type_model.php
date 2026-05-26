<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Allowed_file_type_model extends CI_Model
{
    protected $table = 'allowed_file_types';

    public function findActiveByExtension($extension)
    {
        return $this->db
            ->where('extension', strtolower($extension))
            ->where('is_active', 1)
            ->get($this->table)
            ->row();
    }
}
