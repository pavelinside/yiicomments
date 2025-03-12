<?php
namespace app\modules\online\services;

/*
version 0.0.1
принимает адрес сайта c http(s), например:
	php /pathtofile/botparser.php http://www.ex.ua/
по умолчанию вложенность равна 1, задаётся
папка PATHLOG должна быть доступна для записи

проверял на сайтах
http://www.ex.ua/, http://www.ex.ua/ru/video/foreign?r=23775
http://habrahabr.ru/
http://olx.ua/
php /var/www/pbor/encoder/botparser.php http://www.torrentino.ru

возможные TODO
- получение заголовков и определение, нужно ли данную страницу загружать
- получение и передача cookie при получении страниц
- возможно, использование Curl ускорит работу скрипта
- параметр depth перенести в параметр при вызове скрипта
- обрабатываются ссылки, начинающиеся с / и http;
	добавить обработку ссылок, начинающихся не с /, но не содержащих
	протоколы типа magnet:, mailto: ...
*/

define('PATHLOG', '/var/www/fs/log/');

/**
 * Interface IExtractor, to get <img> and <a>
 * can extract via xpath, some library ...
 */
interface IExtractor {

    /**
     * get <a> from string
     * @param $url
     * @param $content
     * @return mixed
     */
    public function getLinks($url, $content);

    /**
     * get <img> from string
     * @param $content
     * @return mixed
     */
    public function getImgs($content);
}

/**
 * Interface IWriter
 */
interface IWriter {

    /**
     *
     * @return mixed
     */
    public function write();
}

/**
 * Class SimpleRegexTagExtractor
 */
class SimpleRegexTagExtractor implements IExtractor {

    /**
     * get all <a> tags
     * @param $url
     * @param $content
     * @return array bool
     */
    public function getLinks($url, $content){
        if(!$content){
            return false;
        }
        preg_match_all('/(?:<a[^>]*)href=(?:[ \'\"]?)([^\s\"\'> ]+)(?:[ \'\"]?)(?:[^>]*>)/i', $content, $mtch);
        if(!$mtch || !isset($mtch[1])){
            return [];
        }
        return array_unique($mtch[1]);
    }

    /**
     * get all <img> tags
     * @param $content
     * @return array
     */
    public function getImgs($content){
        preg_match_all('/(?:<img[^>]*)src=(?:[ \'\"]?)([^\s\"\'> ]+)(?:[ \'\"]?)(?:[^>]*>)/i', $content, $mtch);
        if(!$mtch || !isset($mtch[1])){
            return [];
        }
        return array_unique($mtch[1]);
    }
}

class BotparserService {
    private $_opts = ['http'=>['method'=>'GET','timeout'=>8,'user_agent'=>'asdsad']];
    private $_context;
    private $_pages = []; 				// result array of pages
    private $_wr; 								// IWriter
    private $_timer;
    private $_urlRoot = ''; 			// root of the site

    /**
     * start timer
     */
    public function timer(){
        $stmtime = microtime();
        $this->_timer = explode(' ', $stmtime);
    }

    /**
     * end timer
     */
    public function timerStop(){
        $endtime = explode(' ', microtime());
        $eTime = ($endtime[1] - $this->_timer[1]) + round($endtime[0] - $this->_timer[0], 4);
        return $eTime;
    }

    /**
     * get root of url, set context options
     * @param $url
     * @throws \Exception
     */
    public function __construct($url){
        // get root of site for links like
        $data = parse_url($url);
        if(isset($data['scheme'], $data['host']) && $data['scheme'] && $data['host']){
            $this->_urlRoot = $data['scheme'] . '://' . $data['host'] . '/';
        }else{
            throw new \Exception('Can not find root of the site: ' . $url);
        }

        $this->_context = stream_context_create($this->_opts);
    }

    /**
     * set IWriter for logging
     * @param IWriter $wr
     */
    public function setWriter(IWriter $wr){
        $this->_wr = $wr;
    }

    /**
     * get pages array
     * @return array
     */
    public function getPages(){
        return $this->_pages;
    }

    /**
     * filter link to pages
     * @param $url
     * @param array $links
     * @return array
     */
    private function _filterLinks($url, array $links){
        $res = [];
        foreach($links as $link){
            if(!$link || $link === '/' || $link === $url || isset($this->_pages[$link])){
                continue;
            }
            // отностельные ссылки
            if($link[0] === '/'){
                $res[] = $url . substr($link, 1);
            }else{
                if(strpos($link, 'http') === 0){
                    $res[] = $link;
                }
            }
        }
        return array_unique($res);
    }


    private function _curlGet($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    /**
     * parse site, set result in $this->_pages
     * @param IExtractor $ex
     * @param $url
     * @param int $depth
     * @return bool
     */
    public function parse(IExtractor $ex, $url, $depth = 2){
        $this->timer();

        // TODO get cookie or do something with javascript redirect, because redirect and error load
        // TODO get file format from headers
        // TODO use Curl - maybe work faster

        //$page = @file_get_contents($url, false, $this->_context);
        $page = $this->_curlGet($url);
        if(!$page){
            $this->_pages[$url] = ['e'=>'error','cnt'=>0,'time' > $this->timerStop()];
            return;
        }

        // get img count
        $imgs = $ex->getImgs($page);
        $this->_pages[$url] = ['cnt'=>count($imgs)];

        // if($this->_wr){
        // $this->_wr->write(["Load: $url ".count($imgs)."_".count($this->_pages), $imgs]);
        // }

        // if process all depth do not continue
        if($depth < 1){
            if(isset($this->_pages[$url])){
                $this->_pages[$url]['time'] = $this->timerStop();
            }
            return;
        }

        // process links of the site
        $links = $ex->getLinks($this->_urlRoot, $page);
        if($links){
            $links = $this->_filterLinks($this->_urlRoot, $links);
        }

        //if($this->_wr){
        //	$this->_wr->write($links);
        //}

        if(isset($this->_pages[$url])){
            $this->_pages[$url]['time'] = $this->timerStop();
        }
        // links no found
        if(!$links){
            return;
        }
        // recursion
        $depth--;
        if(is_array($links)){
            foreach($links as $link){
                if(!isset($this->_pages[$link])){
                    $this->parse($ex, $link, $depth);
                }
            }
        }
    }
}

/**
 * Class writer
 */
final class writer implements IWriter {
    private $logfile = 'custom.log';

    // TODO use fopen one time, fwrite many times, fclose one time

    /**
     * check paths
     * @throws \Exception
     */
    public function __construct(){
        if(!is_dir(PATHLOG)){
            throw new \Exception('Can not find log dir: ' . PATHLOG);
        }
        if(!is_writable(PATHLOG)){
            throw new \Exception('Cannot write in dir: ' . PATHLOG);
        }
    }

    /**
     * the formation of an array of string arguments
     * @param $args
     * @param $cnt
     * @return string
     */
    private function argsMake($args, $cnt){
        $s = '';
        for($i = 0; $i < $cnt; $i++){
            $str = is_array($args[$i]) ? "\n" . print_r($args[$i], true) : $args[$i] . chr(9);
            $s = ($s === '') ? $str : $s . ' ' . $str;
        }
        return $s;
    }

    /**
     * write to file
     * @param $fpath
     * @param $str
     * @param $mode
     * @param bool $newline
     * @param int $chmode
     * @return bool
     */
    public function fwrite($fpath, $str, $mode, $newline = false, $chmode = 0660){
        $f = fopen($fpath, $mode);
        if(!$f){
            return;
        }
        @flock($f, LOCK_EX); // write lock
        if($newline){
            @fwrite($f, "\n");
        }
        @fwrite($f, $str);
        @fflush($f); // cleansing file buffer and write to the file
        @flock($f, LOCK_UN); // unlock
        @fclose($f);
        @chmod($fpath, intval($chmode, 8));
    }

    /**
     * get all arguments to string and write to file
     */
    public function write(){
        $date = date('Y-m-d H:i:s');
        $str = $this->argsMake(func_get_args(), func_num_args());
        $this->fwrite(PATHLOG . $this->logfile, $date . ' ' . $str, 'ab', true);
    }
}

echo date('Y-m-d H:i:s') . 'start' . "\n";

// process arguments
if(!isset($argv) || !$argv || !isset($argv[1])){
    exit('ERROR: need a website address parameter' . "\n");
}
$url = $argv[1];

// writer
try{
    $wr = new writer();
}catch(\Exception $e){
    exit($e->getMessage());
}

// tag extractor
$ex = new SimpleRegexTagExtractor();
// parsing
try{
    $parser = new BotparserService($url);
    $parser->setWriter($wr);
    // depth 1 by default w
    $parser->parse($ex, $url, 1);
}catch(\Exception $e){
    exit($e->getMessage());
}

$pages = $parser->getPages();
if(!$pages || !is_array($pages)){
    exit('Pages not found');
}

// sort by img count Array([cnt] => 13 [time] => 0.2259)
uasort($pages, function ($a, $b){
    if(isset($a['cnt'], $b['cnt'])){
        if($a['cnt'] === $b['cnt']){
            if(isset($a['e'])){
                return -1;
            }elseif(isset($b['e'])){
                return 1;
            }
            return 0;
        }
        return ($a['cnt'] < $b['cnt']) ? 1 : -1;
    }
    return 0;
});

// report in report_dd.mm.yyyy.html
$res = "<!DOCTYPE HTML>\n<HTML>\n<BODY>\n<table>\n<tbody>\n<tr><td>page</td><td>count</td><td>time</td></tr>\n";
foreach($pages as $page => $pagerow){
    $cnt = isset($pagerow['cnt']) ? $pagerow['cnt'] : 0;
    $time = isset($pagerow['time']) ? $pagerow['time'] : 'err';
    $res .= "<tr><td>$page</td><td>$cnt</td><td>$time</td></tr>\n";
}
$res .= "</tbody>\n</table>\n</BODY>\n</HTML>";
// maybe use url of site in filename ???
$filename = 'report_' . date('d.m.Y') . '.html';
$wr->fwrite(PATHLOG . $filename, $res, 'wb');

echo date('Y-m-d H:i:s') . 'end' . "\n";
exit();

/*
Разработать Crawler (бота).

Задачи бота:
 - бот должен зайти на сайт и на каждой странице этого сайта посчитать кол-во тегов img
 - по завершению работы бот должен сгенерировать отчет (файл с именем report_dd.mm.yyyy.html) в виде (таблицы):
 * адрес страницы
 * кол-во тегов <img>
 * длительность обработки страницы
 * отсортировать по кол-ву тегов <img>

Входные данные бота:
 - адрес сайта

Результат:
 - файл report_dd.mm.yyyy.html

Использовать сторонние библиотеки, фреймворки и.т.д. запрещено.
Очень желательно видеть код с комментариями (было бы замечательно видеть в стиле PHP Documentator)
Скрипт должен запускаться как CLI приложение.
 */

// get headers
// $href = "http://www.torrentino.ru/torrents/2emvn-gerakl";
// $tmp = parse_url($href);
// print_r($tmp);
// $stream = @fsockopen($tmp['host'], 80);
// $headers = "GET ".$tmp['path']." HTTP/1.1\r\n";
// $headers.= "Host: ".$tmp['host']."\r\n";
// $headers.= "Connection: close\r\n";
// $headers.= "\r\n";
// fwrite($stream, $headers);
// $response = '';
// while (!feof($stream)) {
// 	$response .= fread($stream,1024);
// }
// $wr->write($response);
// echo current(explode("\r\n\r\n",$response,2));

/*
 XPAth
$dom = new \DomDocument();
@$dom->loadHTML( $content );
$xpath = new \DomXPath( $dom );
$_res = $xpath->query(".//a");
foreach($_res as $obj) {
$wr->write($obj->getAttribute('href'), $obj->nodeValue);
}
*/