# DBExport Plugin for Kimai

A Kimai plugin that allows administrators to export the MySQL database as a downloadable SQL dump file.

## Features

- **Admin-Only Access**: Restricted to users with `system_configuration` permission (typically ROLE_SUPER_ADMIN or ROLE_ADMIN)
- **One-Click Export**: Simple UI with a button to trigger database export
- **Automatic Cleanup**: Temporary files are automatically deleted after download
- **Timestamped Filenames**: Downloads are named with database name and timestamp (e.g., `kimai_backup_kimai_2026-02-10_143025.sql`)
- **Secure**: Uses Kimai's built-in security system with Symfony's `#[IsGranted]` attributes

## Installation

The plugin is automatically installed when you build the Docker container:

```bash
docker compose up --build
```

## Usage

1. Log in as an administrator (user with `system_configuration` permission)
2. Navigate to `/db-export` in your Kimai instance
3. Click the "Export Database Now" button
4. The database dump will be generated and downloaded to your computer

## Technical Details

### Components

- **DBExportController**: Handles the routes and user interface
  - `GET /db-export` - Displays the export page
  - `POST /db-export/download` - Generates and downloads the dump

- **DatabaseDumpService**: Manages the database dump process
  - Parses `DATABASE_URL` environment variable
  - Executes `mysqldump` command
  - Creates temporary files in `/tmp`

- **Security**: Uses Symfony security attributes
  - `#[IsGranted('IS_AUTHENTICATED_FULLY')]` - Requires authentication
  - `#[IsGranted('system_configuration')]` - Requires admin permission

### Requirements

- Kimai 2.0+
- MySQL database
- `mysqldump` command available in container (included in base Kimai image)

## Development

The plugin follows Kimai's plugin conventions:

- Namespace: `KimaiPlugin\DBExportBundle`
- PSR-4 autoloading
- Symfony dependency injection
- Twig templating extends `base.html.twig`

## Security Notes

- Only administrators can access this feature
- Database credentials are read from environment variables (never hardcoded)
- Temporary files are stored in `/tmp` and automatically cleaned up after download
- Uses `--single-transaction` for consistent dumps without locking tables
