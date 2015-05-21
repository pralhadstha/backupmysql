<?php

  /**
   * BackupCleaner Klasse.
   *
   * Löscht Dateien nach dem komprimieren bzw. nach dem alter.
   *
   * todo: ioc!!!
   */
  class BackupCleaner {

    /**
     * Löscht die SQL Datei nach dem komprimieren.
     */
    public static function deleteSQLFileAfterCompression($filePath)
    {
      if(is_readable($filePath . '.sql')) {
        unlink($filePath . '.sql');
      }
    }

    /**
     * Löscht alte lokale Backups.
     */
    public static function deleteOldBackupsFromLocal($folder, $maxAgeOfBackupFile, $maxBackupFiles)
    {
      $backupFiles = array_slice(scandir($folder), 2);

      // Erstellt ein neues Array mit den Dateien inklusive erstellter Zeit.
      $backupFilesWithTime = array();
      foreach($backupFiles as $backupFile) {
        $backupFilesWithTime[$backupFile] = filectime($folder . '/' . $backupFile);
      }

      asort($backupFilesWithTime);

      // Umrechnung in Sekunden.
      $maxAgeOfBackupFile = $maxAgeOfBackupFile * 60 * 60;

      // Löscht zuerst alte Backups.
      foreach($backupFilesWithTime as $backupFile => $backupTime) {
        if($backupTime <= time() - $maxAgeOfBackupFile) {
          unlink($folder . '/' . $backupFile);
          unset($backupFilesWithTime[$backupFile]);
        }
      }

      // Löscht nach der Anzahl der Backups.
      if(count($backupFilesWithTime) > $maxBackupFiles) {
        array_splice($backupFilesWithTime, -$maxBackupFiles);

        foreach($backupFilesWithTime as $backupFile => $backupTime) {
          unlink($folder . '/' . $backupFile);
        }
      }
    }

    /**
     * Löscht alte Backups vom FTP Server.
     */
    public static function deleteOldBackupsFromFTP($connection, $maxAgeOfBackupFile, $maxBackupFiles)
    {
      $backupFiles = array_slice(ftp_nlist($connection, '.'), 2);

      // Erstellt ein neues Array mit den Dateien inklusive erstellter Zeit.
      $backupFilesWithTime = array();
      foreach($backupFiles as $backupFile) {
        if( ! function_exists('ftp_mdtm')) {
          // Error 'Dein Server unterstützt die PHP-Funktion ftp_mdtm() nicht. Es können keine Backups nach dem Alter gelöscht werden'
          return false;
        }

        $backupFilesWithTime[$backupFile] = ftp_mdtm($connection, $backupFile);
      }

      asort($backupFilesWithTime);

      // Umrechnung in Sekunden.
      $maxAgeOfBackupFile = $maxAgeOfBackupFile * 60 * 60;

      // Löscht zuerst alte Backups.
      foreach($backupFilesWithTime as $backupFile => $backupTime) {
        if($backupTime <= time() - $maxAgeOfBackupFile) {
          ftp_delete($connection, $backupFile);
          unset($backupFilesWithTime[$backupFile]);
        }
      }

      // Löscht nach der Anzahl der Backups.
      if(count($backupFilesWithTime) > $maxBackupFiles) {
        array_splice($backupFilesWithTime, -$maxBackupFiles);

        foreach($backupFilesWithTime as $backupFile => $backupTime) {
          ftp_delete($connection, $backupFile);
        }
      }
    }

    /**
     * Löscht alte Backups vom SFTP Server.
     */
    public static function deleteOldBackupsFromSFTP($connection, $maxAgeOfBackupFile, $maxBackupFiles)
    {
      $backupFiles = $connection->rawlist();
      sort($backupFiles);
      $backupFiles = array_slice($backupFiles, 2);

      // Erstellt ein neues Array mit den Dateien inklusive erstellter Zeit.
      $backupFilesWithTime = array();
      foreach($backupFiles as $backupFile) {
        $backupFilesWithTime[$backupFile['filename']] = $backupFile['mtime'];
      }

      asort($backupFilesWithTime);

      // Umrechnung in Sekunden.
      $maxAgeOfBackupFile = $maxAgeOfBackupFile * 60 * 60;

      // Löscht zuerst alte Backups.
      foreach($backupFilesWithTime as $backupFile => $backupTime) {
        if($backupTime <= time() - $maxAgeOfBackupFile) {
          $connection->delete($backupFile);
          unset($backupFilesWithTime[$backupFile]);
        }
      }

      // Löscht nach der Anzahl der Backups.
      if(count($backupFilesWithTime) > $maxBackupFiles) {
        array_splice($backupFilesWithTime, -$maxBackupFiles);

        foreach($backupFilesWithTime as $backupFile => $backupTime) {
          $connection->delete($backupFile);
        }
      }
    }

    /**
     * Löscht alte Backups aus Dropbox.
     */
    public static function deleteOldBackupsFromDropbox($connection, $maxAgeOfBackupFile, $maxBackupFiles, $path)
    {
      $backupFiles = $connection->metaData($path)['body']->contents;

      // Erstellt ein neues Array mit den Dateien inklusive erstellter Zeit.
      $backupFilesWithTime = array();
      foreach($backupFiles as $backupFile) {
        $filename = explode('/', $backupFile->path);
        $mtime = strtotime($backupFile->modified);

        $backupFilesWithTime[end($filename)] = $mtime;
      }

      asort($backupFilesWithTime);

      // Umrechnung in Sekunden.
      $maxAgeOfBackupFile = $maxAgeOfBackupFile * 60 * 60;

      // Löscht zuerst alte Backups.
      foreach($backupFilesWithTime as $backupFile => $backupTime) {
        if($backupTime <= time() - $maxAgeOfBackupFile) {
          $connection->delete($path . '/' . $backupFile);
          unset($backupFilesWithTime[$backupFile]);
        }
      }

      // Löscht nach der Anzahl der Backups.
      if(count($backupFilesWithTime) > $maxBackupFiles) {
        array_splice($backupFilesWithTime, -$maxBackupFiles);

        foreach($backupFilesWithTime as $backupFile => $backupTime) {
          $connection->delete($path . '/' . $backupFile);
        }
      }
    }
  }
