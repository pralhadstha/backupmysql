<?php

  /**
   * FTP Upload Klasse.
   *
   * Überträgt das Backup über FTP auf einen oder mehrere externe Server.
   */
  class UploadFTP extends Upload {

    public function __construct($folder, $filePath, $server, $data, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize)
    {
      if( ! $this->isFTPActivated()) {
        return false;
      }

      parent::__construct($folder, $filePath, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize);

      $this->boot($server, $data);
    }

    /**
     * Bootstrap den Upload.
     */
    private function boot($server, $data)
    {
      if($this->isConnectionDataClean($data)) {
        $this->connect($data);
        $this->upload();
        $this->deleteOldBackups();
        ftp_close($this->connection);
      } else {
        // Error 'Es wurden für $server nicht alle FTP-Daten angegeben'
      }
    }

    /**
     * Stellt die Verbindung zum externen Server her.
     *
     * http://php.net/manual/de/function.ftp-ssl-connect.php
     */
    private function connect($dataFTP)
    {
      // Es wird überprüft ob eine sichere Verbindung angegeben wurde, und ob der Server diese funktion zur verfügung hat.
      // Wenn nicht, wird eine normale Verbindung hergestellt.
      if(function_exists('ftp_ssl_connect')) {
        $this->connection = ftp_ssl_connect($dataFTP['Server']);
      } else {
        $this->connection = ftp_connect($dataFTP['Server']);
      }

      // Es wird geprüft ob ein login möglich ist. Wenn nicht, und eine sichere Verbindung angegeben wurde,
      // kann der Server nicht mit verschlüsselten Details umgehen, und es wird versucht eine normale FTP-Verbindung aufzubauen.
      if( ! $login = ftp_login($this->connection, $dataFTP['Username'], $dataFTP['Passwort'])) {
        $this->connection = ftp_connect($dataFTP['Server']);
        $login = ftp_login($this->connection, $dataFTP['Username'], $dataFTP['Passwort']);
      }

      if( ! $this->connection || ! $login) {
        // Error 'Es konnte keine FTP-Verbindung aufgebaut werden. Stimmt der Server Name und der Benutzername bzw. das Passwort?'
      }

      ftp_pasv($this->connection, true);

      $this->changeAndCreateDir($this->folder, $dataFTP['Pfad']);
    }

    /**
     * Ladet die Datei hoch.
     */
    private function upload()
    {
      if(file_exists($this->filePath . '.sql')) {
        $filePath = $this->filePath . '.sql';
        $ftpMode = FTP_ASCII;
      } elseif(file_exists($this->filePath . '.zip')) {
        $filePath = $this->filePath . '.zip';
        $ftpMode = FTP_BINARY;
      } else {
        // Error 'Der FTP-Upload ist fehlgeschlagen. Es wurde keine Backup Datei gefunden'
      }

      if($this->isBackupSizeCorrect($filePath)) {
        $filename = explode('/', $filePath);

        if( ! ftp_put($this->connection, end($filename), $filePath, $ftpMode)) {
          // Error 'Der FTP-Upload ist fehlgeschlagen'
        }
      }
    }

    /**
     * Löscht alte Backups raus.
     */
    private function deleteOldBackups()
    {
      BackupCleaner::deleteOldBackupsFromFTP($this->connection, $this->maxAgeOfBackupFile, $this->maxBackupFiles);
    }

    /**
     * Falls angegeben, wird der Pfad auf dem externen Server gewechselt.
     *
     * Außerdem wird die Ordnerstruktur für die Backups erstellt.
     */
    private function changeAndCreateDir($folder, $path)
    {
      if( ! ftp_chdir($this->connection, $path)) {
        // Error 'Es konnte nicht auf den angegebenen Start-Pfad gewechselt werden. Existiert der Ordner $path? Es wurde der Standard-Pfad genommen'
      }

      $dirs = explode('/', $folder);

      foreach($dirs as $dir) {
        if( ! ftp_chdir($this->connection, $dir)) {
          ftp_mkdir($this->connection, $dir);
          ftp_chdir($this->connection, $dir);
          ftp_chmod($this->connection, 0777, $dir);
        }
      }
    }

    /**
     * Kontrolliert ob der Server die FTP Erweiterung installiert hat.
     */
    private function isFTPActivated()
    {
      if ( ! extension_loaded('ftp')) {
        // Error 'Dein Server hat die FTP Erweiterung nicht aktiviert'
        return false;
      }

      return true;
    }
  }
