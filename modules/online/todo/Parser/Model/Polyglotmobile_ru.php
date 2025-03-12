<?php
namespace Parser\Model;

use Helper\Curl as Curl;

/**
// 		$offerBlocks = $xpath->query("./tr[position()>1]", $offerTable);
// 		$info = $xpath->query("./td[@class='price']//a[contains(@class, 'price')]", $offerBlock);
//      $elements = $xpath->query(".//a[@class= 'link-3']/span[1]/span[last()]", $offerBlock);
 * download html files with rules and videos for english
 * Class polyglotmobile_ru
 * @package Parser\Parsers
 */
class Polyglotmobile_ru
{
    private $_host = "polyglotmobile.ru";
    private $_curl = null;
    private $_htmlLinks = [
        "http://polyglotmobile.ru/polyglot-english-base/",
        "http://polyglotmobile.ru/polyglot-english-advanced/",
        "http://polyglotmobile.ru/poliglot-angliyskie-artikli/"
    ];
    private $_videoLinks = [
        "http://polyglotmobile.ru/listening/"
    ];
    private $_xpath = false;

    /**
     * polyglotmobile_ru constructor.
     */
    public function __construct()
    {
        $this->_curl = new Curl();
    }

    public function parse(){
        $result = [];
        $indexHtml = "<!DOCTYPE HTML><html><style>
            <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
            </style><body>\n";
        $index = 1;
        foreach ($this->_htmlLinks as $url){
            $html = $this->_curl->getContent($url);
            if (!@$doc = \DOMDocument::loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' .$html)) {
                throw new \Exception("error load htmlLink page $url");
            }
            $this->_xpath = new \DOMXPath($doc);

            // links from page
            $pageLinks = $this->getHTMLLinks();
            foreach ($pageLinks as $pageUrl){
                $htmlPage = $this->_curl->getContent($pageUrl['href']);
                if (!@$docPage = \DOMDocument::loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' .$htmlPage)) {
                    throw new \Exception("error load htmlLink page $url");
                }
                $this->_xpath = new \DOMXPath($docPage);
                $fileName = $this->getFileName($pageUrl['href']);

                $link = "<a href='$fileName' title='{$pageUrl['title']}'>{$pageUrl['text']}</a>";
                $indexHtml .= "<p>$index $link</p>";
                $index++;

                // sve css files
                $links = $this->saveCss();
                $params = [];
                if($links){
                    $params['link'] = $links;
                }

                // save html
                $this->saveHTML($fileName, $params);
            }


            //return "d";

            $result = array_merge($result, $pageLinks);
        }
        $indexHtml .= "\n</body></html>";
        $this->saveHTML('index.html', ['content' => $indexHtml]);

    }

    /**
     * get lesson url links
     * @return array
     */
    private function getHTMLLinks()
    {
        $items = $this->_xpath->query(".//div[@class='entry-content']//a[contains(@href, '".$this->_host."')]");
        $result = [];
        for($i = 0, $ilen = $items->length; $i < $ilen; $i++) {
            $result[]= [
                'href' =>  $items->item($i)->getAttribute('href'),
                'title' =>  $items->item($i)->getAttribute('title'),
                'text' =>  $items->item($i)->textContent,
            ];
        }
        return $result;
    }

    /**
     * get fileName from url like http://polyglotmobile.ru/polyglot-english-base/lesson-1/
     * @param $href
     * @return string
     */
    private function getFileName($href){
        $items = explode('/', $href);
        $fileName = $items[count($items) - 2].".html";
        return $fileName;
    }

    private function saveCss()
    {
        $items = $this->_xpath->query(".//link[contains(@href, '.css')]");
        if(!$items->length){
            // TODO log
            return false;
        }
        $links = [];
        $filePath = \APP::getConfig()->getPathFile();
        for($i = 0, $ilen = $items->length; $i < $ilen; $i++){
            $href = $items->item($i)->getAttribute("href");
            $baseName = basename($href);
            // TODO переделать
            if(mb_strpos($baseName, '?') === false){
                if(!file_exists($filePath.$baseName)){
                    $content = $this->_curl->getContent($href);
                    if($content){
                        file_put_contents($filePath.$baseName, $content);
                    }
                }
                $link = "<link href='$baseName' rel='stylesheet' type='text/css'>";
                $links []= $link;
            }
        }
        return $links;
    }

    /**
     * save html page
     */
    private function saveHTML($fileName, array $params = []){
        $filePath = \APP::getConfig()->getPathFile();
        if($params && isset($params['content']) && is_string($params['content'])){
            file_put_contents($filePath.$fileName, $params['content']);
            return $params['content'];
        }

        $items = $this->_xpath->query(".//div[@id='content']/div[2]");
        if(!$items->length){
            // TODO log
            return false;
        }

        $item = $items->item(0);
        // remove last elements
        $divContent = $this->_xpath->query("div[@class='entry-content']", $item);
        if($divContent->length){
            $skipElements = $this->_xpath->query("child::*", $divContent->item(0));
            for($skipLength = $skipElements->length - 1, $j = $skipLength; $j > $skipLength - 4; $j--){
                if($j>0){
                    $divContent->item(0)->removeChild($skipElements->item($j));
                }
            }
        }

        $css = "";
        if(isset($params['link'])){
            if(is_array($params['link'])){
                $css = implode("\n", $params['link']);
            } else if(is_string($params['link'])){
                $css = $params['link'];
            }
        }

        $content = "<!DOCTYPE HTML><html><head>\n"
            .'<meta http-equiv="content-type" content="text/html; charset=utf-8">'
            .$css
            ."\n<style></style></head><body id='content' class='entry-content'>\n"
            .$item->C14N()
            ."\n</body></html>";


        file_put_contents($filePath.$fileName, $content);
        return $content;
    }
}