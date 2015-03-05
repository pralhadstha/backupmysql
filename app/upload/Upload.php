<?php

  abstract class Upload {

    protected $connection;

    protected $maxBackupFiles;
    protected $maxAgeOfBackupFile;

    protected $folder;
    protected $filePath;

    public function __construct($folder, $filePath, $data, $maxBackupFiles, $maxAgeOfBackupFile)
    {
      $this->maxBackupFiles = $maxBackupFiles;
      $this->maxAgeOfBackupFile = $maxAgeOfBackupFile;

      $this->folder = $folder;
      $this->filePath = $filePath;
    }

    /**
     * Prüft ob leere Verbindungsdaten hinterlegt sind.
     *
     * Eine Außnahme gilt für den Pfad. Ist dieser leer, wird eine Verbindung zum 'standard-root' Ordner hergestellt.
     */
    protected function isConnectionDataClean($data)
    {
      foreach($data as $key => $value) {
        if($key === 'path') continue;
        if($value === '') return false;
      }

      return true;
    }
  }
