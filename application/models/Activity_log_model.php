<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity_log_model extends CI_Model
{
    protected $table = 'activity_logs';

    public function create($userId, $action, $description = null, $projectId = null)
    {
        return $this->db->insert($this->table, array(
            'id_user' => (int) $userId,
            'id_project' => $projectId ? (int) $projectId : null,
            'action' => $action,
            'description' => $description,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
        ));
    }
}
