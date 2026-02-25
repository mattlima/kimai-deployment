<?php

namespace KimaiPlugin\DBExportBundle\Service;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DatabaseDumpService
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        // Parse DATABASE_URL environment variable
        // Format: mysql://username:password@host/database?charset=utf8mb4&serverVersion=8.3.0
        $databaseUrl = $_ENV['DATABASE_URL'] ?? '';
        
        if (preg_match('/mysql:\/\/([^:]+):([^@]+)@([^\/]+)\/([^?]+)/', $databaseUrl, $matches)) {
            $this->username = $matches[1];
            $this->password = $matches[2];
            $this->host = $matches[3];
            $this->database = $matches[4];
        } else {
            throw new \RuntimeException('Unable to parse DATABASE_URL environment variable');
        }
    }

    /**
     * Creates a database dump and returns the temporary file path.
     *
     * @return string Path to the temporary dump file
     * @throws \RuntimeException if the dump fails
     */
    public function createDump(): string
    {
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'kimai_db_dump_') . '.sql';
        
        // Build mysqldump command
        $command = [
            'mysqldump',
            '--host=' . $this->host,
            '--user=' . $this->username,
            '--password=' . $this->password,
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            $this->database,
        ];

        // Execute mysqldump
        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes timeout
        
        try {
            $process->mustRun();
            
            // Write output to file
            file_put_contents($tempFile, $process->getOutput());
            
            return $tempFile;
        } catch (ProcessFailedException $exception) {
            // Clean up temp file if it was created
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            throw new \RuntimeException(
                'Database dump failed: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    /**
     * Gets a filename for the export with timestamp.
     */
    public function getExportFilename(): string
    {
        return sprintf(
            'kimai_backup_%s_%s.sql',
            $this->database,
            date('Y-m-d_His')
        );
    }
}
