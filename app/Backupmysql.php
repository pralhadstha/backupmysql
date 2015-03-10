<?php

  class Backupmysql {

    private $host;
    private $username;
    private $password;
    private $database;

    protected $databaseAlias;
    protected $zipCompression;
    protected $uploadFTP;
    protected $uploadDropbox;

    protected $maxBackupFiles;
    protected $maxAgeOfBackupFile;

    protected $maxBackupSizeForFTP;
    protected $maxBackupSizeForDropbox;

    protected $backupFolder;

    protected $dataFTP = array();

    protected $apiKey;

    protected $db;

    protected $folder;

    public function __construct(array $config)
    {
      // todo: Keys.
      $this->host = $config['Host'];
      $this->username = $config['Username'];
      $this->password = $config['Passwort'];
      $this->database = $config['Datenbank'];

      $this->databaseAlias = $config['Datenbank-Alias'] != '' ? $config['Datenbank-Alias'] : $this->database;
      $this->zipCompression = $config['ZIP-Komprimierung'];
      $this->uploadFTP = $config['FTP-Sicherung'];
      $this->uploadDropbox = $config['Dropbox-Sicherung'];

      $this->maxBackupFiles = $config['Max. Backup-Dateien'];
      $this->maxAgeOfBackupFile = $config['Max. Alter der Backup-Dateien'];

      $this->maxBackupSizeForFTP = $config['Max. Groeße für FTP-Sicherung'];
      $this->maxBackupSizeForDropbox = $config['Max. Groeße für Dropbox-Sicherung'];

      $this->backupFolder = $config['Backup-Ordner'];

      $this->dataFTP = $config['FTP-Daten'];

      $this->apiKey = $config['API-Schluessel'];

      // todo: Deaktivieren.
      error_reporting(-1);
      ini_set('display_errors', 'On');
      set_time_limit(0);

      // Gibt für größere Datenbanken genügend Speicher frei.
      ini_set('memory_limit', '1024M');

      $this->createBackupFolder();
      if($this->isConnectionDataClean()) {
        $this->connectDB();
      }
    }

    protected function getDBName()
    {
      return $this->database;
    }

    protected function getDBAliasName()
    {
      return $this->databaseAlias;
    }

    /**
     * Erstelle einen Backup Ordner für die lokale Sicherung.
     */
    private function createBackupFolder()
    {
      $this->folder = $this->backupFolder . '/' . $this->databaseAlias;

      if( ! file_exists($this->folder) && ! mkdir($this->folder, 0777, true)) {
        // Error 'Keine Berechtigung zum erstellen für den Ordner'
      }
    }

    /**
     * Prüft ob leere MySql-Verbindungsdaten hinterlegt sind.
     */
    private function isConnectionDataClean()
    {
      if($this->host != '' && $this->username != '' && $this->password != '' && $this->database != '') {
        return true;
      }

      // Error 'Prüfen Sie ob alle erforderlichen MySql-Verbindungsdaten hinterlegt sind'
      return false;
    }

    /**
     * Stellt die Datenbankverbindung her.
     */
    private function connectDB()
    {
      $this->db = new mysqli($this->host, $this->username, $this->password, $this->database);

      if($this->db->connect_errno) {
        // Error 'Es konnte keine Datenbank Verbindung aufgebaut werden'
      }

      $this->db->set_charset("utf8");
    }
  }