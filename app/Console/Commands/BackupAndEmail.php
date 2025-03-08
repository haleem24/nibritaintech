<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\GoogleController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use ZipArchive;

class BackupAndEmail extends Command
{
    protected $signature = 'backup:email';
    protected $description = 'Backup the database in chunks and email';

    private $maxChunkSize = 25 * 1024 * 1024; // 25MB in bytes

    public function handle()
    {
        try {
            // Get the current timestamp
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupDir = storage_path("app/backups/$timestamp");
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Get all table names
            $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

            // Backup each table
            foreach ($tables as $table) {
                $this->backupTableInChunks($table, $backupDir);
            }

            // Send email with all compressed backups
            $this->emailBackups($backupDir);

            // Clean up backup directory
            $this->cleanUp($backupDir);

            $this->info("Database backup created in chunks and emailed.");
        } catch (\Exception $e) {
            $this->error("Error occurred: " . $e->getMessage());
        }
    }

    private function backupTableInChunks($table, $backupDir)
    {
        // Get table row count
        $rowCount = DB::table($table)->count();
        $chunkSize = 10000; // Adjust the chunk size as necessary

        for ($offset = 0; $offset < $rowCount; $offset += $chunkSize) {
            $backupFile = "{$table}_chunk_{$offset}.sql";
            $backupPath = "$backupDir/$backupFile";

            $command = sprintf(
                'mysqldump --column-statistics=0 --user=%s --password=%s --host=%s --port=%s %s %s --where="1 LIMIT %d OFFSET %d" > %s 2>&1',
                escapeshellarg(env('DB_USERNAME')),
                escapeshellarg(env('DB_PASSWORD')),
                escapeshellarg(env('DB_HOST')),
                escapeshellarg(env('DB_PORT')),
                escapeshellarg(env('DB_DATABASE')),
                // escapeshellarg('/root/.my.cnf'), // Update to the correct path to your .my.cnf file
                // escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($table),
                $chunkSize,
                $offset,
                escapeshellarg($backupPath)
            );
            // Prepare the mysqldump command
            // $command = sprintf(
            //     'mysqldump --column-statistics=0 --user=%s --password=%s --host=%s --port=%s %s > %s',
            //     escapeshellarg(env('DB_USERNAME')),
            //     escapeshellarg(env('DB_PASSWORD')),
            //     escapeshellarg(env('DB_HOST')),
            //     escapeshellarg(env('DB_PORT')),
            //     escapeshellarg(env('DB_DATABASE')),
            //     $backupPath
            // );


            $output = shell_exec($command);
            // if ($output === null || $output !== '') {
            //     throw new \Exception("Backup for table $table chunk $offset failed. Error: $output");
            // }

            // Compress the backup file
            $zip = new ZipArchive();
            $zipPath = "$backupDir/{$table}_chunk_{$offset}.zip";
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                $zip->addFile($backupPath, $backupFile);
                $zip->close();
                unlink($backupPath); // Delete the uncompressed file

                // Check if the zip file is larger than the max size
                if (filesize($zipPath) > $this->maxChunkSize) {
                    throw new \Exception("Chunk size exceeds 25MB for table $table chunk $offset.");
                }
            } else {
                throw new \Exception("Could not create zip file for $table chunk $offset.");
            }
        }
    }

    private function emailBackups($backupDir)
    {
        $files = scandir($backupDir);
        $attachments = [];
        $currentEmailSize = 0;

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $filePath = "$backupDir/$file";
                $fileSize = filesize($filePath);

                // Check if adding this file exceeds the max email size
                if ($currentEmailSize + $fileSize > $this->maxChunkSize) {
                    // Send current attachments
                    $this->sendEmailWithAttachments($attachments);
                    $attachments = [];
                    $currentEmailSize = 0;
                }

                $attachments[] = $filePath;
                $currentEmailSize += $fileSize;
            }
        }

        // Send remaining attachments
        if (!empty($attachments)) {
            $this->sendEmailWithAttachments($attachments);
        }
    }

    private function sendEmailWithAttachments($attachments)
    {
        $recipientEmail = 'wethesd@gmail.com';
        $subject = 'Database Backup';
        $body = 'Here are the database backup files.';

        $email = app(GoogleController::class)->sendEmail($recipientEmail, $subject, $body, $attachments);

        // print_r($email);
        // Mail::raw('Database Backup', function ($message) use ($attachments) {
        //     $message->to('wethesd@gmail.com')
        //             ->subject('Database Backup');
        //     foreach ($attachments as $attachment) {
        //         $message->attach($attachment);
        //     }
        // });

    }

    private function cleanUp($backupDir)
    {
        $files = scandir($backupDir);
        foreach ($files as $file) {
            $filePath = "$backupDir/$file";
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        rmdir($backupDir);
    }
}
