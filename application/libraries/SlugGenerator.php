<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SlugGenerator
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Project_model');
    }

    public function uniqueForUser($userId, $projectName)
    {
        $base = webdrop_slug($projectName);
        $slug = $base;
        $counter = 2;

        while ($this->CI->Project_model->slugExists($userId, $slug)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
