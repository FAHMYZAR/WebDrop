<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Publish extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Project_model');
        $this->load->library('StaticPublisher');
    }

    public function run($projectId)
    {
        $project = $this->Project_model->findByUserAndId($this->user()->id_user, $projectId);

        if ( ! $project) {
            show_404();
        }

        try {
            $publicUrl = $this->staticpublisher->publish($project, $this->user());
            $this->session->set_flashdata('success', 'Publish berhasil: ' . $publicUrl);
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
        }

        redirect('editor/' . $project->id_project);
    }
}
