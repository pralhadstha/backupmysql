<?php

  /**
   * SFTP Upload Klasse.
   *
   * Überträgt das Backup über SFTP auf einen oder mehrere externe Server.
   */
  class UploadSFTP extends Upload {

    public function __construct($folder, $filePath, $server, $data, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize)
    {
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
      } else {
        // Error 'Es wurden für $server nicht alle FTP-Daten angegeben'
      }
    }

    /**
     * Stellt die Verbindung zum externen Server her.
     */
    private function connect($dataSFTP)
    {
      $this->connection = new Net_SFTP($dataSFTP['Server']);

      if( ! $this->connection->login($dataSFTP['Username'], $dataSFTP['Passwort'])) {
        // Error 'Es konnte keine SFTP-Verbindung aufgebaut werden. Stimmt der Server Name und der Benutzername bzw. das Passwort?'
      }

      $this->changeAndCreateDir($this->folder, $dataSFTP['Pfad']);
    }

    /**
     * Ladet die Datei hoch.
     */
    private function upload()
    {
      if(file_exists($this->filePath . '.sql')) {
        $filePath = $this->filePath . '.sql';
      } elseif(file_exists($this->filePath . '.zip')) {
        $filePath = $this->filePath . '.zip';
      } else {
        // Error 'Der SFTP-Upload ist fehlgeschlagen. Es wurde keine Backup Datei gefunden'
      }

      if($this->isBackupSizeCorrect($filePath)) {
        $filename = explode('/', $filePath);

        if( ! $this->connection->put(end($filename), $filePath, NET_SFTP_LOCAL_FILE)) {
          // Error 'Der SFTP-Upload ist fehlgeschlagen. Kontrolliere deine Daten oder versuche es mit einem normalen FTP-Upload.'
        }
      }
    }

    /**
     * Löscht alte Backups raus.
     */
    private function deleteOldBackups()
    {
      BackupCleaner::deleteOldBackupsFromSFTP($this->connection, $this->maxAgeOfBackupFile, $this->maxBackupFiles);
    }

    /**
     * Falls angegeben, wird der Pfad auf dem externen Server gewechselt.
     *
     * Außerdem wird die Ordnerstruktur für die Backups erstellt.
     */
    private function changeAndCreateDir($folder, $path)
    {
      if( ! $this->connection->chdir($path)) {
        // Error 'Es konnte nicht auf den angegebenen Start-Pfad gewechselt werden. Existiert der Ordner $path? Es wurde der Standard-Pfad genommen'
      }

      $dirs = explode('/', $folder);

      foreach($dirs as $dir) {
        if( ! $this->connection->chdir($dir)) {
          $this->connection->mkdir($dir);
          $this->connection->chdir($dir);
          $this->connection->chmod(0777, $dir);
        }
      }
    }
  }