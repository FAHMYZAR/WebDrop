<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('ProjectManager');
    }

    public function index()
    {
        $this->render('dashboard/index', array(
            'page_title' => 'Dashboard',
            'projects' => $this->projectmanager->dashboardProjects($this->user()->id_user),
            'layout_variant' => 'app',
            'body_class' => 'app-body',
        ));
    }
}
