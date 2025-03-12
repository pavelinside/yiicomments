<?php

namespace app\modules\online\services;

class OlxService
{
    private $_url = 'http://olx.ua/';

    public function action($action, array $data){
        $arr = [];
        switch($action){
            case 'parseCategory':
                $arr = $this->getCategoryFromSite();
                break;
            case 'parseCity':
                $this->parseCity($data);
                break;
            case 'parseDistrict':
                $this->parseDistrict($data);
                break;
            case 'parseSubcategories':
                $this->parseSubcategories($data);
                break;
            case 'parseParameters':
                $this->parseParameters($data);
                break;
            case 'parseValues':
                $this->parseValues($data);
                break;
        }
        return ['arr' => print_r($arr, true)];
    }

    /**
     * 1) get list of main category and subcategory from site
     * @return type
     */
    private function getCategoryFromSite(){
        $doc = $this->getDOMDocument($this->_url, true);
        if(!$doc){
            return "Error load ".$this->_url;
        }
        $xpath = new \DOMXPath($doc);

        $mainCategoryQuery = ".//div[@class='maincategories']//a[contains(@class, 'link parent')]";
        $parsedLinks = $xpath->query($mainCategoryQuery);
        if (!$parsedLinks->length) {
            return 'No find main category';
        }
        $mainCategory = [];
        foreach ($parsedLinks as $link) {
            $id = $link->getAttribute('data-id');
            $href = $link->getAttribute('href');
            $slug = '';
            $title = '';
            if($href){
                $parts = explode('/', $href);
                if(isset($parts[3]) && $parts[3]){
                    $slug = $parts[3];
                }
            }
            $elem = $xpath->query(".//span", $link);
            if($elem->length){
                //$title = \Encoder\coder::coding($elem->item(0)->textContent, 0, 1);
                $title = $elem->item(0)->textContent;
            }

            $data = [
                'id' => $id,
                'slug' => $slug,
                'title' => $title,
                'subcategory' => []
            ];

            $subLinks = $xpath->query(".//div[@id='bottom$id']");
            if(!$subLinks->length){
                $mainCategories []= $data;
                continue;
            }
            $subLinks = $xpath->query(".//div[@id='bottom$id']//ul//a");
            foreach ($subLinks as $sublink) {
                $subid = $sublink->getAttribute('data-id');
                if($subid < 1){
                    return;
                }
                $subhref = $sublink->getAttribute('href');
                // slug http://olx.ua/detskiy-mir/odessa/
                if($subhref){
                    $parts = explode('/', $subhref);
                    if($parts[4]){
                        $subslug = $parts[4];
                    }
                }
                // title
                $subtitle = '';
                $elem = $xpath->query(".//span//span", $sublink);
                if($elem->length){
                    //$subtitle = \Encoder\coder::coding($elem->item(0)->textContent, 0, 1);
                    $subtitle = $elem->item(0)->textContent;
                }
                $subdata = [
                    'id' => $subid,
                    'slug' => $subslug,
                    'title' => $subtitle
                ];
                $data['subcategory'] []= $subdata;
            }
            $mainCategory []= $data;
        }
        return $mainCategory;
    }

    /**
    $category = $this->getCategoryFromSite();
    $this->addCategoryToDb($category);
     * @param array $mainCategory
     */
    private function addCategoryToDb(array $mainCategory){
        $sql = "TRUNCATE olx_category";
        \db::query($sql);

        $values = [];
        $sql = "INSERT INTO olx_category (id, slug, title, olx_categoryid) VALUES";
        foreach ($mainCategory as $category){
            $title = \db::escape_string($category['title']);
            $values []= "({$category['id']},'{$category['slug']}','$title',0)";
            foreach($category['subcategory'] as $subcategory){
                $subtitle = \db::escape_string($subcategory['title']);
                $values []= "({$subcategory['id']},'{$subcategory['slug']}','$subtitle',{$category['id']})";
            }
        }
        if($values){
            $sql .= implode(',', $values);
        }
        return \db::query($sql);
    }

    private function parseValues(array $data){
        $parameterNames = [];

        $sql = "SELECT name, id FROM olx_parameter";
        $parameters = \db::select($sql);

        // olx_category_parameter_value(categoryid, parameterid, 	parametervalueid)
        // olx_category_parameter(categoryid, parameterid)
        $iCategoryParameter = [];
        $iCategoryParameterValue = [];

        //echo 'sss';
        return 'ddd';

        foreach($data as $row){
            $h = 1;
            if(!isset($row['k']) || !$row['k']){
                continue;
            }
            //$row['v'];
            //$row['c'];

            $parameterName = key($row['k']);
            $sql = "SELECT id, name FROM olx_parameter WHERE name = '$parameterName'";
            $parameter = \db::arr($sql);
            if(count($parameter) > 1){
                $categoryId = key($row['c']);
                $sql = "SELECT ocp.parameterid  FROM `olx_category_parameter` ocp
					LEFT JOIN olx_parameter op ON op.id = ocp.parameterid
				WHERE ocp.`categoryid` = $categoryId AND op.name = '$parameterName'
				LIMIT 1";
                $parameterId = \db::val($sql);
            } else {
                $parameterId = $parameters[$parameterName];
            }

            foreach($row['c'] as $categoryid => $number){
                if($categoryid <= 0){
                    continue;
                }
                $iCategoryParameter []= "($categoryid, $parameterId)";
                if(!isset($row['v'])){
                    continue;
                }
                foreach($row['v'] as $pkey => $pvalue){
                    $sql = "INSERT INTO olx_parametervalue(pkey, pvalue) 
						VALUES('".\db::escape_string($pkey)."', '".\db::escape_string($pvalue)."')
						ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
                    $valueId = \db::query($sql, true);

                    $iCategoryParameterValue []= "($categoryid, $parameterId, $valueId)";
                }


            }

            // $categoryId
            $categoryCount = count($row['c']);
            if($categoryCount > 1 || $categoryCount == 0){
                //\sys\show($parameterName, $parameterId, count($row['c']));
            }

            //\sys\show($row['v']);


            //\sys\show($parameterName, $number);
            $parameterNames []= $parameterName;

        }


        sort($parameterNames);
        //\sys\show($parameterNames);

    }

    /**
     * category of 3 level
     * @param $html
     * @return string|void
     */
    public function olxTopLinks($html, $categoryId = false){
        $doc = $this->getDOMDocument($html, false);
        if(!$doc){
            return "Error load";
        }
        $xpath = new \DOMXPath($doc);

        //$parsedLinks = $xpath->query(".//div[@id='topLink']//div[@id='content']//table[@id='tiles']//td[contains(@class, 'tile product-tile')]");
        $parsedLinks = $xpath->query(".//div[@id='topLink']//a[contains(@class, 'topLink')]");
        if (!$parsedLinks->length) {
            echo 'not find links' . "<br>";
            return 'not find links';
        }

        $links = [];
        foreach ($parsedLinks as $link) {
            $dataId = $link->getAttribute('data-id');
            if(isset($links[$dataId])){
                continue;
            }
            $slug = '';
            $href = $link->getAttribute('href');
            // skip with filters (free, change, part job ...)
            if(strpos($href, '?search') !== FALSE){
                continue;
            }
            if($href){
                $parts = explode('/', $href);
                if(isset($parts[5]) && $parts[5]){
                    $slug = $parts[5];
                }
            }
            $title = '';
            $elem = $xpath->query(".//span//span", $link);
            if ($elem->length) {
                //$title = \Encoder\coder::coding($elem->item(0)->textContent, 0, 1);
                $title = $elem->item(0)->textContent;
            } else {
                //echo 'not found title'.$dataId;
            }

            $links[$dataId] = [
                'slug' => $slug,
                'title' => $title
            ];

            $link = "<a target='_blank' href='$href'>$dataId->$slug->$title</a>";
            echo $link."\n<br>";
        }
        return true;
    }

    /**
     * category of 3 level
     * @param $html
     * @return string|void
     */
    public function getFilters($html, $categoryId = false){
        $doc = $this->getDOMDocument($html, false);
        if(!$doc){
            return "Error load";
        }
        $xpath = new \DOMXPath($doc);

        // 1) filter_float
        // <li class="param paramFloat " data-name="search[filter_float_price][]" data-key="price" data-param-id="1" id="param_price">
        $parsedLinks = $xpath->query(".//li[contains(@class, 'param paramFloat')]");
        foreach ($parsedLinks as $link) {
            $dataKey = $link->getAttribute('data-key');
            echo "filter_float_".$dataKey.": ";
            $names = $xpath->query(".//span[contains(@class, 'header block')]", $link);
            foreach ($names as $name) {
                $dataKey = $name->getAttribute('data-default-label');
                echo $dataKey.";";
            }
            echo "<br>";
        }

        // 2) filter_enum
        $parsedLinks = $xpath->query(".//li[contains(@class, 'param paramSelect')]");
        foreach ($parsedLinks as $link) {
            $dataKey = $link->getAttribute('data-key');
            echo "filter_enum_".$dataKey.": ";
            //$names = $xpath->query(".//span[contains(@class, 'header block')]", $link);
            //foreach ($names as $name) {
            //	$dataKey = $name->getAttribute('data-default-label');
            //	echo $dataKey.";";
            //	}
            echo "<br>";
        }
        return true;
    }

    /**
     * work with categorys
     * @param string $path
     * @param int $action	(1 - get from site and save to disk, 2 - load from disk and process)
     */
    public function getCategoryPages($path, $action = 1){
        // get main categorys with subcategory
        $sql = "SELECT ocmain.*, oc.id subid, oc.slug subslug, oc.title subtitle FROM olx_category ocmain 
  LEFT JOIN olx_category oc ON oc.olx_categoryid = ocmain.id 
WHERE ocmain.olx_categoryid = 0";
        $categorys = \db::arr($sql);

        foreach ($categorys as $row){
            //$href = "http://olx.ua/nedvizhimost/arenda-komnat/odessa/";
            if($row['subid']){
                $fileName = $row['subid']."_".$row['slug']."_".$row['subslug'].".html";
                $hrefURL = "http://olx.ua/".$row['slug']."/".$row['subslug']."/odessa/";
                $title = $row['title']."/".$row['subtitle'];
                $categoryId = $row['subid'];
            } else {
                $fileName = $row['id']."_".$row['slug'].".html";
                $hrefURL = "http://olx.ua/".$row['slug']."/odessa/";
                $title = $row['title'];
                $categoryId = $row['id'];
            }
            $filePath = $path.$fileName;

            if($action == 1) {
                $content = file_get_contents($hrefURL);
                file_put_contents($filePath, $content);
            } else if($action == 2){
                if($row['subid']){
                    $link = "<a target='_blank' href='$hrefURL'>$title</a>";
                    echo $link."\n<br>";
                    $content = file_get_contents($filePath);
                    $this->olxTopLinks($content, $categoryId);
                }
            } else if($action == 3){
                if($row['subid']){
                    $link = "<a target='_blank' href='$hrefURL'>$title</a>";
                    echo $row['subid'].$link."\n<br>";
                    $content = file_get_contents($filePath);
                    $this->getFilters($content, $categoryId);
                    //break;
                }
            }
        }
    }

    public function init(){
        // 1) parse categorys and save to database
        //$mainCategory = $this->getCategoryFromSite();
        //$this->addCategoryToDb($mainCategory);

        // 2) download categorys pages and process
        $path = \APP::getConfig()->getPathLog()."olx"."/";
        @mkdir($path);
        //$this->getCategoryPages($path, 1);
        $this->getCategoryPages($path, 3);

        /*
http://olx.ua/ajax/odessa/search/list/
search[city_id]																				62
search[district_id]																		85						район
search[region_id]																			9
view																																??? ???????????
q
search[description]																		1							искать в описании
search[photos]																				1							искать с фото
search[courier]																				1							искать с доставкой
search[dist]																					0							+ сколько км к поиску
search[order]																					filter_float_price:asc, filter_float_price:asc, created_at:desc,
min_id
page																									3
search[category_id]																		386

search[filter_enum_state][]														used, new			?????????
search[filter_enum_washing_machine_manufacturers][]		2329
search[filter_float_price:from]												900
search[filter_float_price:to]													2700

search[filter_enum_price][0]=free					бесплатно
?search[filter_enum_price][0]=exchange		обмен


https://mc.yandex.ru/clmap/15553948?page-url=
    http%3A%2F%2Folx.ua%2Ftransport%2Flegkovye-avtomobili%2Fodessa%2F&pointer-click=rn%3A126480209%3Ax%3A29667%3Ay%3A39321%3At%3A139%3Ap%3AW%3BAQdQ7dE1%C2%87FAA1%7FA&browser-info=rqnl%3A1%3Ast%3A1462055805%3Au%3A14571824411014882893

         */

        // ?search%5Bdistrict_id%5D=85





    }

    private function addCity($id, $parentid, $url, $name, $namelong = '', $priority = 0){
        $sql = "INSERT INTO olx_city(id, cityid, name, url, name_long, priority) 
			VALUES($id, $parentid, '$name', '$url', '$namelong', $priority) 
			";
        // ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
        return \db::query($sql, 1);
    }

    private function parseCity(array $data){
        foreach($data as $row){
            $cityid = $this->addCity(100000 + $row['id'], 0, $row['url'], $row['name'], '', 0);
            foreach($row['subregions'] as $subrow){
                $this->addCity($subrow['id'], $cityid, $subrow['url'], $subrow['name'], $subrow['name_long'], 0);
            }
        }
        return true;
    }

    private function parseDistrict(array $data){
        foreach($data as $cityid => $districts){
            foreach($districts as $district){
                $sql = "INSERT INTO olx_district(id, cityid, name) VALUES({$district['id']}, $cityid, '{$district['name']}')";
                \db::query($sql);
            }
        }
        return true;
    }

    private function addCategory($id, $parentid, $title, $slug, $priority = 0){
        $title = \db::escape_string($title);
        $sql = "INSERT INTO olx_category (id, slug, title, olx_categoryid, priority) 
			VALUES ($id, '$slug', '$title', $parentid, $priority)";
        return \db::query($sql);
    }

    private function parseSubcategories(array $data){
        foreach($data as $categoryId => $row){
            //$label = $row['search_label'];
            foreach($row['children'] as $subcategoryid => $subcategory){
                $this->addCategory($subcategoryid, $categoryId, $subcategory['label'], $subcategory['code'], $subcategory['s']);
            }
        }
        return true;
    }

    private function parseParameters(array $data){
        \db::query("TRUNCATE olx_category_parameter");
        \db::query("TRUNCATE olx_parameter");

        foreach($data as $row){
            $parameter = \Encoder\Coder::coding($row['parameter'], true, false);
            $forName = isset($parameter['for']) ? $parameter['for'] : '';
            $suffix = $parameter['suffix'] ? "'{$parameter['suffix']}'" : NULL;
            $parameterId = $parameter['id'];
            $validators = '';
            if(isset($parameter['validators'])){
                if(is_array($parameter['validators'])){
                    $validators = print_r($parameter['validators'], true);
                } else {
                    $validators = $parameter['validators'];
                }
            }

            $sql = "INSERT INTO olx_parameter(id, name, label, solr_type, solr_column_name, type, validators, isNumeric, suffix, forname)
				VALUES ($parameterId, '{$parameter['key']}', '{$parameter['label']}', '{$parameter['solr_type']}',
					'{$parameter['solr_column_name']}', '{$parameter['type']}', '$validators', {$parameter['isNumeric']},
					$suffix, '$forName'
				)";
            $code = \db::query($sql);
            if(!$code){

            }
            foreach($row['categories'] as $categoryId => $value){
                if($categoryId <= 1){
                    continue;
                }
                $sql = "INSERT INTO olx_category_parameter(categoryid, parameterid, priority) 
					VALUES ($categoryId, $parameterId, $value)";
                $code = \db::query($sql);
                //\sys\show($sql);

            }
        }
    }

    /**
     *
     * @param type $html			html or url
     * @param type $isString
     * @return DOMDocument
     */
    private function getDOMDocument($html, $isURL = false){
        if($isURL){
            $html = file_get_contents($html);
        }
        $meta = '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
        if(!@$doc = \DOMDocument::loadHTML($meta . $html)){
            return false;
        }
        return $doc;
    }

    private function sql(){
        /*
        CREATE TABLE IF NOT EXISTS `olx_category` (
        `id` int(10) unsigned NOT NULL ,
            `slug` varchar(100) NOT NULL,
            `title` varchar(100) NOT NULL,
            `olx_categoryid` int(10) unsigned NOT NULL
        ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT charset=utf8;

        ALTER TABLE `olx_category`
            ADD PRIMARY KEY (`id`), ADD KEY `olx_categoryid` (`olx_categoryid`);
        */
    }
}