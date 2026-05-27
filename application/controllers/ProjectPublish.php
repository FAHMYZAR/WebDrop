<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProjectPublish extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Project_model');
        $this->load->library(array('StaticPublisher', 'ProjectManager'));
    }

    public function publish($projectId)
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $projectId);
            $publicUrl = $this->staticpublisher->publish($project, $this->user());
            $updatedProject = $this->Project_model->findById($project->id_project);

            $this->jsonResponse(true, 'Project published successfully!', array(
                'public_url' => $publicUrl,
                'project' => $this->projectPayload($updatedProject),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function savedraft()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $this->projectmanager->unpublishProject($project);
            $updatedProject = $this->Project_model->findById($project->id_project);

            $this->jsonResponse(true, 'Project saved as draft.', array(
                'project' => $this->projectPayload($updatedProject),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }
}
