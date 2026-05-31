<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class File_model extends CI_Model
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

    public function totalSizeByProject($projectId)
    {
        $row = $this->db
            ->select('COALESCE(SUM(file_size), 0) AS total_size', false)
            ->where('id_project', (int) $projectId)
            ->where('is_folder', 0)
            ->get($this->table)
            ->row();

        return $row ? (int) $row->total_size : 0;
    }

    public function totalSizeByUser($userId)
    {
        $row = $this->db
            ->select('COALESCE(SUM(f.file_size), 0) AS total_size', false)
            ->from($this->table . ' f')
            ->join('projects p', 'p.id_project = f.id_project', 'inner')
            ->where('p.id_user', (int) $userId)
            ->where('f.is_folder', 0)
            ->get()
            ->row();

        return $row ? (int) $row->total_size : 0;
    }
}
