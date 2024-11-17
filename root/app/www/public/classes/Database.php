<?php

/*
----------------------------------
 ------  Created: 082124   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Database');

class Database
{
    use Apps;
    use NotificationLink;
    use NotificationPlatform;
    use NotificationTrigger;
    use Settings;
    use Starrs;
    use Usage;

    public $db;
    public $dbName;
    public $settingsTable;
    public $notificationPlatformTable;
    public $notificationTriggersTable;
    public $notificationLinkTable;
    public $starr;
    public $shell;

    public function __construct($dbName)
    {
        $this->connect(DATABASE_PATH . $dbName);
        $this->dbName = $dbName;
        $this->starr = new Starr();
        $this->shell = new Shell();
    }

    public function connect($dbFile)
    {
        $db = new SQLite3($dbFile, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $db->exec('PRAGMA journal_mode = WAL;');
        $this->db = $db;
    }

    public function query($query)
    {
        if (str_equals_any(substr($query, 0, 5), ['UPDATE', 'INSERT', 'DELETE'])) {
            $transaction[] = 'BEGIN TRANSACTION;';
            $transaction[] = $query . (substr($query, -1) != ';' ? ';' : '');
            $transaction[] = 'COMMIT;';

            $transaction = implode("\n", $transaction);
        } else {
            $transaction = $query;
        }

        return $this->db->query($transaction);
    }

    public function fetchAssoc($res)
    {
        return !$res ? [] : $res->fetchArray(SQLITE3_ASSOC);
    }

    public function affectedRows()
    {
        return $this->db->changes();
    }

    public function insertId()
    {
        return $this->db->lastInsertRowID();
    }

    public function error()
    {
        return $this->db->lastErrorMsg();
    }

    public function prepare($in)
    {
        if (!$in) {
            return;
        }

        $out = addslashes(stripslashes($in));
        return $out;
    }

    public function backup()
    {
        $this->db->query("VACUUM INTO '" . BACKUP_PATH . date('Y-m-d') . '/' . $this->dbName . "'");

        if ($this->error() != 'not an error') {
            return $this->error();
        }
    }

    public function getBackups()
    {
        if (!is_dir(BACKUP_PATH)) {
            return [];
        }

        $dir = opendir(BACKUP_PATH);
        while ($backup = readdir($dir)) {
            if ($backup[0] == '.' || !is_dir(BACKUP_PATH . $backup)) {
                continue;
            }

            $proxyDatabaseSize = filesize(BACKUP_PATH . $backup . '/' . PROXY_DATABASE_NAME);
            $usageDatabaseSize = filesize(BACKUP_PATH . $backup . '/' . USAGE_DATABASE_NAME);
            $backups[$backup] = [PROXY_DATABASE_NAME => byteConversion($proxyDatabaseSize), USAGE_DATABASE_NAME => byteConversion($usageDatabaseSize)];
        }
        closedir($dir);
        krsort($backups);

        return $backups;
    }

    public function getNewestMigration()
    {
        $newestMigration = '001';
        $dir = opendir(MIGRATIONS_PATH);
        while ($migration = readdir($dir)) {
            if (intval(substr($migration, 0, 3)) > intval($newestMigration) && str_contains($migration, '.php')) {
                $newestMigration = substr($migration, 0, 3);
            }
        }
        closedir($dir);

        return $newestMigration;
    }

    public function migrations()
    {
        $proxyDb    = $this;
        $usageDb    = new Database(USAGE_DATABASE_NAME);
        $starr      = new Starr();

        //-- DONT RUN MIGRATIONS IF IT IS ALREADY RUNNING
        if (file_exists(MIGRATION_FILE)) {
            return;
        }

        setFile(MIGRATION_FILE, ['started' => date('c')]);

        if (filesize(DATABASE_PATH . $this->dbName) == 0) { //-- INITIAL SETUP
            logger(SYSTEM_LOG, 'Creating database and applying migration 001_initial_setup');
            logger(MIGRATION_LOG, '====================|');
            logger(MIGRATION_LOG, '====================| migrations');
            logger(MIGRATION_LOG, '====================|');
            logger(MIGRATION_LOG, 'migration 001 ->');
            $q = [];
            require MIGRATIONS_PATH . '001_initial_setup.php';
            logger(MIGRATION_LOG, 'migration 001 <-');

            $neededMigrations = [];
            $dir = opendir(MIGRATIONS_PATH);
            while ($migration = readdir($dir)) {
                if (substr($migration, 0, 3) > '001' && substr($migration, 0, 3) > $this->getSetting('migration') && str_contains($migration, '.php')) {
                    $neededMigrations[substr($migration, 0, 3)] = $migration;
                }
            }
            closedir($dir);

            if ($neededMigrations) {
                ksort($neededMigrations);

                foreach ($neededMigrations as $migrationNumber => $neededMigration) {
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' ->');
                    $q = [];
                    require MIGRATIONS_PATH . $neededMigration;
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' <-');
                }
            }
        } else { //-- GET CURRENT MIGRATION & CHECK FOR NEEDED MIGRATIONS
            $neededMigrations = [];
            $dir = opendir(MIGRATIONS_PATH);
            while ($migration = readdir($dir)) {
                if (substr($migration, 0, 3) > $this->getSetting('migration') && str_contains($migration, '.php')) {
                    $neededMigrations[substr($migration, 0, 3)] = $migration;
                }
            }
            closedir($dir);

            if ($neededMigrations) {
                ksort($neededMigrations);

                logger(SYSTEM_LOG, 'Applying migrations: ' . implode(', ', array_keys($neededMigrations)));
                logger(MIGRATION_LOG, '====================|');
                logger(MIGRATION_LOG, '====================| migrations');
                logger(MIGRATION_LOG, '====================|');

                foreach ($neededMigrations as $migrationNumber => $neededMigration) {
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' ->');
                    $q = [];
                    require MIGRATIONS_PATH . $neededMigration;
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' <-');
                }
            }
        }

        deleteFile(MIGRATION_FILE);
    }
}
