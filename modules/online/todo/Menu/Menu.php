<?php
namespace Menu;

/**
 * Class menu
 */
class Menu {
	private $_menu = null;
	private $_menusorted = null;
	private $_hrefs = [];
	private $_rights = [];
  
  /**
   * load menu from database
   * @param integer $menutypid
   * @return array
   */
  public function load($menutypid){
  	$qry = "SELECT mn.*, count(mnchild.id) FROM menu mn 
  		LEFT JOIN menu mnchild ON mnchild.menuid=mn.id
  	WHERE mn.menutypid=$menutypid AND NOT mn.hidden
  	GROUP BY mn.id 
  	ORDER BY menuid, position";
  	$this->_menu = \db::arr($qry);
  	return $this->_menu;
  }
  
  private function _formAppend($id, $index, &$res){
  	for($i = $index+1, $ilen=count($this->_menu); $i < $ilen; $i++){
  		if($this->_menu[$i]['menuid'] == $id){
  
  			$uri = $this->_menu[$i]['uri'] ? $this->_menu[$i]['uri'] : "";;
  			$href = $this->_menu[$i]['href'] ? "?".$this->_menu[$i]['href'] : "";
  			if($href){
  				$uri .= "/";
  			}
  			if($uri.$href){
  				$this->_hrefs []= $uri.$href;
  				$this->_menu[$i]['key'] = $uri.$href;
  			}
  			
  			$res []= $this->_menu[$i];
  
  			$this->_formAppend($this->_menu[$i]['id'], $i, $res);
  		}
  	}
  }
  
	/**
		* form menu 
		* check rights
	 */
  public function form($rp){
  	// form menu
  	$res = [];
  	$this->_hrefs = [];
  	foreach($this->_menu as $i => $row){
  		if(!$row['menuid']){
  			$res []= $row;
  			$this->_formAppend($row['id'], $i, $res);
  		}
  	}
  	
  	// check menu rights
  	//$rights = $rp->rights($this->_hrefs);
  	for($i = count($res)-1; $i >-1; $i--){
  		//if(isset($res[$i]['key']) && (!isset($rights[$res[$i]['key']]) || !$rights[$res[$i]['key']])){
  			//array_splice($res, $i, 1);
  		//}
  	}
  	 
  	$this->_menusorted = $res;
  	return $res;
  }
  
  private function htmlOpenMenu($type = 'flex'){
  	switch ($type){
  		case 'dropdown':
  			return "<ul style='height:30px;border-left:0px solid black;margin-left:45px;' id='nav'>";
  		default:
  			return "<ul class='menuflex'>";
  	}
  }
  
  private function htmlOpenSubMenu($name, $type = 'flex'){
  	switch ($type){
  		case 'dropdown':
  			return "\n<li class='lihov'><span>$name</span>\n<ul>";
  		default:
  			return "\n<li>\n<b>$name</b>";
  	}
  }
  
  private function htmlCloseSubMenu($type = 'flex'){
  	switch ($type){
  		case 'dropdown':
  			return "\n</ul>\n</li>";
  		default:
  			return "\n</li>";
  	}
  }
  
  private function htmlBeforeLink($type = 'flex'){
  	switch ($type){
  		case 'dropdown':
  			return "<li><nobr>";
  		default:
  			return "";
  	}
  }
  
  private function htmlAfterLink($type = 'flex'){
  	switch ($type){
  		case 'dropdown':
  			return "</nobr></li>";
  		default:
  			return "";
  	}
  }
  
  /**
	 * form html for menu
   */
  public function formMenu($res, $type = 'flex'){
  	$str = $this->htmlOpenMenu($type);
  	$curid = 0;
  	$isfolder = true;
  	for($i = 0, $ilen = count($res); $i < $ilen; $i++){
  		$oldisfolder = $isfolder;
  		$isfolder = true;
  		if($i && $res[$i]['menuid'] == $curid){
  			$isfolder = false;
  		}
  
  		$name = $res[$i]['name'];
  		if($isfolder){
  			// folder
  			if($oldisfolder != $isfolder){
  				$str .= $this->htmlCloseSubMenu($type);
  			}
  			$str .= $this->htmlOpenSubMenu($name, $type);
  		} else {
  			// items
  			$uri = $res[$i]['uri'] ? \APP::getConfig()->getBaseURL() . $res[$i]['uri']."/" : "";;
  			$href = $res[$i]['href'] ? "?".$res[$i]['href'] : "";
  			
  			$str .= $this->htmlBeforeLink($type);
  			if($uri != 'js/'){
  				$target = md5($uri.$href);
  				$str .= "\n<a {$res[$i]['attributes']} href='$uri$href' target='$target' title='$name'>$name</a>";
  			} else {
  				$str .= "\n<a {$res[$i]['attributes']} title='$name'>$name</a>";
  			}
  			$str .= $this->htmlAfterLink($type);
  		}
  
  		if(!$curid || ($res[$i]['menuid'] != $curid)){
  			$curid = $res[$i]['id'];
  		}
  	}
  	if($ilen){
  		$str .= $this->htmlCloseSubMenu($type);
  	}
  	$str .= "</ul>";
  	return $str;
  }
}