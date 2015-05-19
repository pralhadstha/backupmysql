<?php

  abstract class Upload {

    protected $connection;

    protected $maxBackupFiles;
    protected $maxAgeOfBackupFile;

    protected $maxBackupSize;

    protected $folder;
    protected $filePath;

    public function __construct($folder, $filePath, $data, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize)
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
     * Eine Außnahme gilt für den Pfad. Ist dieser leer, wird eine Verbindung zum 'standard-root' Ordner hergestellt.
     */
    protected function isConnectionDataClean($data)
    {
      foreach($data as $key => $value) {
        if($key === 'Pfad') continue;
        if($value === '') return false;
      }

      return true;
    }
  }
