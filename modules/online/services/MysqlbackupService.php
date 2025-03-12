<?php
namespace app\modules\online\services;

use app\modules\online\helpers\DbService;
use yii\db\Exception;

define('PREFIX', 'bkp_');

class MysqlbackupService
{
    private $_dbTables;

    public function getDbTables($where = '', $force = 0){
        if($this->_dbTables && !$force){
            return $this->_dbTables;
        }

        $wh = $where ? " AND $where" : "";
        $qry = "SELECT `TABLE_NAME` FROM `information_schema`.`TABLES`
		 WHERE `TABLE_TYPE`='BASE TABLE' AND `TABLE_SCHEMA`='" . \dbdef::DBNAME ."'$wh";

        $tables = DbService::col($qry);
        if(!$where){
            $this->_dbTables = $tables;
        }
        return $tables;
    }

    /**
     * backup table to PREFIX.$table
     * @param string $table
     * @return number
     * @throws \Exception
     */
    public function backup($table){
        $this->getDbTables();
        if(!in_array($table, $this->_dbTables)){
            throw new \Exception("$table Table do not exist");
        }

        $arr = DbService::row("SHOW CREATE TABLE `$table`", 1);
        $arr = explode('(', $arr[1], 2);

        $backupTable = PREFIX.$table;
        $qrys = ["DROP TABLE IF EXISTS `$backupTable`"];
        $qrys[] = "CREATE TABLE `$backupTable` (" . $arr[1];
        $qrys[] = "INSERT INTO `$backupTable` SELECT * FROM `$table`";

        $cnt = 0;
        foreach ($qrys as $qry){
            $cnt += DbService::query($qry) ?: 1;
        }
        return $cnt;
    }

    /**
     * restore table from PREFIX.$table
     * @param string $table
     * @return number
     * @throws \Exception
     */
    public function restore(string $table): int
    {
        $backupTable = PREFIX.$table;
        $this->getDbTables();
        if(!in_array($backupTable, $this->_dbTables)){
            throw new \Exception("$backupTable Table do not exist");
        }

        $arr = DbService::row("SHOW CREATE TABLE `$backupTable`", 1);
        $arr = explode('(', $arr[1], 2);

        $qrys = ["DROP TABLE IF EXISTS `$table`"];
        $qrys[] = "CREATE TABLE `$table` (" . $arr[1];
        $qrys[] = "INSERT INTO `$table` SELECT * FROM `$backupTable`";
        $cnt = 0;
        foreach ($qrys as $qry){
            $cnt+= DbService::query($qry) ?: 1;
        }
        return $cnt;
    }

    /**
     * delete backup for table PREFIX.$table
     * @param string $table
     * @return bool|int
     * @throws Exception
     */
    public function flush(string $table){
        $backupTable = PREFIX.$table;
        $qry = "DROP TABLE IF EXISTS `$backupTable`";
        return DbService::query($qry);
    }
}