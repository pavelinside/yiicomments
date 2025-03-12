<?php
namespace app\modules\online\services;

/**
 * когда-то был сайт ex.ua на котором можно было просматривать фильмы и я делал для приставки расширение
 */
class ExuaService {
    public $site = 'http://www.ex.ua';
    public $query = "r=23775";								// ?r=23775&per=20&p=1
    public $page = 0;
    public $perpage = 20;
    public $cntpages = 0;
    public $category = "";
    public $categories = array(
        '/ru/video/foreign' => 'Зарубежное',
        '/ru/video/our' => 'Наше',
        '/ru/video/foreign_series' => 'Зарубежные сериалы',
        '/ru/video/our_series' => 'Наши сериалы',
        '/ru/video/cartoon' => 'Мультфильмы',
        '/ru/video/anime' => 'Аниме',
        '/ru/video/documentary' => 'Документальное',
        '/ru/video/trailer' => 'Трейлеры',
        '/ru/video/clip' => 'Клипы',
        '/ru/video/concert' => 'Концерты',
        '/ru/video/show' => 'Шоу и Передачи',
        '/ru/video/training' => 'Уроки и Тренинги',
        '/ru/video/sport' => 'Спорт',
        '/ru/video/short' => 'Короткие видеоролики, приколы...',
        '/ru/video/theater' => 'Театр',
        '/ru/video/sermon' => 'Проповеди',
        '/ru/video/commercial' => 'Рекламные ролики',
        '/ru/video/mobile' => 'Для мобильных устройств',
        '/ru/video/artist' => 'Артисты',
    );

    /**
     * страница категории
     * @return string
     */
    public function url(){
        $url = $this->site;
        if($this->category){
            $str = "&per=".$this->perpage."&p=".$this->page;
            $url .= $this->category."?".$this->query.rawurlencode($str);
        }
        return $url;
    }

    public function urlsearch($word){
        $url = $this->site."/search";
        $str = "?s=".uni($word)."&per=100";
        $url .= $str;
        return $url;
    }

    public function parsePage($url, $getlinks = "category"){
        $page = file_get_contents($url);

        // ищем все ссылки
        $alllinks = $this->tagFromTxt($page);
        if(!$alllinks || !$alllinks[0])
            return array();

        $links = array();
        $this->cntpages = 0;
        for($i = 0, $len = count($alllinks[0]); $i < $len; $i++){
            $link = $alllinks[0][$i];

            // прочие ссылки пропускаем
            $folderInLink = false;
            if($getlinks == "category"){
                if(strpos($link, '?r=') === FALSE){
                    continue;
                }
                $ppos = strpos($link, '&p=');
                $imgpos = strpos($link, '<img');
                // ссылку-картинку пропускаем, не пропускаем ссылку на последнюю страницу
                if(($imgpos !== FALSE && $ppos === FALSE) || ($imgpos === FALSE && $ppos !== FALSE)){
                    continue;
                }
                if($ppos === FALSE && strpos($link, '/ru/') !== FALSE){
                    continue;
                }
            } else if($getlinks == "item"){
                // прочие ссылки пропускаем
                if(strpos($link, '/get/') === FALSE){
                    if(strpos($link, '?r=') !== FALSE && strpos($link, '&p=') === FALSE){
                        $folderInLink = true;
                    } else {
                        continue;
                    }
                }
            }

            $linkinfo = $this->aFromTxt($link);
            if(!$linkinfo || !$linkinfo[1]){
                continue;
            }

            if($getlinks == "search"){
                if(!$linkinfo[1][0] || $linkinfo[1][0] == '/' || preg_match('/\D/', str_replace('/', '', $linkinfo[1][0]))){
                    continue;
                }
            }

            if($getlinks == "category"){
                // ищем максимальную страницу
                if($ppos !== FALSE){
                    parse_str($linkinfo[1][0], $linkarr);
                    if(isset($linkarr['p']) && intval($linkarr['p']) > $this->cntpages){
                        $this->cntpages = intval($linkarr['p']);
                    }
                    continue;
                }
            }

            // описание фильма
            $txt = "";
            if($getlinks == "category" || $getlinks == "search" || $folderInLink){
                $txts = $this->tagFromTxt($link, 'b');
                if($txts && isset($txts[0])){
                    $txt = str_replace(array('<b>', '</b>'), '', $txts[0][0]);
                }
                if($folderInLink && !$txt){
                    continue;
                }
            } else if($getlinks == "item"){
                // <a href='/get/141521067' title='Zodiac. Director&#39;s Cut (2007) BDRip-AVC.mkv' rel='nofollow'>Zodiac. Director&#39;s Cut (2007) BDRip-AVC.mkv</a>
                $lastpos = strrpos($link, '</a>');
                if($lastpos !== FALSE){
                    $firstpos = strrpos($link, '>', $lastpos - strlen($link));
                    if($firstpos !== FALSE){
                        $txt = substr($link, $firstpos+1, $lastpos - $firstpos - 1);
                    }
                }
            }

            if(!isset($links [$linkinfo[1][0]]) || ($txt && $txt != uni("играть"))){
                $links [$linkinfo[1][0]]= $txt;
            }
        }
        if($this->page - $this->cntpages == 1){
            $this->cntpages = $this->page;
        }

        return $links;
    }

    public function linksProcess(array $links, $isFolder = true){
        $ret = array();
        foreach($links as $link => $linkinfo){
            $txt = mb_convert_encoding($linkinfo, "Windows-1251", "UTF-8");
            if($isFolder || strpos($link, '?r=') !== FALSE){
                $ret[] = Container("url=".$this->site.$link, uni($txt));
            } else {
                $ret[] = Item($this->site.$link, uni($txt));
            }
        }
        return $ret;
    }

    function _pluginSearch($arg){
        if(!$arg){
            return array();
        }
        $url = $this->urlsearch($arg);
        $links = $this->parsePage($url, "search");

        return $this->linksProcess($links);
    }

    function _pluginMain($arg){
        if(!$arg){
            $this->page = 0;
            $this->perpage = 20;

            $ret = array();
            foreach($this->categories as $categoryurl => $category){
                $this->category = $categoryurl;
                $url = $this->url();
                $ret[] = Container("url=".$url, uni($category));
            }

            $this->category = "";
            return $ret;
        }

        // TODO search?s=а&original_id=70538&p=1

        $arr = parse_url($arg);

        // выбор категории
        if(!$this->category){
            foreach($this->categories as $categoryurl => $category){
                if(strpos($arr['path'], $categoryurl) !== FALSE){
                    $this->category = $categoryurl;
                    break;
                }
            }
        }

        // на какой мы сейчас странице
        if($this->category && isset($arr['query']) && $arr['query']){
            parse_str($arr['query'], $queryarr);
            if(isset($queryarr['per']) && $queryarr['per'] && in_array($queryarr['per'], array(4, 8, 12, 16, 20, 24, 32, 40, 60, 80, 100))){
                $this->perpage = $queryarr['per'];
            }
            if(isset($queryarr['p']) && $queryarr['p'] && $queryarr['p'] >= 0 && (!$this->cntpages || $queryarr['p'] <= $this->cntpages)){
                $this->page = $queryarr['p'];
            }
        }

        // get links
        $url = $this->category ? $this->url() : $arg;
        $links = $this->parsePage(rawurldecode($url), $this->category ? "category" : "item");

        $oldpage = $this->page;

        // если мы в папке
        $ret = array();
        if($this->category){
            // next
            $this->page = $oldpage + 1;
            if(!$this->cntpages || ($this->page < $this->cntpages)){
                $ret[] = Container("url=".$this->url(), uni("Следующая(".$this->page.")"));
            }
            $this->page = $oldpage;
        }

        $ar = $this->linksProcess($links, $this->category ? true : false);
        $ret = array_merge($ret, $ar);

        // если мы в папке
        if($this->category){
            // root
            $ret[] = Container("url=", uni("В корень"));

            // last page
            if($this->cntpages && $oldpage != $this->cntpages){
                $this->page = $this->cntpages;
                $ret[] = Container("url=".$this->url(), uni("На последнюю(".$this->cntpages.")"));
            }

            // first page
            if($this->page != 0){
                $this->page = 0;
                $ret[] = Container("url=".$this->url(), uni("На первую(0)"));
            }

            // prev
            $this->page = $oldpage - 1;
            if($this->page > 0){
                $ret[] = Container("url=".$this->url(), uni("Предыдущая(".$this->page.")"));
            }

            $this->page = $oldpage;
        }

        return $ret;
    }

    /**
     * из текста получить все ссылки
     * @param $str
     */
    public function aFromTxt($str){
        preg_match_all('/(?:<a[^>]*)href=(?:[ \'\"]?)([^\s\"\'> ]+)(?:[ \'\"]?)(?:[^>]*>)/i', $str, $mtch);
        return $mtch;
    }

    /**
     * получить все <script>*</sript> из строки
     * @param string $str
     */
    public function tagFromTxt($str, $tag = 'a'){
        // U - модификатор инвертирует жадность квантификаторов
        preg_match_all('/(?:<'.$tag.')(?:[\S\s]*)(?:\/'.$tag.'>)/iU', $str, $mtch);
        return $mtch;
    }
}

function Container($id, $title, $key = 'url'){
    if($id && strpos($id, 'url=') === 0){
        $id = substr($id, 4);
    }
    $href = "?control=exua&$key=$id";
    return "<a href='$href' title='$title'>$title</a><br>";
}

function Item($url, $title){
    return "<a target='_blank' rel='nofollow' href='$url' title='$title'>$title</a><br>";
}

function uni($w){
    return mb_convert_encoding($w, "UTF-8", "Windows-1251");
}