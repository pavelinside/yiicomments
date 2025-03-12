<?php
namespace app\modules\online\helpers;

class FileTool {

	/**
	 * @param $fileName
	 * @return string
	 */
	public static function getFileExtension($fileName) {
		$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
		return strtolower($fileExtension);
	}

	/**
	 * убирает из имени файла запрещённые символы \/:*?"<>|
	 * @param \File\Model\unknown_type $name
	 */
	public function clearUnacceptableCharactersFromName($fileName){
		$afrom= ['\\', '/', ':', '*', '?', '"', '<', '>', '|'];
		$ato= ['', '_', '_', '_', '_', '_', '_', '_', '_'];
		$newName = str_replace($afrom, $ato, $fileName);
		return $newName;
	}

	/**
	 * при ошибке удаления папки
	 * @param string $error
	 */
	public static function onDirectoryRemoveError($error) {
		//m_lgr::onlog("Ошибка удаления папки: " . $error, ['log' => 1]);
		return false;
	}

	/**
	 * удалить папку
	 * @param $dir
	 */
	public static function directoryremove($dir) {
		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (!$handle)
				return self::onDirectoryRemoveError("Ошибка открытия папки $dir");

			// получаем все файлы
			while (false !== ($file = readdir($handle))) {
				if ($file == '.' or $file == '..')
					continue;
				$srcFile = $dir . '/' . $file;

				// удаляем файл или папку
				if (is_file($srcFile)) {
					$deleted = unlink($srcFile);
					if (!$deleted)
						self::onDirectoryRemoveError("Не удалось удалить файл $srcFile");
				} else if (is_dir($srcFile)) {
					$deleted = self::directoryremove($srcFile);
					if (!$deleted)
						self::onDirectoryRemoveError("Не удалось удалить папку $srcFile");
				}
			}
			closedir($handle);
			$deleted = rmdir($dir);
			return $deleted ? TRUE : self::onDirectoryRemoveError("Не удалось удалить папку $dir");
		} else if (is_file($dir)) {
			$deleted = unlink($dir);
			if (!$deleted)
				return self::onDirectoryRemoveError("Не удалось удалить файл $dir");
		} else
			return self::onDirectoryRemoveError("Отсутствует папка $dir");
	}

	/**
	 * поиск строки в файле с определённой позиции
	 * @param string $fpath
	 * @param string $word
	 * @param integer $frompos
	 * @return boolean
	 */
	public static function findWord($fpath, $word, $frompos = 0) {
		$readbytes = 2097152;
		if (!file_exists($fpath))
			return false;
		$wordlen = strlen($word);
		if ($wordlen > $readbytes - 1)
			return false;
		// открыть файл
		$handle = fopen($fpath, "rb");
		if (!$handle)
			return false;
		// установить позицию курсора
		if ($frompos && filesize($fpath) < $frompos)
			fseek($handle, $frompos, SEEK_SET);

		$findpos = false;
		while (!feof($handle)) {
			$currseek = ftell($handle);
			$content = fread($handle, $readbytes);
			$pos = strpos($content, $word);
			if ($pos !== false) {
				$findpos = $currseek + $pos;
				break;
			} else if (!feof($handle)) {
				fseek($handle, -$wordlen, SEEK_CUR);
			}
		}

		fclose($handle);
		return $findpos;
	}

	/**
	 * удаляет sfx модуль из архива (rar, zip)
	 * @param string $fpath
	 */
	public static function archiveRemoveSfxStub($fpath) {
		if (!file_exists($fpath))
			return false;

		$str = "PADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADDINGXXPADDINGPADD";

		$ps1 = self::findWord($fpath, "MZ");
		$ps2 = self::findWord($fpath, $str);

		if ($ps1 !== false && $ps2 !== false) {
			$posfile = false;
			$filetyp = '';

			$ps3 = self::findWord($fpath, "Rar!", $ps2);    // rar архив
			$ps4 = self::findWord($fpath, "PK", $ps2);      // zip архив

			if ($ps3 !== false && ($ps2 + strlen($str)) == $ps3) {
				$posfile = $ps3;
				$filetyp = 'rar';
			} else if ($ps4 !== false && ($ps2 + strlen($str)) == $ps4) {
				$filetyp = 'zip';
				$posfile = $ps4;
			}

			if (!$posfile || !$filetyp)
				return false;

			$flinfs = self::fileGenerate(\File\Model\mydef::$uploadpath, \File\Model\m_auth::$id);
			if ($flinfs !== false) {
				// сохраняем файл без sfx модуля
				$handle = fopen($fpath, "rb");
				fseek($handle, $posfile, SEEK_SET);
				while (!feof($handle)) {
					$content = fread($handle, 2097152);
					fwrite($flinfs['handle'], $content);
				}
				fclose($handle);
				fclose($flinfs['handle']);
				@chmod($flinfs['path'], 0660);
				return array('type' => 'rar', 'path' => $flinfs['path']);
			}
		}
		return false;
	}

	/**
	 * Генерирует произвольное имя файла и возвращает его дескриптор
	 *
	 * @param string $path
	 * @param string $key
	 */
	public static function fileGenerate($path, $key = '') {
		$cnttry = 50;
		while ($cnttry > 0) {
			$fpath = $path . "/" . $key . md5(microtime() . rand());
			$f = @fopen($fpath, 'xb');
			if ($f !== false) {
				return array('path' => $fpath, 'handle' => $f);
			}
			$cnttry--;
		}
		return false;
	}

}