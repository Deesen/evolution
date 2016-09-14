<?php

class ManagerTemplateEngine {

	var $dom = array();
	var $templates;

	function __construct()
	{
		global $modx, $modx_lang_attribute, $modx_textdir;

		// Load template defaults
		$theme = $modx->config['manager_theme'];
		
		// Prepare DOM-array
		$this->dom['title'] = 'No title';
		$this->dom['actionButtons'] = array();
		$this->dom['header'] = array();
		$this->dom['body'] = array();
		$this->dom['footer'] = array();

		// Prepare Header
		$this->dom['header']['modx_lang_attribute'] = $modx_lang_attribute ? $modx_lang_attribute : 'en';
		$this->dom['header']['modx_textdir'] = isset($modx_textdir) && $modx_textdir === 'rtl' ? $modx_textdir : null;

		$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
		$this->dom['header']['OnManagerMainFrameHeaderHTMLBlock'] = is_array($evtOut) ? implode("\n", $evtOut) : '';
		
		if(!isset($modx->config['mgr_jquery_path']))  $modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';
		if(!isset($modx->config['mgr_date_picker_path'])) $modx->config['mgr_date_picker_path'] = 'media/script/air-datepicker/datepicker.inc.php';
		
		
		
		
	}

	function setTitle($title) {
		$this->title = $title;
	}

	function setActionButtons($arr) {
		$this->dom['actionButtons'] = array_merge($this->dom['actionButtons'], $arr);
	}

	function addBody($content) {
		$this->dom['body'] = $this->dom['body'] + $content;
	}

	function renderFullDom() {
		echo '<pre>';
		print_r($this->dom);
		echo '</pre>';
	}
}