<?php

/**
 * ECollapse makes a HTML DOM element collapsible as specified by passed options
 *
 * Based on the jQuery-Collapse script
 * @link http://webcloud.se/code/jQuery-Collapse/
 *
 * @author Luke Jurgs
 * @version 0.0.2-2012-03-03
 */
class ECollapse extends CWidget {

	private $_assetUrl;
	/**
	 * @var string $selector the css selector to apply the behaviour to, defaults to '.collaspe'.
	 */
	public $selector = '.collapse';
	/**
	 * @var boolean should the selected element start in a collapsed state, defaults to false.
	 */
	public $collapsed = true;
	/**
	 * @var mixed $speed the duration of the default animation, replaces the string '{duration}' in $show and $hide,
   * valid values are: 'fast', 'slow' or a integer specifying milliseconds, defaults to 'fast'.
	 */
	public $duration = 'fast';
	/**
	 * @var string $cssFile the CSS file used for the widget. If null (the default) the included css is used.
	 * @see assets/css/jquery.collapse.css
	 */
	public $cssFile;
	/**
	 * @var string $show a javascript function that defines how the content should be visually expanded. The string
	 * should begin with 'js:' so CJavascript::encode handles it correctly.
	 */
	public $show = 'js:
		function() {
			this.animate({
				opacity: "toggle",
				height: "toggle"
			}, "{duration}");
		}';
	/**
	 * @var string $hide a function that defines how the content should be visually hidden. The string
   * should begin with 'js:' so CJavascript::encode handles it correctly.
	 */
	public $hide = 'js:
		function() {
			this.animate({
				opacity: "toggle",
				height: "toggle"
			}, "{duration}");
		}';
	/**
	 * @var string $head the css selector of the element(s) you want act as clickable headings, defaults to 'h3'.
	 */
	public $head = 'h3';
	/**
	 * @var string $group the css selector of the element(s) to group hidden content, defaults to 'div, ul'.
	 */
	public $group = 'div, ul';
	/**
	 * @var string $cookieName the name of cookie used by the plugin, defaults to 'collapse'.
	 */
	public $cookieName = 'collapse';
	/**
	 * @var bool $cookieEnabled should the plugin use the inbuilt cookie functionality to remember the state of collapsible
	 * elements between visits, defaults to true.
	 */
	public $cookieEnabled = true;

	/**
	 * Initializes the widget.
	 * This method is called by {@link CBaseController::createWidget}
	 * and {@link CBaseController::beginWidget} after the widget's
	 * properties have been initialized.
	 */
	public function init() {
		$this->_assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets');
	}

	/**
	 * Executes the widget.
	 * This method is called by {@link CBaseController::endWidget}.
	 */
	public function run() {
		//setup the show and hide functions if they are set
		$this->show = str_replace('{duration}', $this->duration, $this->show);
		$this->hide = str_replace('{duration}', $this->duration, $this->hide);
		$this->registerClientScript();
	}

	/**
	 * Registers necessary client scripts.
	 */
	public function registerClientScript() {
		$clientScript = Yii::app()->clientScript;
		//register javascript
		$clientScript->registerCoreScript('jquery');
		//use minified JS if not debugging
		if (YII_DEBUG) {
			$cookieScript = '/js/jquery.cookie.js';
			$collapseScript = '/js/jquery.collapse.js';
		} else {
			$cookieScript = '/js/jquery.cookie.min.js';
			$collapseScript = '/js/jquery.collapse.min.js';
		}
		//do not register unused JS
		if ($this->cookieEnabled) {
			$clientScript->registerScriptFile($this->_assetUrl . $cookieScript);
		}
		$clientScript->registerScriptFile($this->_assetUrl . $collapseScript);

		$javascript = '';
		//register css
		if (null === $this->cssFile) {
			$clientScript->registerCssFile($this->_assetUrl . '/css/jquery.collapse.css');
			//apply the class in the default css to the head elements
			$javascript .= "jQuery('$this->selector $this->head').addClass('jquery-collapse-head');";
		} else {
			$clientScript->registerCssFile($this->cssFile);
		}

		$id = __CLASS__ . '#' . $this->getId();

		//add a class to the html element that lets us hide the groups so they don't flash
		$clientScript->registerScript('jquery-collapse-hide-group-script', 'document.documentElement.className = "js";', CClientScript::POS_HEAD);
		$groups = explode(',', $this->group);
		foreach ($groups as &$group) {
			$group = trim($group);
			$group = ".js {$this->selector} .inactive ~ $group .hide";
		}
		$groups = implode(', ', $groups);
		$clientScript->registerCss("{$id}-jquery-collapse-hide-group-css", "$groups  { display : none; }");

		//build the jquery collapsed script options
		$options = array(
			'head' => $this->head,
			'group' => $this->group,
			'cookieName' => $this->cookieName,
			'cookieEnabled' => $this->cookieEnabled,
		);

		if (!empty($this->show)) {
			$options['show'] = $this->show;
		}
		if (!empty($this->hide)) {
			$options['hide'] = $this->hide;
		}

		$options = CJavaScript::encode($options);

		//set the class of head elements to inactive
		if ($this->collapsed) {
			$javascript .= "
				jQuery('{$this->selector} {$this->head}').each(function() {
					if (!$(this).hasClass('active') && !$(this).hasClass('inactive')) {
						$(this).addClass('inactive');
					}
				});
			";
		} else {
			$javascript .= "
				jQuery('{$this->selector} {$this->head}').each(function() {
					if (!$(this).hasClass('active') && !$(this).hasClass('inactive')) {
						$(this).addClass('active');
					}
				});
			";
		}
		$javascript .= "jQuery('{$this->selector}').collapse($options);";
		$clientScript->registerScript($id, $javascript);
	}

}