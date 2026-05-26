# WebDrop

WebDrop is a CodeIgniter 3 based web project manager for building, editing, previewing, and publishing static websites.

## Overview

WebDrop combines a dashboard and an in-browser editor so you can manage website files, edit content with Monaco Editor, preview the result, and publish the generated site.

## Stack

- CodeIgniter 3
- PHP
- MySQL
- Tailwind CSS
- Vanilla JavaScript
- Monaco Editor

## Features

- Dashboard for project management
- Static project creation and deletion
- File tree, file editing, file creation, folder creation, rename, delete, and upload
- Live preview panel inside the editor
- Publish workflow for generated static sites
- User profile and authentication flows

## Requirements

- PHP 5.3.7 or newer
- MySQL
- Web server such as Apache or Nginx
- Composer for dependency management if needed by your environment

## Local Setup

1. Clone or open the repository in your local workspace.
2. Configure your web server document root to the project root.
3. Update application configuration in `application/config/config.php` and database settings in `application/config/database.php`.
4. If your environment uses a local `.env`, update it with your database and app settings.
5. Make sure writable folders are available for runtime data and uploads.
6. Open the app in your browser and sign in.

## Important Folders

- `application/controllers` - app controllers
- `application/libraries` - file, project, path, and publish services
- `application/models` - database models
- `application/views` - dashboard, editor, auth, and layout views
- `assets/css` - shared styling
- `assets/js` - dashboard and editor behavior
- `storage` - runtime files such as logs, uploads, published output, and workspace data
- `sites` - generated/public site output

## Notes

- `application/cache`, `application/logs`, and runtime storage folders are ignored from version control.
- The editor UI is designed to stay full-height and avoid body scrolling.
- Preview rendering uses the project preview endpoint, not raw file content.

## License

This project is built on CodeIgniter 3. See the original project license terms where applicable.
