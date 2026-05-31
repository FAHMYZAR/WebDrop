<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('ProjectManager');
        $this->load->model('File_model');
    }

    public function index()
    {
        $this->render('dashboard/index', array(
            'page_title' => 'Dashboard',
            'projects' => $this->projectmanager->dashboardProjects($this->user()->id_user),
            'account_usage_bytes' => $this->File_model->totalSizeByUser($this->user()->id_user),
            'layout_variant' => 'app',
            'body_class' => 'app-body',
        ));
    }
}
