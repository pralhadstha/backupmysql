<?php

  /**
   * Backupmysql.
   *
   * @author Viktor Geringer <devfakeplus@googlemail.com>
   * @link https://github.com/devfake/backupmysql
   */
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
      $config = array_values($config);

      $this->host = $config[0];
      $this->username = $config[1];
      $this->password = $config[2];
      $this->database = $config[3];

      $this->databaseAlias = $config[4] ?: $this->database;
      $this->zipCompression = $config[5];
      $this->uploadFTP = $config[6];
      $this->uploadDropbox = $config[7];

      $this->maxBackupFiles = $config[8];
      $this->maxAgeOfBackupFile = $config[9];

      $this->maxBackupSizeForFTP = $config[10];
      $this->maxBackupSizeForDropbox = $config[11];

      $this->backupFolder = $config[12];

      $this->dataFTP = $config[13];
      $this->dataDropbox = $config[14];

      $this->apiKey = $config[15];

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