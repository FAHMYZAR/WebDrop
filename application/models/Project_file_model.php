<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Project_file_model extends CI_Model
{
    protected $table = 'project_files';

    public function deleteByProject($projectId)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->delete($this->table);
    }

    public function insertBatch(array $rows)
    {
        if (empty($rows)) {
            return true;
        }

        return $this->db->insert_batch($this->table, $rows);
    }

    public function findByProject($projectId)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->order_by('is_folder', 'DESC')
            ->order_by('relative_path', 'ASC')
            ->get($this->table)
            ->result();
    }

    public function findByProjectAndPath($projectId, $relativePath)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->where('relative_path', $relativePath)
            ->get($this->table)
            ->row();
    }
}
