<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Publish_job_model extends CI_Model
{
    protected $table = 'publish_jobs';

    public function create($projectId, $userId)
    {
        $this->db->insert($this->table, array(
            'id_project' => (int) $projectId,
            'id_user' => (int) $userId,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ));

        return $this->db->insert_id();
    }

    public function finish($jobId, $status, $message = null)
    {
        return $this->db
            ->where('id_publish_job', (int) $jobId)
            ->update($this->table, array(
                'status' => $status,
                'message' => $message,
                'finished_at' => date('Y-m-d H:i:s'),
            ));
    }
}
