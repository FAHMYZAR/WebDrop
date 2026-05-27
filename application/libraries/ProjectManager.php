<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProjectManager
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Project_model', 'File_model', 'Activity_log_model'));
        $this->CI->load->library(array('SlugGenerator', 'PathResolver', 'FileValidator', 'FileManagerService'));
    }

    public function dashboardProjects($userId)
    {
        return $this->CI->Project_model->findDashboardByUser($userId);
    }

    public function createProject($user, $projectName)
    {
        $projectName = $this->CI->filevalidator->assertProjectName($projectName);
        $slug = $this->CI->sluggenerator->uniqueForUser($user->id_user, $projectName);
        $workspacePath = $this->CI->pathresolver->workspaceDirectory($user->username, $slug);

        if ( ! is_dir($workspacePath) && ! mkdir($workspacePath, DIR_WRITE_MODE, true) && ! is_dir($workspacePath)) {
            throw new RuntimeException('Gagal membuat workspace project.');
        }

        $project = $this->CI->Project_model->createProject($user->id_user, $projectName, $slug, $workspacePath);

        if ( ! $project) {
            throw new RuntimeException('Gagal membuat project.');
        }

        file_put_contents($workspacePath . 'index.html', $this->defaultHtml($projectName));
        file_put_contents($workspacePath . 'style.css', $this->defaultCss());
        file_put_contents($workspacePath . 'script.js', $this->defaultJs());

        $this->CI->filemanagerservice->syncProjectFiles($project);
        $this->CI->Activity_log_model->create($user->id_user, 'create_project', 'Project dibuat: ' . $projectName, $project->id_project);

        return $this->CI->Project_model->findById($project->id_project);
    }

    public function deleteProject($project, $user)
    {
        $workspacePath = rtrim($project->workspace_path, DIRECTORY_SEPARATOR);
        $publishedPath = rtrim((string) $project->published_path, DIRECTORY_SEPARATOR);

        try {
            try {
                $this->CI->Activity_log_model->create($user->id_user, 'delete_project', 'Project dihapus: ' . $project->project_name, $project->id_project);
            } catch (Throwable $exception) {
                log_message('error', 'Delete project log failed: ' . $exception->getMessage());
            }

            if ($workspacePath !== '' && is_dir($workspacePath)) {
                $this->deleteDirectory($workspacePath);
            }

            if ($publishedPath !== '' && is_dir($publishedPath)) {
                $this->deleteDirectory($publishedPath);
            }

            try {
                $this->CI->File_model->deleteByProject($project->id_project);
            } catch (Throwable $exception) {
                log_message('error', 'Delete project files failed: ' . $exception->getMessage());
            }

            $this->CI->Project_model->deleteByUserAndId($user->id_user, $project->id_project);
        } catch (Throwable $exception) {
            log_message('error', 'Delete project failed: ' . $exception->getMessage());
            throw $exception;
        }
    }

    public function unpublishProject($project)
    {
        $publishedPath = rtrim((string) $project->published_path, DIRECTORY_SEPARATOR);

        if ($publishedPath !== '' && is_dir($publishedPath)) {
            $this->deleteDirectory($publishedPath);
        }

        return $this->CI->Project_model->update($project->id_project, array(
            'status' => 'draft',
            'public_url' => null,
            'published_path' => null,
        ));
    }

    protected function defaultHtml($projectName)
    {
        return "<!doctype html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"utf-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n    <title>" . html_escape($projectName) . "</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <main class=\"page\">\n        <h1>" . html_escape($projectName) . "</h1>\n        <p>WebDrop starter project ready.</p>\n    </main>\n    <script src=\"script.js\"></script>\n</body>\n</html>\n";
    }

    protected function defaultCss()
    {
        return "body {\n    margin: 0;\n    font-family: Arial, sans-serif;\n    background: #f6f7fb;\n    color: #182033;\n}\n\n.page {\n    max-width: 720px;\n    margin: 96px auto;\n    padding: 24px;\n}\n";
    }

    protected function defaultJs()
    {
        return "console.log('WebDrop ready');\n";
    }

    protected function deleteDirectory($directory)
    {
        if ( ! is_dir($directory)) {
            return;
        }

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
