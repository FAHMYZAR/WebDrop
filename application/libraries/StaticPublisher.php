<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StaticPublisher
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Project_model', 'Activity_log_model', 'Publish_job_model'));
        $this->CI->load->library(array('PathResolver', 'FileManagerService'));
    }

    public function publish($project, $user)
    {
        $jobId = $this->CI->Publish_job_model->create($project->id_project, $user->id_user);

        try {
            $indexPath = rtrim($project->workspace_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';

            if ( ! is_file($indexPath)) {
                throw new RuntimeException('Project belum dapat dipublish karena file index.html tidak ditemukan.');
            }

            $this->assertNoForbiddenFiles($project->workspace_path);

            $siteDirectory = $this->CI->pathresolver->siteDirectory($user->username, $project->slug);
            $this->replaceDirectory($project->workspace_path, $siteDirectory);

            $publicUrl = $this->CI->pathresolver->publicUrl($user->username, $project->slug);
            $this->CI->Project_model->markPublished($project->id_project, $user->id_user, $siteDirectory, $publicUrl);
            $this->CI->Activity_log_model->create($user->id_user, 'publish_project', 'Project dipublish ke ' . $publicUrl, $project->id_project);
            $this->CI->Publish_job_model->finish($jobId, 'success', 'Publish berhasil.');

            return $publicUrl;
        } catch (Throwable $exception) {
            $this->CI->Publish_job_model->finish($jobId, 'failed', $exception->getMessage());
            throw $exception;
        }
    }

    protected function assertNoForbiddenFiles($directory)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $filename = $item->getFilename();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($filename === '.htaccess' || in_array($extension, array('php', 'phtml', 'phar', 'sh', 'py', 'rb', 'pl', 'cgi', 'env', 'sql', 'bat', 'cmd', 'exe', 'jar', 'war', 'dll', 'so', 'bin'), true)) {
                throw new RuntimeException('Project mengandung file berbahaya dan tidak bisa dipublish.');
            }
        }
    }

    protected function replaceDirectory($sourceDirectory, $targetDirectory)
    {
        if (is_dir($targetDirectory)) {
            $this->deleteDirectory($targetDirectory);
        }

        if ( ! mkdir($targetDirectory, DIR_WRITE_MODE, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('Gagal membuat folder publish.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace(rtrim($sourceDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $targetPath = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if ( ! is_dir($targetPath) && ! mkdir($targetPath, DIR_WRITE_MODE, true) && ! is_dir($targetPath)) {
                    throw new RuntimeException('Gagal membuat subfolder publish.');
                }
                continue;
            }

            copy($item->getPathname(), $targetPath);
        }
    }

    protected function deleteDirectory($directory)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
