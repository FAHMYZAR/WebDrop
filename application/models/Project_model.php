<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Project_model extends CI_Model
{
    protected $table = 'projects';

    public function create(array $data)
    {
        $this->db->insert($this->table, $data);

        return $this->findById($this->db->insert_id());
    }

    public function update($projectId, array $data)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->update($this->table, $data);
    }

    public function createProject($userId, $projectName, $slug, $workspacePath)
    {
        return $this->create(array(
            'id_user' => (int) $userId,
            'project_name' => $projectName,
            'slug' => $slug,
            'workspace_path' => $workspacePath,
            'status' => 'draft',
        ));
    }

    public function findById($projectId)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->get($this->table)
            ->row();
    }

    public function findByUserAndId($userId, $projectId)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->where('id_user', (int) $userId)
            ->get($this->table)
            ->row();
    }

    public function findByUserAndSlug($userId, $slug)
    {
        return $this->db
            ->where('id_user', (int) $userId)
            ->where('slug', $slug)
            ->get($this->table)
            ->row();
    }

    public function findDashboardByUser($userId)
    {
        if ($this->db->table_exists('v_project_dashboard')) {
            return $this->db
                ->where('id_user', (int) $userId)
                ->order_by('updated_at', 'DESC')
                ->get('v_project_dashboard')
                ->result();
        }

        return $this->db
            ->select('p.*, COUNT(f.id_file) AS total_items, SUM(CASE WHEN f.is_folder = 0 THEN 1 ELSE 0 END) AS total_files, SUM(CASE WHEN f.is_folder = 1 THEN 1 ELSE 0 END) AS total_folders, COALESCE(SUM(f.file_size), 0) AS total_size_bytes', false)
            ->from('projects p')
            ->join('project_files f', 'f.id_project = p.id_project', 'left')
            ->where('p.id_user', (int) $userId)
            ->group_by('p.id_project')
            ->order_by('p.updated_at', 'DESC')
            ->get()
            ->result();
    }

    public function slugExists($userId, $slug)
    {
        return $this->db
            ->where('id_user', (int) $userId)
            ->where('slug', $slug)
            ->count_all_results($this->table) > 0;
    }

    public function markPublished($projectId, $userId, $publishedPath, $publicUrl)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->where('id_user', (int) $userId)
            ->update($this->table, array(
                'status' => 'published',
                'published_path' => $publishedPath,
                'public_url' => $publicUrl,
                'last_published_at' => date('Y-m-d H:i:s'),
            ));
    }

    public function deleteByUserAndId($userId, $projectId)
    {
        return $this->db
            ->where('id_project', (int) $projectId)
            ->where('id_user', (int) $userId)
            ->delete($this->table);
    }

}
