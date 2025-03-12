<?php

namespace Parser\Controller;

use Encoder\Controller;
use Parser\Model\Polyglotmobile_ru as pmModel;

class Polyglotmobile_ru extends Controller {
	private $_model = null;

	public function __construct() {
		parent::__construct();

		$this->_model = new pmModel();
	}

	public function indexAction() {
		$view = $this->getDefaultView();
		if (!isset($_POST['action'])) {
			$html = $this->_model->parse();
			return \APP::render($view, ['html' => "<p>$html</p>"]);
		}
		//$result = $this->_olx->action($_POST['action'], $_POST['data']);
		return ['code' => 1];
	}
}