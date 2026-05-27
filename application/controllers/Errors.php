<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Errors extends CI_Controller
{
    public function page_missing()
    {
        $file = FCPATH . 'sites' . DIRECTORY_SEPARATOR . '404.html';

        if (is_file($file)) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('text/html', 'UTF-8')
                ->set_output(file_get_contents($file));

            return;
        }

        $this->output
            ->set_status_header(404)
            ->set_content_type('text/html', 'UTF-8')
            ->set_output('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>WebDrop - 404</title></head><body><h1>OOPS, halaman tidak ditemukan</h1><p>Halaman yang kamu cari tidak tersedia atau sudah dipindah.</p></body></html>');
    }
}
