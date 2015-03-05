<?php

  /**
   * Backup Klasse.
   *
   * Speichert die Datenbank als SQL Datei, komprimiert diese optional, speichert
   * diese auf dem Server und lädt sie optional über FTP auf einen anderen Server.
   */
  class Backup extends Backupmysql {

    private $filePath;

    private $tables = array();

    private $query;

    private $newSQLFile = array();

    public function __construct(array $config)
    {
      parent::__construct($config);

      // backup/db/xx.xx.xx--xx-xx-Uhr--db
      $this->filePath = $this->folder . '/' . date('d.m.Y--H-i', time()) . '-Uhr--' . $this->getDBName();
      $this->createSQLFile();
      $this->deleteOldBackups();
    }

    /**
     * Bootstrap methode zum erstellen der SQL Datei.
     */
    private function createSQLFile()
    {
      $this->storeTableNames();
      $this->createSQLHead();
      $this->createSQLBody();
      $this->saveSQLFile($this->query);
      $this->cleanSQLBody();
      $this->saveSQLFile($this->newSQLFile);
      $this->throughOptions();
    }

    /**
     * Speichert alle Tabellennamen in ein array.
     */
    private function storeTableNames()
    {
      $row = $this->db->query('SHOW TABLES');
      while($fetch = $row->fetch_array()) {
        $this->tables[] = $fetch[0];
      }
    }

    /**
     * Erstellt den Kopf der SQL Datei.
     */
    private function createSQLHead()
    {
      // Todo: Vor und Nachteile abwägen. So kann man die SQL Datei nicht auf
      // eine neue Datenbank (die nicht den selben Namen hat) importieren.
      // Oder Doku erstellen wo man das ändern kann in der SQL Datei.
      //$this->query .= "CREATE DATABASE IF NOT EXISTS `" . $this->getDBName() . "`;\n";
      //$this->query .= "USE `" . $this->getDBName() . "`;\n\n";
    }

    /**
     * Erstellt den Hauptteil der SQL Datei.
     * Läuft alle Tabellen durch und speichert alle Daten davon.
     */
    private function createSQLBody()
    {
      foreach($this->tables as $table) {
        $row = $this->db->query('SELECT * FROM ' . $table);
        $fieldCount = $row->field_count;

        $this->query .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $this->query .= "DROP TABLE IF EXISTS `$table`;\n";
        $this->query .= "SET FOREIGN_KEY_CHECKS = 1;";

        // Speichert die Tabellenstruktur.
        $rowCreates = $this->db->query('SHOW CREATE TABLE ' . $table);
        $fetch = $rowCreates->fetch_array();
        $this->query .= "\n\n" . $fetch[1] . ";\n\n";

        // Speichert alle Werte dieser Tabelle.
        for($i = 0; $i < $fieldCount; $i++) {
          while($fetch = $row->fetch_array()) {
            $this->query .= "INSERT INTO $table VALUES (";

            for($j = 0; $j < $fieldCount; $j++) {
              $fetch[$j] = $this->db->real_escape_string($fetch[$j]);
              $fetch[$j] = str_replace("\n", "\\n", $fetch[$j]);

              if(isset($fetch[$j])) {
                $this->query .= "'" . $fetch[$j] . "'";
              } else {
                $this->query .= '""';
              }

              if($j < ($fieldCount - 1)) {
                $this->query .= ',';
              }
            }

            $this->query .= ");\n";
          }
        }

        $this->query .= "\n";
      }
    }

    /**
     * Löscht die Beziehungen zwischen den Tabellen aus der SQL Datei raus
     * und schreibt diese ans ende der Datei. Säubert die Syntax.
     */
    private function cleanSQLBody()
    {
      // Speichert alle beziehungen.
      $relations = array();

      // Speichert die momentane SQL Datei ohne die Beziehungen.
      $sqlFile = array();

      $file = file($this->filePath . '.sql');

      foreach($file as $lines) {

        // Speichert den Tabellennamen.
        if(strpos($lines, 'CREATE TABLE') !== false) {
          $tableName = explode('`', $lines);
        }

        // Falls es eine Zeile gibt die eine Beziehung enthält,
        // wird diese rausgelöscht und in ein anderes array geschrieben.
        if(strpos($lines, 'CONSTRAINT') === false) {
          $sqlFile[] = $lines;
        } else {
          $relations[$tableName[1]][] = trim($lines, ",\n");
        }
      }

      // Korrigiert die Syntax.
      foreach($sqlFile as $lines) {
        if(strpos($lines, ') ENGINE') !== false) {
          $count = count($this->newSQLFile) - 1;
          // Entfernt das komma mit dem Zeilenumbruch.
          $this->newSQLFile[$count] = rtrim($this->newSQLFile[$count], ",\n");
          // Fügt für die letzte Zeile den Zeilenumbruch wieder dazu.
          $this->newSQLFile[$count] .= "\n";
        }
        $this->newSQLFile[] = $lines;
      }

      // Verkettet die Beziehungen.
      $relationsReturn = "";
      foreach($relations as $table => $querys) {
        $relationsReturn .= "\n\nALTER TABLE `$table`\n";
        // Korrektur für die Syntax. Es wird entschieden ob ein Komma oder ein Semikolon ans Ende muss.
        $endQuery = end($querys);
        foreach($querys as $query) {
          $lastchar = $endQuery == $query ? ';' : ',';
          $relationsReturn .= "	ADD " . trim($query) . "$lastchar\n";
        }
      }

      // Fügt beide Arrays zu einem String.
      $this->newSQLFile = implode("", $this->newSQLFile);
      $this->newSQLFile .= $relationsReturn;
    }

    /**
     * Erstellt und speichert die SQL Datei.
     */
    private function saveSQLFile($value)
    {
      $handle = fopen($this->filePath . '.sql', 'w+');
      fwrite($handle, $value);
      fclose($handle);
      chmod($this->filePath . '.sql', 0777);
    }

    /**
     * Prüft die Optionen die der Benutzer angelegt hat und führt die nötigen Prozesse durch.
     */
    private function throughOptions()
    {
      if($this->zipCompression) {
        new ZIP($this->filePath);
      }

      if($this->uploadFTP) {
        new UploadFTP($this->folder, $this->filePath, $this->dataFTP, $this->maxBackupFiles, $this->maxAgeOfBackupFile);
      }

      if($this->uploadDropbox) {
        new UploadDropbox($this->folder, $this->filePath, $this->dataFTP, $this->maxBackupFiles, $this->maxAgeOfBackupFile);
      }
    }

    /**
     * Löscht alte Backups.
     *
     * Entweder ab einer bestimmten Anzahl oder ab einer bestimmten Zeitspanne.
     */
    private function deleteOldBackups()
    {
      BackupCleaner::deleteOldBackupsFromLocal($this->folder, $this->maxAgeOfBackupFile, $this->maxBackupFiles);
    }
  }
