<?php
namespace app\modules\online\helpers;

class FIleSystemHelper {
    /**
     * get subdir of dir
     * @param string $dir
     */
    public static function getSubFolders(string $dir, array &$arr, array $skip = []){
        if(!is_dir($dir))
            return;

        $dh  = opendir($dir);
        while (false !== ($filename = readdir($dh))) {
            $full = $dir . $filename;
            if(is_dir($full) && !in_array($filename, [".", ".."]) && !in_array($filename, $skip)){
                $arr []= $full . DIRECTORY_SEPARATOR;
                static::getSubFolders($full . DIRECTORY_SEPARATOR, $arr);
            }
        }
        closedir($dh);
    }
}