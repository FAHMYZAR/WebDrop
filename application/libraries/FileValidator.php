<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FileValidator
{
    protected $editableExtensions = array('html', 'css', 'js');
    protected $assetExtensions = array('json', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'zip');
    protected $blockedExtensions = array('php', 'phtml', 'phar', 'exe', 'sh', 'py', 'rb', 'pl', 'cgi', 'htaccess', 'env', 'sql', 'bat', 'cmd', 'jar', 'war', 'dll', 'so', 'bin');
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Allowed_file_type_model', 'File_model'));
    }

    public function assertQuota($project, $additionalBytes = 0)
    {
        $additionalBytes = (int) $additionalBytes;
        $projectQuotaLimitBytes = 50 * 1024 * 1024;
        $userQuotaLimitBytes = 100 * 1024 * 1024;

        $currentProjectSize = $this->CI->File_model->totalSizeByProject($project->id_project);
        
        if (($currentProjectSize + $additionalBytes) > $projectQuotaLimitBytes) {
            throw new RuntimeException('Quota project (50MB) penuh.');
        }

        $currentUserSize = $this->CI->File_model->totalSizeByUser($project->id_user);

        if (($currentUserSize + $additionalBytes) > $userQuotaLimitBytes) {
            throw new RuntimeException('Quota storage akun (100MB) penuh.');
        }

        return true;
    }

    public function assertProjectName($name)
    {
        $name = trim((string) $name);

        if ($name === '' || mb_strlen($name) > 120) {
            throw new RuntimeException('Nama project wajib diisi dan maksimal 120 karakter.');
        }

        return $name;
    }

    public function assertFileName($name)
    {
        $name = trim((string) $name);

        if ($name === '' || preg_match('/[<>:"|?*\\\\\/]/', $name)) {
            throw new RuntimeException('Nama file atau folder tidak valid.');
        }

        return $name;
    }

    public function extension($relativePath)
    {
        return strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    }

    public function isEditableExtension($extension)
    {
        return in_array(strtolower($extension), $this->editableExtensions, true);
    }

    public function isAssetExtension($extension)
    {
        return in_array(strtolower($extension), $this->assetExtensions, true);
    }

    public function isBlockedExtension($extension)
    {
        return in_array(strtolower($extension), $this->blockedExtensions, true);
    }

    public function isEditable($relativePath)
    {
        return $this->isEditableExtension($this->extension($relativePath));
    }

    public function assertEditable($relativePath)
    {
        if ( ! $this->isEditable($relativePath)) {
            throw new RuntimeException('File ini tidak bisa diedit.');
        }
    }

    public function assertManagedExtension($relativePath)
    {
        $extension = $this->extension($relativePath);

        if ($this->isEditableExtension($extension) || $this->isAssetExtension($extension)) {
            return true;
        }

        if ($this->isBlockedExtension($extension)) {
            throw new RuntimeException('Ekstensi file tidak diizinkan.');
        }

        if ( ! $this->CI->Allowed_file_type_model->findActiveByExtension($extension)) {
            throw new RuntimeException('Ekstensi file tidak diizinkan.');
        }

        return true;
    }

    public function assertAllowedUpload($fileName, $sizeBytes)
    {
        $extension = $this->extension($fileName);

        if ($this->isBlockedExtension($extension)) {
            throw new RuntimeException('Tipe file tidak diizinkan.');
        }

        $allowed = $this->CI->Allowed_file_type_model->findActiveByExtension($extension);

        if ( ! $allowed) {
            throw new RuntimeException('Tipe file tidak diizinkan.');
        }

        $maxBytes = ((int) $allowed->max_size_mb) * 1024 * 1024;

        if ($sizeBytes > $maxBytes) {
            throw new RuntimeException('Ukuran file melebihi batas.');
        }

        return $allowed;
    }
}
