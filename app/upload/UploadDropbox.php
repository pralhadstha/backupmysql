<?php

  /**
   * Dropbox Upload Klasse.
   *
   * Überträgt das Backup in Dropbox.
   */
  class UploadDropbox extends Upload {

    private $apiKey;
    private $dbData;
    private $backupDB;
    private $path;

    public function __construct($folder, $filePath, $server, $data, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize, $apiKey, $dbData, $backupDB)
    {
      parent::__construct($folder, $filePath, $maxBackupFiles, $maxAgeOfBackupFile, $maxBackupSize);

      $this->apiKey = $apiKey;
      $this->dbData = $dbData;
      $this->backupDB = $backupDB;
      $path = explode('/', $this->folder);
      $this->path = end($path);

      $this->boot($server, $data);
    }

    /**
     * Bootstrap den Upload.
     */
    private function boot($server, $data)
    {
      spl_autoload_register(function($class){
        $class = str_replace('\\', '/', $class);
        require_once(__DIR__ . '/../libs/' . $class . '.php');
      });

      if($this->isConnectionDataClean($data)) {
        $this->connect($data);
        $this->upload();
        $this->deleteOldBackups();
      } else {
        // Error 'Es wurden für $server nicht alle Dropbox-Daten angegeben'
      }
    }

    /**
     * Stellt die Verbindung zur Dropbox App her.
     * Speichert den OAuth Token in der Datenbank.
     */
    private function connect($dataDropbox)
    {
      $key = $dataDropbox['Key'];
      $secret = $dataDropbox['Secret'];

      $protocol = ( ! empty($_SERVER['HTTPS'])) ? 'https' : 'http';
      $callback = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

      $encrypter = new \Dropbox\OAuth\Storage\Encrypter($this->apiKey);

      $userID = 1;

      $storage = new \Dropbox\OAuth\Storage\PDO($encrypter, $userID);
      $storage->connect($this->dbData['host'], $this->backupDB, $this->dbData['username'], $this->dbData['password']);

      $OAuth = new \Dropbox\OAuth\Consumer\Curl($key, $secret, $storage, $callback);
      $this->connection = new \Dropbox\API($OAuth);
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
        // Error 'Der Dropbox-Upload ist fehlgeschlagen. Es wurde keine Backup Datei gefunden'
      }

      if($this->isBackupSizeCorrect($filePath)) {
        $filename = explode('/', $filePath);

        if( ! $this->connection->putFile(__DIR__ . '/../../' . $filePath, end($filename), $this->path)) {
          // Error 'Der Dropbox-Upload ist fehlgeschlagen'
        }
      }
    }

    /**
     * Löscht alte Backups raus.
     */
    private function deleteOldBackups()
    {
      BackupCleaner::deleteOldBackupsFromDropbox($this->connection, $this->maxAgeOfBackupFile, $this->maxBackupFiles, $this->path);
    }
  }
