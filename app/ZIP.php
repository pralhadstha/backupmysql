<?php

  /**
   * Kompressions Klasse.
   *
   * Komprimiert die SQL Datei als ZIP und löscht danach die SQL Datei.
   */
  class ZIP {

    public function __construct($filePath)
    {
      if( ! $this->isZIPActivated()) {
        return false;
      }

      // Verhindert eine Ordner Struktur in der ZIP Datei.
      $filename = substr($filePath, strrpos($filePath, '/') + 1);

      $this->create($filePath, $filename);
    }

    /**
     * Erstellt aus der SQL Datei eine ZIP.
     */
    private function create($filePath, $filename)
    {
      $zip = new ZipArchive();
      if($zip->open($filePath . '.zip', ZIPARCHIVE::CREATE) !== true) {
        // Error 'Konnte keine ZIP Datei erstellen. Bitte kontrolliere ob der Ordner die erforderlichen Rechte hat'
      }
      $zip->addFile($filePath . '.sql', $filename . '.sql');
      $zip->close();

      $this->deleteSQLFile($filePath);
    }

    /**
     * Kontrolliert ob der Server die ZIP Erweiterung installiert hat.
     */
    private function isZIPActivated()
    {
      if( ! class_exists('ZipArchive')) {
        // Error 'Dein Server hat die ZipArchive Erweiterung nicht aktiviert'
      }

      return true;
    }

    /**
     * Löscht die SQL Datei nach dem komprimieren.
     */
    private function deleteSQLFile($filePath)
    {
      BackupCleaner::deleteSQLFileAfterCompression($filePath);
    }
  }
