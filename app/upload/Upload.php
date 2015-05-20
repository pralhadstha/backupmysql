<?php

  abstract class Upload {

    protected $connection;

    protected $maxBackupFiles;
    protected $maxAgeOfBackupFile;

    protected $maxBackupSize;

    protected $folder;
    protected $filePath;

    public function __construct($folder, $filePath, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize)
    {
      $this->maxBackupFiles = $maxBackupFiles;
      $this->maxAgeOfBackupFile = $maxAgeOfBackupFile;

      $this->folder = $folder;
      $this->filePath = $filePath;

      $this->maxBackupSize = $maxBackupSize;
    }

    /**
     * Prüft ob leere Verbindungsdaten hinterlegt sind.
     *
     * Ist der Wert ein String, wird die Überprüfung übersprungen.
     * Ist der Pfad leer, wird eine Verbindung zum 'standard-root' Ordner hergestellt.
     */
    protected function isConnectionDataClean($data)
    {
      if(is_string($data)) return true;

      foreach($data as $key => $value) {
        if($key === 'Pfad') continue;
        if($value === '') return false;
      }

      return true;
    }

    /**
     * Kontrolliert ob die Backup Datei nicht zu groß ist.
     */
    protected function isBackupSizeCorrect($filePath)
    {
      if(filesize($filePath) > $this->maxBackupSize) {
        // Error 'Die Backup Datei überschreitet die maximal angegebene Dateigröße für den FTP-Upload'
        return false;
      }

      return true;
    }
  }
