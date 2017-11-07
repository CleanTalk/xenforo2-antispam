<?php

namespace CleanTalk\XF\Template;

use XF\App;
use XF\Language;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Router;
use XF\Util\Arr;

class Templater extends \XF\Template\Templater
{
	const MAX_EXECUTION_DEPTH = 50;

	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @var Router
	 */
	protected $router;
	protected $routerType;

	/**
	 * @var \Closure
	 */
	protected $pather;

	/**
	 * @var Language
	 */
	protected $language;

	protected $compiledPath;

	protected $styleId = 0;

	/**
	 * @var \XF\Style|null
	 */
	protected $style;

	protected $filters = [];
	protected $functions = [];
	protected $tests = [];

	protected $defaultParams = [];

	protected $templateCache = [];

	protected $jQueryVersion;
	protected $jQuerySource = 'local';
	protected $jsVersion = '';

	protected $dynamicDefaultAvatars = true;

	protected $mediaSites = [];

	protected $groupStyles = [];
	protected $userTitleLadder = [];
	protected $userTitleLadderField = 'trophy_points';
	protected $userBanners = [];
	protected $userBannerConfig = [];

	protected $widgetPositions = [];

	/**
	 * @var WatcherInterface[]
	 */
	protected $watchers = [];

	protected $currentTemplateType;
	protected $currentTemplateName;

	protected $wrapTemplateName = null;
	protected $wrapTemplateParams = null;

	protected $executionDepth = 0;
	protected $templateErrors = [];

	protected $escapeContext = 'html';

	protected $includeCss = [];
	protected $inlineCss = [];
	protected $includeJs = [];
	protected $inlineJs = [];

	protected $sidebar = [];
	protected $sideNav = [];

	protected $uniqueIdCounter = 0;
	protected $uniqueIdPrefix;
	protected $uniqueIdFormat = '_xfUid-%s';

	protected $avatarDefaultStylingCache = [];
	protected $avatarLetterRegex = '/[^\(\)\{\}\[\]\<\>\-\.\+\:\=\*\!\|\^\/\\\\\'`"_,#~ ]/u';

	public $pageParams = [];

	protected $defaultFilters = [
		'default' => 'filterDefault',
		'censor' => 'filterCensor',
		'currency' => 'filterCurrency',
		'escape' => 'filterEscape',
		'for_attr' => 'filterForAttr',
		'file_size' => 'filterFileSize',
		'first' => 'filterFirst',
		'hex' => 'filterHex',
		'host' => 'filterHost',
		'ip' => 'filterIp',
		'join' => 'filterJoin',
		'json' => 'filterJson',
		'last' => 'filterLast',
		'nl2br' => 'filterNl2Br',
		'nl2nl' => 'filterNl2Nl',
		'number' => 'filterNumber',
		'number_short' => 'filterNumberShort',
		'parens' => 'filterParens',
		'pluck' => 'filterPluck',
		'preescaped' => 'filterPreEscaped',
		'raw' => 'filterRaw',
		'replace' => 'filterReplace',
		'strip_tags' => 'filterStripTags',
		'to_lower' => 'filterToLower',
		'to_upper' => 'filterToUpper',
		'url' => 'filterUrl',
		'urlencode' => 'filterUrlencode'
	];

	protected $defaultFunctions = [
		'anchor_target' => 'fnAnchorTarget',
		'array_keys' => 'fnArrayKeys',
		'array_merge' => 'fnArrayMerge',
		'array_values' => 'fnArrayValues',
		'attributes' => 'fnAttributes',
		'avatar' => 'fnAvatar',
		'base_url' => 'fnBaseUrl',
		'bb_code' => 'fnBbCode',
		'button_icon' => 'fnButtonIcon',
		'callable' => 'fnCallable',
		'captcha' => 'fnCaptcha',
		'ceil' => 'fnCeil',
		'contains' => 'fnContains',
		'copyright' => 'fnCopyright',
		'core_js' => 'fnCoreJs',
		'count' => 'fnCount',
		'csrf_input' => 'fnCsrfInput',
		'csrf_token' => 'fnCsrfToken',
		'css_url' => 'fnCssUrl',
		'date' => 'fnDate',
		'date_from_format' => 'fnDateFromFormat',
		'date_dynamic' => 'fnDateDynamic',
		'date_time' => 'fnDateTime',
		'debug_url' => 'fnDebugUrl',
		'display_totals' => 'fnDisplayTotals',
		'dump' => 'fnDump',
		'dump_simple' => 'fnDumpSimple',
		'file_size' => 'fnFileSize',
		'floor' => 'fnFloor',
		'gravatar_url' => 'fnGravatarUrl',
		'highlight' => 'fnHighlight',
		'in_array' => 'fnInArray',
		'is_array' => 'fnIsArray',
		'is_addon_active' => 'fnIsAddonActive',
		'is_editor_capable' => 'fnIsEditorCapable',
		'js_url' => 'fnJsUrl',
		'last_pages' => 'fnLastPages',
		'likes' => 'fnLikes',
		'likes_content' => 'fnLikesContent',
		'link' => 'fnLink',
		'link_type' => 'fnLinkType',
		'max_length' => 'fnMaxLength',
		'media_sites' => 'fnMediaSites',
		'mustache' => 'fnMustache',
		'number' => 'fnNumber',
		'named_colors' => 'fnNamedColors',
		'page_description' => 'fnPageDescription',
		'page_h1' => 'fnPageH1',
		'page_nav' => 'fnPageNav',
		'page_title' => 'fnPageTitle',
		'parens' => 'fnParens',
		'prefix' => 'fnPrefix',
		'prefix_group' => 'fnPrefixGroup',
		'prefix_title' => 'fnPrefixTitle',
		'property' => 'fnProperty',
		'rand' => 'fnRand',
		'range' => 'fnRange',
		'redirect_input' => 'fnRedirectInput',
		'repeat' => 'fnRepeat',
		'repeat_raw' => 'fnRepeatRaw',
		'show_ignored' => 'fnShowIgnored',
		'smilie' => 'fnSmilie',
		'snippet' => 'fnSnippet',
		'strlen' => 'fnStrlen',
		'structured_text' => 'fnStructuredText',
		'templater' => 'fnTemplater',
		'time' => 'fnTime',
		'trim' => 'fnTrim',
		'unique_id' => 'fnUniqueId',
		'user_activity' => 'fnUserActivity',
		'user_banners' => 'fnUserBanners',
		'user_blurb' => 'fnUserBlurb',
		'user_title' => 'fnUserTitle',
		'username_link' => 'fnUsernameLink',
		'username_link_email' => 'fnUsernameLinkEmail',
		'widget_data' => 'fnWidgetData'
	];

	protected $defaultTests = [
		'empty' => 'testEmpty'
	];

	public function __construct(App $app, Language $language, $compiledPath)
	{
		$this->app = $app;
		$this->language = $language;
		$this->compiledPath = $compiledPath;

		$this->router = $app->router();
		$this->pather = $app->container('request.pather');
		$this->uniqueIdFormat = '_xfUid-%s-' . \XF::$time;
	}

	public function getTemplateFilePath($type, $name, $styleIdOverride = null)
	{
		return $this->compiledPath
			. '/l' . $this->language->getId()
			. '/s' . intval($styleIdOverride !== null ? $styleIdOverride : $this->styleId)
			. '/' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $type)
			. '/' .  preg_replace('/[^a-zA-Z0-9_.-]/', '', $name) . '.php';
	}

	protected function getTemplateDataFromSource($type, $name)
	{
		$file = $this->getTemplateFilePath($type, $name);
		if (!file_exists($file))
		{
			return false;
		}

		return include($file);
	}

	public function getRouter()
	{
		if ($this->currentTemplateType && $this->currentTemplateType != $this->routerType)
		{
			$container = $this->app->container();
			$type = $this->currentTemplateType;

			/** @var \XF\Mvc\Router|null $router */
			$router = isset($container['router.' . $type]) ? $container['router.' . $type] : null;
			if ($router)
			{
				$this->router = $router;
				$this->routerType = $type;
			}
		}

		return $this->router;
	}

	public function getCssLoadUrl(array $templates)
	{
		$url = 'css.php?css='
			. urlencode(implode(',', $templates))
			. '&s=' . $this->styleId
			. '&l=' . $this->language->getId()
			. '&d=' . ($this->style ? $this->style->getLastModified() : \XF::$time);

		$pather = $this->pather;
		return $pather($url, 'base');
	}

	public function getJsUrl($js)
	{
		if (preg_match('#^(/|[a-z]+:)#i', $js))
		{
			return $js;
		}

		if (!strpos($js, '_v='))
		{
			$js = $js . (strpos($js, '?') ? '&' : '?') . $this->getJsCacheBuster();
		}

		$pather = $this->pather;
		return $pather("js/$js", 'base');
	}

	public function getJsCacheBuster()
	{
		return '_v=' . $this->jsVersion;
	}

	public function getDevJsUrl($addOnId, $js)
	{
		$url = 'js/devjs.php?addon_id=' . urlencode($addOnId) . '&js=' . urlencode($js);

		$pather = $this->pather;
		return $pather($url, 'base');
	}

	public function setLanguage(Language $language)
	{
		$this->language = $language;
	}

	public function getLanguage()
	{
		return $this->language;
	}

	public function setStyle(\XF\Style $style)
	{
		$this->style = $style;
		$this->styleId = $style->getId();
	}

	public function getStyle()
	{
		return $this->style;
	}

	public function getStyleId()
	{
		return $this->styleId;
	}

	public function setJquerySource($version, $jQuerySource = null)
	{
		$this->jQueryVersion = $version;
		$this->jQuerySource = $jQuerySource ?: $this->app->options()->jQuerySource;
	}

	public function setJsVersion($version)
	{
		$this->jsVersion = $version;
	}

	public function setDynamicDefaultAvatars($dynamic)
	{
		$this->dynamicDefaultAvatars = $dynamic;
	}

	public function setMediaSites(array $mediaSites)
	{
		$this->mediaSites = $mediaSites;
	}

	public function setUserTitleLadder(array $ladder, $titleField = '')
	{
		$this->userTitleLadder = $ladder;
		if ($titleField)
		{
			$this->userTitleLadderField = $titleField;
		}
	}

	public function setUserBanners(array $banners, array $config = [])
	{
		$this->userBanners = $banners;
		if ($config)
		{
			$this->userBannerConfig = $config;
		}
	}

	public function setGroupStyles(array $styles)
	{
		$this->groupStyles = $styles;
	}

	public function setWidgetPositions(array $widgetPositions)
	{
		$this->widgetPositions = $widgetPositions;
	}

	public function addDefaultHandlers()
	{
		$this->addFilters($this->defaultFilters);
		$this->addFunctions($this->defaultFunctions);
		$this->addTests($this->defaultTests);
	}

	public function addFilters(array $filters)
	{
		$this->filters = array_merge($this->filters, $filters);
	}

	public function addFilter($name, $filter)
	{
		$this->filters[$name] = $filter;
	}

	public function addFunctions(array $functions)
	{
		$this->functions = array_merge($this->functions, $functions);
	}

	public function addFunction($name, $function)
	{
		$this->functions[$name] = $function;
	}

	public function addTests(array $tests)
	{
		$this->tests = array_merge($this->tests, $tests);
	}

	public function addTest($name, $test)
	{
		$this->tests[$name] = $test;
	}

	public function addDefaultParams(array $params)
	{
		$this->defaultParams = array_merge($this->defaultParams, $params);
	}

	public function addDefaultParam($name, $value)
	{
		$this->defaultParams[$name] = $value;
	}

	public function getTemplate($name, array $params = [])
	{
		return new Template($this, $name, $params);
	}


	public function hasWatcherActionedTemplates()
	{
		foreach ($this->watchers AS $watcher)
		{
			if ($watcher->hasActionedTemplates())
			{
				return true;
			}
		}

		return false;
	}

	public function getTemplateTypeAndName($template)
	{
		$parts = explode(':', $template, 2);
		if (count($parts) == 2)
		{
			return [$parts[0], $parts[1]];
		}
		else
		{
			return [$this->currentTemplateType, $parts[0]];
		}
	}

	public function applyDefaultTemplateType($template)
	{
		list($type, $template) = $this->getTemplateTypeAndName($template);

		if ($type)
		{
			$template = "$type:$template";
		}

		return $template;
	}

	/**
	 * @param string $type
	 * @param string $template
	 *
	 * @return \Closure
	 */
	public function getTemplateCode($type, $template)
	{
		$data = $this->getTemplateData($type, $template);
		return $data['code'];
	}

	/**
	 * @param string $type
	 * @param string $template
	 * @param string $macro
	 *
	 * @return \Closure
	 */
	public function getTemplateMacro($type, $template, $macro)
	{
		$data = $this->getTemplateData($type, $template);
		if (isset($data['macros'][$macro]))
		{
			return $data['macros'][$macro];
		}

		trigger_error("Macro $type:$template:$macro is unknown", E_USER_WARNING);
		return function() { return ''; };
	}

	protected function getTemplateData($type, $template)
	{
		if (isset($this->templateCache[$type][$template]))
		{
			return $this->templateCache[$type][$template];
		}

		if (preg_match('#[^a-zA-Z0-9_.-]#', $template))
		{
			throw new \InvalidArgumentException("Template name '$template' contains invalid characters");
		}

		foreach ($this->watchers AS $watcher)
		{
			$watcher->watchTemplate($this, $type, $template);
		}

		$data = $this->getTemplateDataFromSource($type, $template);
		if (!$data || !is_array($data) || !isset($data['code']))
		{
			trigger_error("Template $type:$template is unknown", E_USER_WARNING);
			$data = [
				'macros' => [],
				'code' => function() { return ''; }
			];
		}

		$this->templateCache[$type][$template] = $data;

		return $data;
	}

	public function callAdsMacro($position, array $arguments, array $globalVars)
	{
		$templateData = $this->getTemplateData('public', '_ads');
		if (!isset($templateData['macros'][$position]))
		{
			return '';
		}
		else
		{
			return $this->callMacro('public:_ads', $position, $arguments, $globalVars);
		}
	}

	public function callMacro($template, $name, array $arguments, array $globalVars)
	{
		if ($this->executionDepth >= self::MAX_EXECUTION_DEPTH)
		{
			trigger_error('Max template execution depth reached', E_USER_WARNING);
			return '';
		}

		if (!$template)
		{
			$template = $this->currentTemplateName;
			$type = $this->currentTemplateType;
		}
		else
		{
			list($type, $template) = $this->getTemplateTypeAndName($template);
		}

		if (!$type)
		{
			trigger_error('No template type was provided. Provide template name in type:name format.', E_USER_WARNING);
			return '';
		}

		$this->app->fire(
			'templater_macro_pre_render',
			[$this, &$type, &$template, &$name, &$arguments, &$globalVars],
			"$type:$template:$name"
		);

		$currentType = $this->currentTemplateType;
		$currentName = $this->currentTemplateName;
		$origWrapTemplateName = $this->wrapTemplateName;
		$origWrapTemplateParams = $this->wrapTemplateParams;

		$this->currentTemplateType = $type;
		$this->currentTemplateName = $template;
		$this->wrapTemplateName = null;
		$this->wrapTemplateParams = null;
		$this->executionDepth++;

		set_error_handler([$this, 'handleTemplateError']);

		try
		{
			$macro = $this->getTemplateMacro($type, $template, $name);
			$output = $macro($this, $arguments, $globalVars);
		}
		catch (\Exception $e)
		{
			$errorPrefix = "$this->currentTemplateType:$this->currentTemplateName :: $name()";
			$this->app->logException($e, false, "Macro $errorPrefix error: ");

			if (\XF::$debugMode)
			{
				$message = $e->getMessage();
				$file = $e->getFile() . ':' . $e->getLine();
				$error = $e instanceof \XF\PrintableException ?
					"$errorPrefix - $message"
					: "$errorPrefix - $message in $file";

				if (preg_match('/\.(css|less)$/i', $template))
				{
					$error = strtr($error, [
						"'" => '',
						'\\' => '/',
						"\r" => '',
						"\n" => " "
					]);

					$output = "
						/** Error output **/
						body:before
						{
							background-color: #ccc;
							color: black;
							font-weight: bold;
							display: block;
							padding: 10px;
							margin: 10px;
							border: solid 1px #aaa;
							border-radius: 5px;
							content: 'CSS error: " . $error . "';
						}
					";
				}
				else
				{
					$output = '<div class="error"><h3>Template Compilation Error</h3>'
						. '<div>' . htmlspecialchars($error) . '</div></div>';
				}
			}
			else
			{
				$output = '';
			}
		}

		restore_error_handler();

		if ($this->wrapTemplateName)
		{
			$output = $this->applyWrappedTemplate($output);
		}

		$this->currentTemplateName = $currentName;
		$this->currentTemplateType = $currentType;
		$this->wrapTemplateName = $origWrapTemplateName;
		$this->wrapTemplateParams = $origWrapTemplateParams;
		$this->executionDepth--;

		$this->app->fire(
			'templater_macro_post_render',
			[$this, $type, $template, &$name, &$output],
			"$type:$template:$name"
		);

		return $output;
	}

	public function renderMacro($template, $name, array $arguments = [])
	{
		return $this->callMacro($template, $name, $arguments, $this->defaultParams);
	}

	public function setupBaseParamsForMacro(array $parentVars, $isGlobal = false)
	{
		if (isset($parentVars['__globals']))
		{
			$globalVars = $parentVars['__globals'];
		}
		else
		{
			$globalVars = $parentVars;
		}

		$params = $isGlobal ? $globalVars : $this->defaultParams;
		$params['__globals'] = $globalVars;

		return $params;
	}

	public function mergeMacroArguments(array $expected, array $provided, array $baseParams)
	{
		foreach ($expected AS $argument => $value)
		{
			if (array_key_exists($argument, $provided))
			{
				$baseParams[$argument] = $provided[$argument];
			}
			else if ($value === '!')
			{
				throw new \LogicException("Macro argument $argument is required and no value was provided");
			}
			else
			{
				$baseParams[$argument] = $value;
			}
		}

		return $baseParams;
	}

	public function extractIntoVarContainer(array &$varContainer, $source)
	{
		if (!$this->isTraversable($source))
		{
			return;
		}

		foreach ($source AS $k => $v)
		{
			$varContainer[$k] = $v;
		}
	}

	public function wrapTemplate($template, array $params)
	{
		$template = $this->applyDefaultTemplateType($template);

		$this->wrapTemplateName = $template;
		$this->wrapTemplateParams = $params;
	}

	protected function applyWrappedTemplate($content)
	{
		if (!$this->wrapTemplateName)
		{
			return $content;
		}

		$template = $this->wrapTemplateName;
		$params = $this->wrapTemplateParams;

		$this->wrapTemplateName = null;
		$this->wrapTemplateParams = null;

		$params['innerContent'] = $this->preEscaped($content, 'html');

		return $this->renderTemplate($template, $params, false);
	}

	public function filter($value, array $filters, $escape = true)
	{
		foreach ($filters AS $filter)
		{
			list($name, $arguments) = $filter;
			$name = strtolower($name);
			if (!isset($this->filters[$name]))
			{
				trigger_error("Filter $name is unknown", E_USER_WARNING);
				continue;
			}

			$callable = $this->filters[$name];
			if (is_string($callable))
			{
				$callable = [$this, $callable];
			}

			if ($arguments)
			{
				array_unshift($arguments, null);
				array_unshift($arguments, $value);
				array_unshift($arguments, $this);
				$arguments[2] =& $escape;
			}
			else
			{
				$arguments = [$this, $value, &$escape];
			}

			$value = call_user_func_array($callable, $arguments);
		}

		return $escape ? $this->escape($value, $escape) : $value;
	}

	public function fn($name, array $arguments = [], $escape = true)
	{
		$name = strtolower($name);
		if (!isset($this->functions[$name]))
		{
			trigger_error("Function $name is unknown", E_USER_WARNING);
			return '';
		}

		$callable = $this->functions[$name];
		if (is_string($callable))
		{
			$callable = [$this, $callable];
		}

		if ($arguments)
		{
			array_unshift($arguments, null);
			array_unshift($arguments, $this);
			$arguments[1] =& $escape;
		}
		else
		{
			$arguments = [$this, &$escape];
		}

		$value = call_user_func_array($callable, $arguments);
		return $escape ? $this->escape($value) : $value;
	}

	public function test($value, $test, array $arguments = [])
	{
		if (!isset($this->tests[$test]))
		{
			trigger_error("Test $test is unknown", E_USER_WARNING);
			return false;
		}

		$callable = $this->tests[$test];
		if (is_string($callable))
		{
			$callable = [$this, $callable];
		}

		if ($arguments)
		{
			array_unshift($arguments, $value);
			array_unshift($arguments, $this);
		}
		else
		{
			$arguments = [$this, $value];
		}

		return (bool)call_user_func_array($callable, $arguments);
	}

	public function arrayKey($var, $key)
	{
		return $var[$key];
	}

	public function isA($object, $class)
	{
		return ($object instanceof $class);
	}

	public function method($var, $fn, array $arguments = [])
	{
		if (!is_object($var))
		{
			$type = gettype($var);
			trigger_error("Cannot call method $fn on a non-object ($type)", E_USER_WARNING);
			return '';
		}

		$call = [$var, $fn];

		if (!is_callable($call))
		{
			$class = get_class($var);
			trigger_error("Method $fn is not callable on the given object ($class)", E_USER_WARNING);
			return '';
		}

		return call_user_func_array($call, $arguments);
	}

	public function escape($value, $type = null)
	{
		if ($type === null || $type === true)
		{
			$type = $this->escapeContext;
		}
		return \XF::escapeString($value, $type);
	}

	public function modifySectionedHtml(array &$ref, $key, $html, $mode = 'replace')
	{
		if ($mode == 'delete')
		{
			if ($key)
			{
				unset($ref[$key]);
			}
			return;
		}

		$html = trim($html);
		if (!strlen($html))
		{
			return;
		}

		$html = $this->preEscaped($html, 'html');

		switch ($mode)
		{
			case 'prepend':
				if ($key)
				{
					$ref = [$key => $html] + $ref;
				}
				else
				{
					array_unshift($ref, $html);
				}
				break;

			case 'append':
				if ($key)
				{
					unset($ref[$key]); // unset to ensure this goes at the end
					$ref[$key] = $html;
				}
				else
				{
					$ref[] = $html;
				}
				break;

			case 'replace':
			default:
				if ($key)
				{
					$ref[$key] = $html;
				}
				else
				{
					$ref[] = $html;
				}
				break;
		}
	}

	public function modifySidebarHtml($key, $html, $mode = 'replace')
	{
		$this->modifySectionedHtml($this->sidebar, $key, $html, $mode);
	}

	public function getSidebarHtml()
	{
		return $this->sidebar;
	}

	public function modifySideNavHtml($key, $html, $mode = 'replace')
	{
		$this->modifySectionedHtml($this->sideNav, $key, $html, $mode);
	}

	public function getSideNavHtml()
	{
		return $this->sideNav;
	}

	public function includeCss($css)
	{
		list($type, $template) = $this->getTemplateTypeAndName($css);
		if (!$type)
		{
			trigger_error('No template type was provided. Provide template name in type:name format.', E_USER_WARNING);
			return;
		}

		$this->includeCss["$type:$template"] = true;
	}

	public function getIncludedCss(array $forceAppend = [])
	{
		$css = array_keys($this->includeCss);
		sort($css);
		return array_merge($css, $forceAppend);
	}

	public function inlineCss($css)
	{
		$this->inlineCss[] = $css;
	}

	public function getInlineCss()
	{
		return $this->inlineCss;
	}

	public function includeJs(array $options)
	{
		$options = array_replace([
			'src' => null,
			'addon' => null,
			'min' => null,
			'dev' => null,
			'prod' => null
		], $options);

		$developmentConfig = $this->app->config('development');
		$productionMode = empty($developmentConfig['fullJs']);

		$src = $this->splitJsSrc($options['src']);

		if ($productionMode)
		{
			if ($options['min'])
			{
				$src = array_map(function($path)
				{
					return preg_replace('(\.js$)', '.min.js', $path, 1);
				}, $src);
			}

			$prod = $this->splitJsSrc($options['prod']);
			$src = array_merge($src, $prod);

			foreach ($src AS $path)
			{
				$url = $this->getJsUrl($path);
				$this->includeJs[$url] = true;
			}
		}
		else
		{
			$dev = $this->splitJsSrc($options['dev']);
			$src = array_merge($src, $dev);

			if ($options['addon'])
			{
				foreach ($src AS $path)
				{
					$url = $this->getDevJsUrl($options['addon'], $path);
					$this->includeJs[$url] = true;
				}
			}
			else
			{
				foreach ($src AS $path)
				{
					$url = $this->getJsUrl($path);
					$this->includeJs[$url] = true;
				}
			}
		}
	}

	protected function splitJsSrc($js)
	{
		if ($js)
		{
			return preg_split('/[, ]/', $js, -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			return [];
		}
	}

	public function getIncludedJs()
	{
		return array_keys($this->includeJs);
	}

	public function inlineJs($js)
	{
		$this->inlineJs[] = $js;
	}

	public function getInlineJs()
	{
		return $this->inlineJs;
	}

	public function isTraversable($value)
	{
		return is_array($value) || ($value instanceof \Traversable);
	}

	public function isArrayAccessible($value)
	{
		return is_array($value) || ($value instanceof \ArrayAccess);
	}

	public function handleTemplateError($errorType, $errorString, $file, $line)
	{
		if ($errorType == E_NOTICE || $errorType == E_USER_NOTICE)
		{
			return;
		}

		if ($errorType & error_reporting())
		{
			$this->templateErrors[] = [
				'template' => $this->currentTemplateType . ':' . $this->currentTemplateName,
				'type' => $errorType,
				'error' => $errorString,
				'file' => $file,
				'line' => $line
			];

			$e = new \ErrorException($errorString, 0, $errorType, $file, $line);
			$this->app->logException($e, false, "Template error: ");
		}
	}

	public function getTemplateErrors()
	{
		return $this->templateErrors;
	}

	/**
	 * @param string $template
	 * @param array $params
	 * @param bool $addDefaultParams
	 *
	 * @return string
	 */
	public function renderTemplate($template, array $params = [], $addDefaultParams = true)
	{
		if ($this->executionDepth >= self::MAX_EXECUTION_DEPTH)
		{
			trigger_error('Max template execution depth reached', E_USER_WARNING);
			return '';
		}

		if ($addDefaultParams)
		{
			$params = array_merge($this->defaultParams, $params);
		}

		$type = false;

		if (strpos($template, ':') !== false)
		{
			list($type, $template) = explode(':', $template, 2);
		}

		if (!$type)
		{
			trigger_error('No template type was provided. Provide template name in type:name format.', E_USER_WARNING);
			return '';
		}

		$this->app->fire('templater_template_pre_render', [$this, &$type, &$template, &$params], "$type:$template");

		$currentType = $this->currentTemplateType;
		$currentName = $this->currentTemplateName;
		$origWrapTemplateName = $this->wrapTemplateName;
		$origWrapTemplateParams = $this->wrapTemplateParams;

		$this->currentTemplateType = $type;
		$this->currentTemplateName = $template;
		$this->wrapTemplateName = null;
		$this->wrapTemplateParams = null;
		$this->executionDepth++;

		set_error_handler([$this, 'handleTemplateError']);

		try
		{
			$code = $this->getTemplateCode($type, $template);
			$output = $code($this, $params);
		}
		catch (\Exception $e)
		{
			$errorPrefix = "$this->currentTemplateType:$this->currentTemplateName";
			$this->app->logException($e, false, "Template $errorPrefix error: ");

			if (\XF::$debugMode)
			{
				$message = $e->getMessage();
				$file = $e->getFile() . ':' . $e->getLine();
				$error = $e instanceof \XF\PrintableException ?
					"$errorPrefix - $message"
					: "$errorPrefix - $message in $file";

				if (preg_match('/\.(css|less)$/i', $template))
				{
					$error = strtr($error, [
						"'" => '',
						'\\' => '/',
						"\r" => '',
						"\n" => " "
					]);

					$output = "
						/** Error output **/
						body:before
						{
							background-color: #ccc;
							color: black;
							font-weight: bold;
							display: block;
							padding: 10px;
							margin: 10px;
							border: solid 1px #aaa;
							border-radius: 5px;
							content: 'CSS error: " . $error . "';
						}
					";
				}
				else
				{
					$output = '<div class="error"><h3>Template Compilation Error</h3>'
						. '<div>' . htmlspecialchars($error) . '</div></div>';
				}
			}
			else
			{
				$output = '';
			}
		}

		restore_error_handler();

		if ($this->wrapTemplateName)
		{
			$output = $this->applyWrappedTemplate($output);
		}

		$this->currentTemplateType = $currentType;
		$this->currentTemplateName = $currentName;
		$this->wrapTemplateName = $origWrapTemplateName;
		$this->wrapTemplateParams = $origWrapTemplateParams;
		$this->executionDepth--;

		$this->app->fire('templater_template_post_render', [$this, $type, $template, &$output], "$type:$template");

		return $output;
	}

	public function includeTemplate($template, array $params = [])
	{
		$template = $this->applyDefaultTemplateType($template);

		return $this->renderTemplate($template, $params);
	}

	public function callback($class, $method, $contents, array $params = [])
	{
		if (!\XF\Util\Php::validateCallbackPhrased($class, $method, $errorPhrase))
		{
			return $errorPhrase;
		}
		if (!\XF\Util\Php::nameIndicatesReadOnly($method))
		{
			return \XF::phrase('callback_method_x_does_not_appear_to_indicate_read_only', ['method' => $method]);
		}

		ob_start();
		$output = call_user_func([$class, $method], $contents, $params, $this);
		$output .= ob_get_clean();

		return $output;
	}

	public function setPageParams(array $pageParams)
	{
		$this->pageParams = Arr::mapMerge($this->pageParams, $pageParams);
	}

	public function setPageParam($name, $value)
	{
		if (strpos($name, '.') === false)
		{
			$this->pageParams[$name] = $value;
			return;
		}

		$ref =& $this->pageParams;
		$hasValid = false;
		foreach (explode('.', $name) AS $part)
		{
			if (!strlen($part))
			{
				continue;
			}

			if (!isset($ref[$part]) || !is_array($ref[$part]))
			{
				$ref[$part] = [];
			}

			$ref =& $ref[$part];
			$hasValid = true;
		}

		if ($hasValid)
		{
			$ref = $value;
		}
	}

	public function breadcrumb($value, $href, array $config)
	{
		if (!isset($this->pageParams['breadcrumbs']) || !is_array($this->pageParams['breadcrumbs']))
		{
			$this->pageParams['breadcrumbs'] = [];
		}

		$crumb = [
			'value' => $value,
			'href' => $href,
			'attributes' => $config
		];

		$this->pageParams['breadcrumbs'][] = $crumb;
	}

	public function breadcrumbs(array $crumbs)
	{
		if (!$crumbs)
		{
			$this->pageParams['breadcrumbs'] = [];
			return;
		}

		foreach ($crumbs AS $key => $crumb)
		{
			if (is_string($crumb) || $crumb instanceof \XF\Phrase)
			{
				$crumb = [
					'href' => $key,
					'value' => $crumb
				];
			}

			if (!is_array($crumb))
			{
				trigger_error("Each breadcrumb must be an array", E_USER_WARNING);
				continue;
			}
			if (!isset($crumb['value']))
			{
				trigger_error("Each breadcrumb provide a 'value' key", E_USER_WARNING);
				continue;
			}
			if (!isset($crumb['href']))
			{
				trigger_error("Each breadcrumb provide a 'href' key", E_USER_WARNING);
				continue;
			}

			$value = $crumb['value'];
			$href = $crumb['href'];
			unset($crumb['value'], $crumb['href']);

			$this->breadcrumb($value, $href, $crumb);
		}
	}

	public function button($contentHtml, array $options, $menuHtml = '', array $menuOptions = [])
	{
		$href = $this->processAttributeToRaw($options, 'href', '', true);
		if ($href)
		{
			$element = 'a';
			$type = '';
			$href = ' href="' . $href . '"';
		}
		else
		{
			$element = 'button';
			$type = $this->processAttributeToRaw($options, 'type', '', true);
			if ($type)
			{
				$type = ' type="' . $type . '"';
			}
			else
			{
				$type = ' type="button"';
			}
		}

		$overlay = $this->processAttributeToRaw($options, 'overlay', '', true);
		if ($overlay)
		{
			$overlay = " data-xf-click=\"overlay\"";
		}

		$buttonClasses = 'button';
		$icon = $this->processAttributeToRaw($options, 'icon');
		if ($icon)
		{
			$buttonClasses .= ' button--icon button--icon--' . preg_replace('#[^a-zA-Z0-9_-]#', '', $icon);
		}

		if ($menuHtml)
		{
			$buttonClasses .= ' button--splitTrigger';

			$menuClass = $this->processAttributeToRaw($menuOptions, 'class', ' %s', true);
			$unhandledMenuAttrs = $this->processUnhandledAttributes($menuOptions);

			$menuHtml = "<div class=\"menu{$menuClass}\" data-menu=\"menu\" aria-hidden=\"true\"{$unhandledMenuAttrs}>{$menuHtml}</div>";
		}

		$classAttr = $this->processAttributeToHtmlAttribute($options, 'class', $buttonClasses, true);

		$button = strval($this->processAttributeToRaw($options, 'button'));
		if (!$button)
		{
			$button = $contentHtml;
		}
		if (!$button && $icon)
		{
			$button = $this->getButtonPhraseFromIcon($icon);
		}

		$unhandledControlAttrs = $this->processUnhandledAttributes($options);

		if ($menuHtml)
		{
			return "<span{$classAttr}><{$element}{$type}{$href} class=\"button-text\">{$button}</{$element}>"
				. "<a class=\"button-menu\" data-xf-click=\"menu\" aria-expanded=\"false\" aria-haspopup=\"true\"></a>"
				. $menuHtml
				. "</span>";
		}
		else
		{
			return "<{$element}{$type}{$href}{$classAttr}{$overlay}{$unhandledControlAttrs}><span class=\"button-text\">{$button}</span></{$element}>";
		}
	}

	public function widgetPosition($positionId, array $contextParams = [])
	{
		$widgetPositions = $this->widgetPositions;
		if (!isset($widgetPositions[$positionId]))
		{
			return '';
		}
		$widgetContainer = $this->app->widget();
		$widgets = $widgetContainer->position($positionId, $contextParams);

		$output = '';
		foreach ($widgets AS $widget)
		{
			$output .= $widget->render() . "\n";
		}
		return $output;
	}

	public function renderWidget($identifier, array $options = [], array $contextParams = [])
	{
		$options['context'] = $contextParams;
		$widget = $this->app->widget()->widget($identifier, $options);
		return $widget->render();
	}

	public function preEscaped($value, $type = null)
	{
		if ($type === null)
		{
			$type = $this->escapeContext;
		}
		return new \XF\PreEscaped($value, $type);
	}

	////////////////////// FUNCTIONS ////////////////////////

	public function fnAnchorTarget($templater, &$escape, $hash)
	{
		$escape = false;
		return '<span class="u-anchorTarget" id="' . htmlspecialchars($this->app->getRedirectHash($hash)) . '"></span>';
	}

	public function fnArrayKeys($templater, &$escape, $array)
	{
		if (!is_array($array))
		{
			$array = [];
		}

		return array_keys($array);
	}

	public function fnArrayMerge($templater, &$escape, $array)
	{
		$arrays = func_get_args();
		unset($arrays[0]);
		unset($arrays[1]);

		return call_user_func_array('array_merge', $arrays);
	}

	public function fnArrayValues($templater, &$escape, $array)
	{
		if (!is_array($array))
		{
			$array = [];
		}

		return array_values($array);
	}

	public function fnAttributes($templater, &$escape, $attributes, array $skipAttrs = [])
	{
		if (is_array($attributes))
		{
			foreach ($skipAttrs AS $attr)
			{
				unset($attributes[$attr]);
			}
			$output = $this->processUnhandledAttributes($attributes);
		}
		else
		{
			$output = '';
		}

		$escape = false;
		return $output;
	}

	public function fnAvatar($templater, &$escape, $user, $size, $canonical = false, $attributes = [])
	{
		$escape = false;
		$forceType = $this->processAttributeToRaw($attributes, 'forcetype', '', true);
		$noTooltip = $this->processAttributeToRaw($attributes, 'notooltip', '', false);
		$update = $this->processAttributeToRaw($attributes, 'update', '');

		if ($user instanceof \XF\Entity\User)
		{
			$username = $user->username;
			if (isset($attributes['href']))
			{
				$href = $attributes['href'];
				$noTooltip = true;
			}
			else
			{
				$linkPath = $this->currentTemplateType == 'admin' ? 'users/edit' : 'members';
				$href = $this->getRouter()->buildLink(($canonical ? 'canonical:' : '') . $linkPath, $user);

				if ($this->currentTemplateType == 'admin')
				{
					$noTooltip = true;
				}
			}
			$hrefAttr = $href ? ' href="' . htmlspecialchars($href) . '"' : '';
			$userId = $user->user_id;
			$avatarType = $forceType ?: $user->getAvatarType();

			$canUpdate = ((bool)$update && $user->user_id == \XF::visitor()->user_id && $user->canUploadAvatar());
		}
		else
		{
			if (isset($attributes['defaultname']))
			{
				$username = $attributes['defaultname'];
			}
			else
			{
				$username = null;
			}
			$hrefAttr = '';
			$noTooltip = true;
			$userId = 0;
			$avatarType = 'default';
			$canUpdate = false;
		}

		switch ($avatarType)
		{
			case 'gravatar':
			case 'custom':
				$src = $user->getAvatarUrl($size, $forceType, $canonical);
				break;

			case 'default':
			default:
				$src = null;
				break;
		}

		$actualSize = $size;
		if (!array_key_exists($size, $this->app->container('avatarSizeMap')))
		{
			$actualSize = 's';
		}

		$sizeClass = "avatar-u{$userId}-{$actualSize}";
		$innerClass = $this->processAttributeToRaw($attributes, 'innerclass', ' %s', true);
		$innerClassHtml = $sizeClass . $innerClass;

		if ($src && $forceType != 'default')
		{
			$srcSet = $user->getAvatarUrl2x($size, $forceType, $canonical);

			$itemprop = $this->processAttributeToRaw($attributes, 'itemprop', '%s', true);

			$innerContent = '<img src="' . $src . '" ' . (!empty($srcSet) ? 'srcset="' . $srcSet . ' 2x"' : '')
				. ' alt="' . htmlspecialchars($username) . '"'
				. ' class="' . $innerClassHtml . '"'
				. ($itemprop ? ' itemprop="' . $itemprop . '"' : '')
				. ' />';
		}
		else
		{
			$innerContent = $this->getDynamicAvatarHtml($username, $innerClassHtml, $attributes);
		}

		$updateLink = '';
		$updateLinkClass = '';
		if ($canUpdate)
		{
			$updateLinkClass = 'avatar--updateLink';
			$updateLink = '<div class="avatar-update">
				<a href="' . htmlspecialchars($update) . '" data-xf-click="overlay">' . \XF::phrase('edit_avatar') . '</a>
			</div>';
		}

		$class = $this->processAttributeToRaw($attributes, 'class', ' %s', true);
		$xfInit = $this->processAttributeToRaw($attributes, 'data-xf-init', '', true);

		if (!$noTooltip)
		{
			$xfInit = ltrim("$xfInit member-tooltip");
		}
		$xfInitAttr = $xfInit ? " data-xf-init=\"$xfInit\"" : '';

		unset($attributes['defaultname'], $attributes['href'], $attributes['itemprop']);

		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		if ($hrefAttr)
		{
			$tag = 'a';
		}
		else
		{

			$tag = 'span';
		}
		return "<{$tag}{$hrefAttr} class=\"avatar avatar--{$size} {$updateLinkClass} {$class}\" data-user-id=\"{$userId}\"{$xfInitAttr}{$unhandledAttrs}>
			$innerContent $updateLink
		</{$tag}>";
	}

	protected function getDynamicAvatarHtml($username, $innerClassHtml, array &$outerAttributes)
	{
		if ($username && $this->dynamicDefaultAvatars)
		{
			return $this->getDefaultAvatarHtml($username, $innerClassHtml, $outerAttributes);
		}
		else
		{
			return $this->getFallbackAvatarHtml($innerClassHtml, $outerAttributes);
		}
	}

	protected function getDefaultAvatarHtml($username, $innerClassHtml, array &$outerAttributes)
	{
		$styling = $this->getDefaultAvatarStyling($username);

		if (empty($outerAttributes['style']))
		{
			$outerAttributes['style'] = '';
		}
		else
		{
			$outerAttributes['style'] .= '; ';
		}
		$outerAttributes['style'] .= "background-color: $styling[bgColor]; color: $styling[color]";

		return '<span class="' . $innerClassHtml . '">' . $styling['innerContent'] . '</span>';
	}

	protected function getDefaultAvatarStyling($username)
	{
		if (!isset($this->avatarDefaultStylingCache[$username]))
		{
			$bytes = md5($username, true);
			$r = dechex(round(5 * ord($bytes[0]) / 255) * 0x33);
			$g = dechex(round(5 * ord($bytes[1]) / 255) * 0x33);
			$b = dechex(round(5 * ord($bytes[2]) / 255) * 0x33);
			$hexBgColor = sprintf('%02s%02s%02s', $r, $g, $b);

			$hslBgColor = \XF\Util\Color::hexToHsl($hexBgColor);

			$bgChanged = false;
			if ($hslBgColor[1] > 60)
			{
				$hslBgColor[1] = 60;
				$bgChanged = true;
			}
			else if ($hslBgColor[1] < 15)
			{
				$hslBgColor[1] = 15;
				$bgChanged = true;
			}

			if ($hslBgColor[2] > 85)
			{
				$hslBgColor[2] = 85;
				$bgChanged = true;
			}
			else if ($hslBgColor[2] < 15)
			{
				$hslBgColor[2] = 15;
				$bgChanged = true;
			}

			if ($bgChanged)
			{
				$hexBgColor = \XF\Util\Color::hslToHex($hslBgColor);
			}

			$hslColor = \XF\Util\Color::darkenOrLightenHsl($hslBgColor, 35);
			$hexColor = \XF\Util\Color::hslToHex($hslColor);

			$bgColor = '#' . $hexBgColor;
			$color = '#' . $hexColor;

			if (preg_match($this->avatarLetterRegex, $username, $match))
			{
				$innerContent = htmlspecialchars(utf8_strtoupper($match[0]));
			}
			else
			{
				$innerContent = '?';
			}

			$this->avatarDefaultStylingCache[$username] = [
				'bgColor' => $bgColor,
				'color' => $color,
				'innerContent' => $innerContent
			];
		}

		return $this->avatarDefaultStylingCache[$username];
	}

	protected function getFallbackAvatarHtml($innerClassHtml, array &$outerAttributes)
	{
		if (empty($outerAttributes['class']))
		{
			$outerAttributes['class'] = '';
		}
		else
		{
			$outerAttributes['class'] .= ' ';
		}

		$fallbackType = $this->style->getProperty('avatarDefaultType', 'text');
		$outerAttributes['class'] .= 'avatar--default avatar--default--' . $fallbackType;

		return '<span class="' . $innerClassHtml . '"></span>';
	}


	public function fnBaseUrl($templater, &$escape, $url = null, $full = false)
	{
		$pather = $this->pather;
		return $pather($url ?: '', $full ? 'full' : 'base');
	}

	public function fnBbCode($templater, &$escape, $bbCode, $context, $user = null, array $options = [], $type = 'html')
	{
		if ($user !== null)
		{
			$options['user'] = $user;
		}

		$escape = false;
		return $this->app->bbCode()->render($bbCode, $type, $context, $options);
	}

	public function fnButtonIcon($templater, &$escape, $icon)
	{
		$icon = preg_replace('#[^a-zA-Z0-9_-]#', '', strval($icon));
		if (!$icon)
		{
			return '';
		}

		$escape = false;
		return " button--icon button--icon--" . $icon;
	}

	public function fnCallable($templater, &$escape, $var, $fn)
	{
		$escape = false;

		if (!\XF\Util\Php::validateCallback($var, $fn))
		{
			return false;
		}
		if (!\XF\Util\Php::nameIndicatesReadOnly($fn))
		{
			return false;
		}

		return true;
	}

	public function fnCaptcha($templater, &$escape, $force = false)
	{
		if (!$force && !\XF::visitor()->isShownCaptcha())
		{
			return '';
		}

		$captcha = $this->app->captcha();
		if ($captcha)
		{
			$escape = false;
			return $captcha->render($templater);
		}

		return '';
	}

	public function fnCopyright($templater, &$escape)
	{
		$escape = false;
		return \XF::getCopyrightHtml();
	}

	public function fnCoreJs($templater, &$escape)
	{
		$jqVersion = $this->jQueryVersion;
		$jqMin = '.min';
		$jqLocal = $this->getJsUrl("vendor/jquery/jquery-{$jqVersion}{$jqMin}.js");
		$jqRemote = '';

		if ($this->app['app.defaultType'] == 'public')
		{
			switch ($this->jQuerySource)
			{
				case 'jquery':
					$jqRemote = "https://code.jquery.com/jquery-{$jqVersion}{$jqMin}.js";
					break;

				case 'google':
					$jqRemote = "https://ajax.googleapis.com/ajax/libs/jquery/{$jqVersion}/jquery{$jqMin}.js";
					break;

				case 'microsoft':
					$jqRemote = "https://ajax.microsoft.com/ajax/jquery/jquery-{$jqVersion}{$jqMin}.js";
					break;
			}
		}

		if ($jqRemote)
		{
			$output = '<script src="' . htmlspecialchars($jqRemote) . '"></script>'
				. '<script>window.jQuery || document.write(\'<script src="'
				. \XF::escapeString($jqLocal, 'htmljs') . '"><\\/script>\')</script>';
		}
		else
		{
			$output = '<script src="' . htmlspecialchars($jqLocal) . '"></script>';
		}

		$files = [
			'vendor/vendor-compiled.js'
		];
		if ($this->app['config']['development']['fullJs'])
		{
			$files[] = 'xf/core.js';
			foreach (glob(\XF::getRootDirectory() . '/js/xf/core/*.js') AS $file)
			{
				if (substr($file, -7) == '.min.js')
				{
					continue;
				}
				$files[] = 'xf/core/' . basename($file);
			}
		}
		else
		{
			$files[] = 'xf/core-compiled.js';
		}
		foreach ($files AS $file)
		{
			$output .= "\n\t<script src=\"" . htmlspecialchars($this->getJsUrl($file)) . '"></script>';
		}
		$escape = false;
		return $output;
	}

	public function fnCount($templater, &$escape, $value)
	{
		if (is_array($value) || $value instanceof \Countable)
		{
			return count($value);
		}

		return null;
	}

	public function fnCsrfInput($templater, &$escape)
	{
		$escape = false;
		return '<input type="hidden" name="_xfToken" value="' . htmlspecialchars($this->app['csrf.token']) . '" />';
	}

	public function fnCsrfToken($templater, &$escape)
	{
		return $this->app['csrf.token'];
	}

	public function fnCssUrl($templater, &$escape, array $templates)
	{
		return $this->getCssLoadUrl($templates);
	}

	public function fnDate($templater, &$escape, $date, $format = null)
	{
		return $this->language->date($date, $format);
	}

	public function fnDateFromFormat($templater, &$escape, $format, $dateString, $timeZone = null)
	{
		 return \DateTime::createFromFormat($format, $dateString, $timeZone === null
			 ? $this->language->getTimezone()
			 : new \DateTimeZone($timeZone));
	}

	public function fnDateDynamic($templater, &$escape, $dateTime, array $attributes = [])
	{
		if (!($dateTime instanceof \DateTime))
		{
			$ts = intval($dateTime);
			$dateTime = new \DateTime();
			$dateTime->setTimestamp($ts);
			$dateTime->setTimezone($this->language->getTimeZone());
		}
		else
		{
			$ts = $dateTime->getTimestamp();
		}

		list($date, $time) = $this->language->getDateTimeParts($ts);
		$full = $this->language->getDateTimeOutput($date, $time);
		$relative = $this->language->getRelativeDateTimeOutput($ts, $date, $time, !empty($attributes['data-full-old-date']));

		$class = $this->processAttributeToHtmlAttribute($attributes, 'class', 'u-dt', true);

		unset($attributes['title']);
		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		$escape = false;

		return '<time ' . $class . ' dir="auto" datetime="' . $dateTime->format(\DateTime::ISO8601)
			. '" data-time="' . $ts
			. '" data-date-string="' . htmlspecialchars($date)
			. '" data-time-string="' . htmlspecialchars($time)
			. '" title="' . htmlspecialchars($full)
			. '"' . $unhandledAttrs . '>' . htmlspecialchars($relative) . '</time>';
	}

	public function fnDateTime($templater, &$escape, $date)
	{
		return $this->language->dateTime($date);
	}

	public function fnDebugUrl($templater, &$escape, $url = null)
	{
		if (!$url)
		{
			$url = $this->app->request()->getRequestUri();
		}

		if (strpos($url, '?') === false)
		{
			$url .= '?';
		}
		else
		{
			$url .= '&';
		}

		return $url . '_debug=1';
	}

	public function fnDump($templater, &$escape, $value)
	{
		$escape = false;
		ob_start();
		\XF::dump($value);
		$dump = ob_get_clean();

		return $dump;
	}

	public function fnDumpSimple($templater, &$escape, $value)
	{
		$escape = false;
		return \XF::dumpSimple($value, false);
	}

	public function fnDisplayTotals($templater, &$escape, $count, $total = null)
	{
		if (is_array($count) || $count instanceof \Countable)
		{
			$count = count($count);
		}

		if ($total === null)
		{
			$total = $count;
		}
		else if (is_array($total) || $total instanceof \Countable)
		{
			$total = count($total);
		}

		$params = [
			'count' => $this->language->numberFormat($count),
			'total' => $this->language->numberFormat($total)
		];

		if ($count < 1)
		{
			$phrase = 'no_items_to_display';
		}
		else if ($count == $total)
		{
			$phrase = 'showing_all_items';
		}
		else
		{
			$phrase = 'showing_x_of_y_items';
		}

		$escape = false;
		return '<span class="js-displayTotals" data-count="' . $count . '" data-total="' . $total . '"'
			. ' data-xf-init="tooltip" title="' . \XF::phrase('there_are_x_items_in_total', ['total' => $params['total']]) . '">'
			. \XF::phrase($phrase, $params) . '</span>';
	}

	public function fnFileSize($templater, &$escape, $number)
	{
		return $this->language->fileSizeFormat($number);
	}

	public function fnCeil($templater, &$escape, $value)
	{
		return ceil($value);
	}

	public function fnFloor($templater, &$escape, $value)
	{
		return floor($value);
	}

	public function fnGravatarUrl($templater, &$escape, $user, $size)
	{
		if ($user instanceof \XF\Entity\User)
		{
			return $user->getGravatarUrl($size);
		}
		else
		{
			return '';
		}
	}

	public function fnHighlight($templater, &$escape, $string, $term, $class = 'textHighlight')
	{
		$escape = false;
		return $this->app->stringFormatter()->highlightTermForHtml($string, $term, $class);
	}

	public function fnInArray($templater, &$escape, $needle, $haystack, $strict = false)
	{
		$escape = false;
		if ($haystack instanceof \Traversable)
		{
			$haystack = iterator_to_array($haystack);
		}

		if (!is_array($haystack))
		{
			return false;
		}

		return in_array($needle, $haystack, $strict);
	}

	public function fnIsArray($templater, &$escape, $array)
	{
		$escape = false;
		return is_array($array);
	}

	public function fnIsAddonActive($templater, &$escape, $addOnId, $versionId = null, $operator = '>=')
	{
		$addOns = $this->app->registry()['addOns'];

		if (!isset($addOns[$addOnId]))
		{
			return false;
		}

		if ($versionId === null)
		{
			return $addOns[$addOnId];
		}
		else
		{
			$activeVersionId = $addOns[$addOnId];

			switch ($operator)
			{
				case '>':
					return ($activeVersionId > $versionId);
				case '>=':
					return ($activeVersionId >= $versionId);
				case '<':
					return ($activeVersionId < $versionId);
				case '<=':
					return ($activeVersionId <= $versionId);
				default:
					return $addOns[$addOnId];
			}
		}
	}

	public function fnIsEditorCapable($templater, &$escape)
	{
		$ua = $this->app->request()->getUserAgent();
		if (!$ua)
		{
			return true;
		}

		if (preg_match('#blackberry|opera mini|opera mobi#i', $ua))
		{
			// older/limited mobile browsers
			return false;
		}

		if (preg_match('#msie (\d+)#i', $ua, $match) && intval($match[1]) < 10)
		{
			// only supported in IE10+
			return false;
		}

		if (preg_match('#android (\d+)\.#i', $ua, $match) && intval($match[1]) < 4)
		{
			// only supported in Android 4+
			return false;
		}

		if (preg_match('#(iphone|ipod|ipad).+OS (\d+)_#i', $ua, $match) && intval($match[2]) < 7)
		{
			// only supported in iOS 7+
			return false;
		}

		return true;
	}

	public function fnJsUrl($templater, &$escape, $file)
	{
		return $this->getJsUrl($file);
	}

	public function fnLastPages($templater, &$escape, $total, $perPage, $max = 2)
	{
		$escape = false;

		$perPage = intval($perPage);
		if ($perPage <= 0)
		{
			return [];
		}

		$total = intval($total);
		if ($total <= $perPage)
		{
			return [];
		}

		$max = max(1, intval($max));

		$totalPages = ceil($total / $perPage);
		if ($totalPages == 2)
		{
			return [2];
		}

		// + 1 represents that range covers including the start, whereas we want only the last X, which is start + 1
		$start = max($totalPages - $max + 1, 2);
		return range($start, $totalPages);
	}

	public function fnLikes($templater, &$escape, $count, $users, $liked, $url, array $attributes = [])
	{
		$escape = false;

		$count = intval($count);
		if ($count <= 0)
		{
			return '';
		}

		if (!$users || !is_array($users))
		{
			$phrase = ($count > 1 ? 'likes.x_people' : 'likes.1_person');
			return $this->renderTemplate('public:like_list_row', [
				'url' => $url,
				'likes' => \XF::phrase($phrase, ['likes' => $this->language->numberFormat($count)])
			]);
		}

		$userCount = count($users);
		if ($userCount < 5 && $count > $userCount) // indicates some users are deleted
		{
			for ($i = 0; $i < $count; $i++)
			{
				if (empty($users[$i]))
				{
					$users[$i] = [
						'user_id' => 0,
						'username' => \XF::phrase('likes.deleted_user')
					];
				}
			}
		}

		if ($liked)
		{
			$visitorId = \XF::visitor()->user_id;
			foreach ($users AS $key => $user)
			{
				if ($user['user_id'] == $visitorId)
				{
					unset($users[$key]);
					break;
				}
			}

			$users = array_values($users);

			if (count($users) == 3)
			{
				unset($users[2]);
			}
		}

		$user1 = $user2 = $user3 = '';

		if (isset($users[0]))
		{
			$user1 = $users[0]['username'];
			if (isset($users[1]))
			{
				$user2 = $users[1]['username'];
				if (isset($users[2]))
				{
					$user3 = $users[2]['username'];
				}
			}
		}

		switch ($count)
		{
			case 1: $phrase = ($liked ? 'likes.you' : 'likes.user1'); break;
			case 2: $phrase = ($liked ? 'likes.you_and_user1' : 'likes.user1_and_user2'); break;
			case 3: $phrase = ($liked ? 'likes.you_user1_and_user2' : 'likes.user1_user2_and_user3'); break;
			case 4: $phrase = ($liked ? 'likes.you_user1_user2_and_1_other' : 'likes.user1_user2_user3_and_1_other'); break;
			default: $phrase = ($liked ? 'likes.you_user1_user2_and_x_others' : 'likes.user1_user2_user3_and_x_others'); break;
		}

		$params = [
			'user1' => $user1,
			'user2' => $user2,
			'user3' => $user3,
			'others' => $this->language->numberFormat($count - 3)
		];

		return $this->renderTemplate('public:like_list_row', [
			'url' => $url,
			'likes' => \XF::phrase($phrase, $params)
		]);
	}

	public function fnLikesContent($templater, &$escape, $content, $url, array $attributes = [])
	{
		$escape = false;
		if (!($content instanceof \XF\Mvc\Entity\Entity))
		{
			trigger_error("Content must be an entity link likes_content (given " . gettype($content) . ")", E_USER_WARNING);
			return '';
		}

		$count = $content->likes;
		$users = $content->like_users;

		$userId = \XF::visitor()->user_id;
		$liked = $userId ? isset($content->Likes[$userId]) : false;

		return $this->fn('likes', [$count, $users, $liked, $url, $attributes], false);
	}

	public function fnLink($templater, &$escape, $link, $data = null, array $params = [])
	{
		return $this->getRouter()->buildLink($link, $data, $params);
	}

	public function fnLinkType($templater, &$escape, $type, $link, $data = null, array $params = [])
	{
		$container = $this->app->container();

		/** @var \XF\Mvc\Router|null $router */
		$router = isset($container['router.' . $type]) ? $container['router.' . $type] : null;
		if ($router)
		{
			return $router->buildLink($link, $data, $params);
		}
		else
		{
			return '';
		}
	}

	public function fnMaxLength($templater, &$escape, $entity, $column)
	{
		static $entityCache = [];

		// if $entity is not an entity, expect an entity id string like XF:Thread
		if (is_string($entity) && preg_match('/^\w+:\w+$/i', $entity))
		{
			if (!isset($entityCache[$entity]))
			{
				$entityCache[$entity] = $this->app->em()->create($entity);
			}

			$entity = $entityCache[$entity];
		}

		if ($entity instanceof \XF\Mvc\Entity\Entity)
		{
			$maxlength = $entity->getMaxLength($column);

			return $maxlength > 0 ? $maxlength : null;
		}
		else
		{
			return null;
		}
	}

	public function fnMediaSites($templater, &$escape)
	{
		$output = [];
		foreach ($this->mediaSites AS $site)
		{
			if (!$site['supported'])
			{
				continue;
			}
			if ($site['site_url'])
			{
				$output[] = '<a href="' . htmlspecialchars($site['site_url']) . '" target="_blank" rel="nofollow" dir="auto">' . htmlspecialchars($site['site_title']) . '</a>';
			}
			else
			{
				$output[] = htmlspecialchars($site['site_title']);
			}
		}
		$escape = false;
		return implode(', ', $output);
	}

	public function fnMustache($templater, &$escape, $name, $inner = null)
	{
		$escape = false;

		$var = '{{' . $name . '}}';

		if ($inner === null)
		{
			return $var;
		}
		else
		{
			$close = '{{/' . substr($name, 1) . '}}';
			return "{$var}{$inner}{$close}";
		}
	}

	public function fnNumber($templater, &$escape, $number, $precision = 0)
	{
		return $this->language->numberFormat($number, $precision);
	}


	public function fnNamedColors($templater, &$escape)
	{
		return \XF\Util\Color::getNamedColors();
	}

	public function fnPageDescription($templater, &$escape)
	{
		if (isset($this->pageParams['pageDescription']))
		{
			return $this->pageParams['pageDescription'];
		}
		else
		{
			return '';
		}
	}

	public function fnPageH1($templater, &$escape, $fallback = '')
	{
		if (isset($this->pageParams['pageH1']))
		{
			return $this->pageParams['pageH1'];
		}
		else if (isset($this->pageParams['pageTitle']))
		{
			return $this->pageParams['pageTitle'];
		}
		else
		{
			return $fallback;
		}
	}

	public function fnPageNav($templater, &$escape, array $config)
	{
		$escape = false;

		$config = array_merge([
			'pageParam' => 'page',

			'page' => 0,
			'perPage' => 0,
			'total' => 0,
			'range' => 2,

			'template' => $this->applyDefaultTemplateType('page_nav'),
			'variantClass' => '',

			'link' => '',
			'data' => null,
			'params' => [],

			'wrapper' => '',
			'wrapperclass' => '',
		], $config);

		if (!is_array($config['params']))
		{
			$config['params'] = [];
		}

		$perPage = intval($config['perPage']);
		if ($perPage <= 0)
		{
			return '';
		}

		$total = intval($config['total']);
		if ($total <= $perPage)
		{
			return '';
		}

		$totalPages = ceil($total / $perPage);

		$current = intval($config['page']);
		$current = max(1, min($current, $totalPages));

		// number of pages either side of the current page
		$range = intval($config['range']);

		$startInner = max(2, $current - $range);
		$endInner = min($current + $range, $totalPages - 1);

		if ($startInner <= $endInner)
		{
			$innerPages = range($startInner, $endInner);
		}
		else
		{
			$innerPages = [];
		}

		$wrapperClass = $this->processAttributeToRaw($config, 'wrapperclass', '', true);
		$wrapper = $this->processAttributeToRaw($config, 'wrapper');
		if ($wrapperClass && !$wrapper)
		{
			$wrapper = 'div';
		}

		$router = $this->router;

		$prev = false;
		if ($current > 1)
		{
			$prevPageParam = $current - 1;
			if ($prevPageParam <= 1)
			{
				$prevPageParam = null;
			}

			$prev = $router->buildLink($config['link'], $config['data'], $config['params'] + [$config['pageParam'] => $prevPageParam]);
			if (!isset($this->pageParams['head']['prev']))
			{
				$this->pageParams['head']['prev'] = $this->preEscaped('<link rel="prev" href="' . \XF::escapeString($prev) . '" />');
			}
		}

		$next = false;
		if ($current < $totalPages)
		{
			$next = $router->buildLink($config['link'], $config['data'], $config['params'] + [$config['pageParam'] => $current + 1]);
			if (!isset($this->pageParams['head']['next']))
			{
				$this->pageParams['head']['next'] = $this->preEscaped('<link rel="next" href="' . \XF::escapeString($next) . '" />');
			}
		}

		$html = $this->renderTemplate($config['template'], [
			'prev' => $prev,
			'current' => $current,
			'next' => $next,
			'perPage' => $perPage,
			'total' => $total,
			'totalPages' => $totalPages,
			'innerPages' => $innerPages,
			'startInner' => $startInner,
			'endInner' => $endInner,
			'pageParam' => $config['pageParam'],
			'link' => $config['link'],
			'data' => $config['data'],
			'params' => $config['params'],
			'variantClass' => $config['variantClass']
		]);

		if ($wrapper)
		{
			$wrapperOpen = $wrapper . ($wrapperClass ? " class=\"$wrapperClass\"" : '');
			$html = "<{$wrapperOpen}>{$html}</{$wrapper}>";
		}
		return $html;
	}

	public function fnPageTitle($templater, &$escape, $formatter = null, $fallback = '', $page = null)
	{
		if (isset($this->pageParams['pageTitle']) && strlen($this->pageParams['pageTitle']))
		{
			$pageTitle = $this->pageParams['pageTitle'];

			$page = intval($page);
			if ($page > 1)
			{
				$pageAppend = $this->language->phrase('title_page_x', ['page' => $page]);
				if ($pageTitle instanceof \XF\PreEscaped)
				{
					$pageTitle = clone $pageTitle;
					$pageTitle->value .= $pageAppend;
				}
				else
				{
					$pageTitle .= $pageAppend;
				}
			}

			if ($formatter)
			{
				$value = sprintf($formatter,
					$this->escape($pageTitle, $escape),
					$this->escape($fallback, $escape)
				);

				$escape = false;
				return $value;
			}
			else
			{
				return $pageTitle;
			}
		}
		else
		{
			return $fallback;
		}
	}

	protected function getPrefixPhraseName($contentType, $id, $group = false)
	{
		return $contentType . '_prefix' . ($group ? '_group.' : '.') . $id;
	}

	public function fnParens($templater, &$escape, $value)
	{
		return $this->filterParens($templater, $value, $escape);
	}

	public function fnPrefix($templater, &$escape, $contentType, $prefixId, $format = 'html', $append = null)
	{
		if (!is_int($prefixId))
		{
			$prefixId = $prefixId->prefix_id;
		}

		if (!$prefixId)
		{
			return '';
		}

		$prefixCache = $this->app->container('prefixes.' . $contentType);
		$prefixClass = isset($prefixCache[$prefixId]) ? $prefixCache[$prefixId] : null;

		if (!$prefixClass)
		{
			return '';
		}

		$output = $this->fn('prefix_title', [$contentType, $prefixId], false);

		switch ($format)
		{
			case 'html':
				$output = '<span class="' . htmlspecialchars($prefixClass) . '" dir="auto">'
					. \XF::escapeString($output, 'html') . '</span>';
				if ($append === null)
				{
					$append = '<span class="label-append">&nbsp;</span>';
				}
				break;

			case 'plain':
				if ($output instanceof \XF\Phrase)
				{
					$output = $output->render('raw');
				}
				break; // ok as is

			default:
				$output = \XF::escapeString($output, 'html'); // just be safe and escape everything else
		}

		if ($append === null)
		{
			$append = ' - ';
		}

		$escape = false;
		return $output . $append;
	}

	public function fnPrefixGroup($templater, &$escape, $contentType, $groupId)
	{
		if ($groupId == 0)
		{
			return '(' . \XF::phrase('ungrouped') . ')';
		}

		return \XF::phrase($this->getPrefixPhraseName($contentType, $groupId, true), [], false);
	}

	public function fnPrefixTitle($templater, &$escape, $contentType, $prefixId)
	{
		return \XF::phrase($this->getPrefixPhraseName($contentType, $prefixId), [], false);
	}

	public function fnProperty($templater, &$escape, $name, $fallback = null)
	{
		$escape = false;

		if (!$this->style)
		{
			return $fallback;
		}

		return $this->style->getProperty($name, $fallback);
	}

	public function fnRand($templater, &$escape, $min = 0, $max = 999)
	{
		return mt_rand($min, $max);
	}

	public function fnRange($templater, &$escape, $start, $end, $step = 1)
	{
		return range($start, $end, $step);
	}

	public function fnRedirectInput($templater, &$escape, $url = null, $fallbackUrl = null, $useReferrer = true)
	{
		$escape = false;

		if ($url)
		{
			$redirect = $this->app->request()->convertToAbsoluteUri($url);
		}
		else
		{
			$redirect = $this->app->getDynamicRedirect($fallbackUrl ?: null, (bool)$useReferrer);
		}
		return '<input type="hidden" name="_xfRedirect" value="' . htmlspecialchars($redirect) . '" />';
	}

	public function fnRepeat($templater, &$escape, $string, $count)
	{
		return str_repeat($string, $count);
	}

	public function fnRepeatRaw($templater, &$escape, $string, $count)
	{
		$escape = false;
		return str_repeat($string, $count);
	}

	public function fnShowIgnored($templater, &$escape, array $attributes = [])
	{
		$escape = false;

		if (!\XF::visitor()->user_id)
		{
			return '';
		}

		$wrapperClass = $this->processAttributeToRaw($attributes, 'wrapperclass', '', true);
		$wrapper = $this->processAttributeToRaw($attributes, 'wrapper');
		if ($wrapperClass && !$wrapper)
		{
			$wrapper = 'div';
		}

		$class = $this->processAttributeToRaw($attributes, 'class', ' %s', true);
		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		$html = '<a href="javascript:"'
			. ' class="showIgnoredLink is-hidden js-showIgnored' . $class . '" data-xf-init="tooltip"'
			. ' title="' . \XF::phrase('show_hidden_content_by_x', ['names' => '{{names}}']) . '"'
			. ' ' . $unhandledAttrs . '>' .
			\XF::phrase('show_ignored_content')
			. '</a>';

		if ($wrapper)
		{
			$wrapperOpen = $wrapper . ($wrapperClass ? " class=\"$wrapperClass\"" : '');
			$html = "<{$wrapperOpen}>{$html}</{$wrapper}>";
		}
		return $html;
	}

	public function fnSmilie($templater, &$escape, $smilieString)
	{
		$escape = false;

		$formatter = $this->app->stringFormatter();
		return $formatter->replaceSmiliesHtml($smilieString);
	}

	public function fnSnippet($templater, &$escape, $string, $maxLength = 0, array $options = [])
	{
		// if we aren't escaping here
		$needsEscaping = ($escape ? true : false);
		$escape = false;

		$formatter = $this->app->stringFormatter();
		$string = $formatter->snippetString($string, $maxLength, $options);

		if (!empty($options['term']))
		{
			return $formatter->highlightTermForHtml(
				$string, $options['term'], isset($options['highlightClass']) ? $options['highlightClass'] : 'textHighlight'
			);
		}
		else
		{
			return $needsEscaping ? \XF::escapeString($string) : $string;
		}
	}

	public function fnStrlen($templater, &$escape, $string)
	{
		return utf8_strlen($string);
	}

	public function fnContains($templater, &$escape, $haystack, $needle)
	{
		return utf8_strpos(utf8_strtolower($haystack), utf8_strtolower($needle)) !== false;
	}

	public function fnStructuredText($templater, &$escape, $string, $nl2br = true)
	{
		$stringFormatter = $this->app->stringFormatter();

		$string = $stringFormatter->censorText($string);
		$string = \XF::escapeString($string);
		$string = $stringFormatter->autoLinkStructuredText($string);
		$string = $stringFormatter->linkStructuredTextMentions($string);

		if ($nl2br)
		{
			$string = nl2br($string);
		}

		$escape = false;
		return $string;
	}

	public function fnTemplater($templater, &$escape)
	{
		$escape = false;
		return $templater;
	}

	public function fnTime($templater, &$escape, $time, $format = null)
	{
		return $this->language->time($time, $format);
	}

	public function fnTrim($templater, &$escape, $str, $charlist = " \t\n\r\0\x0B")
	{
		return trim($str, $charlist);
	}

	public function fnUniqueId($templater, &$escape, $baseValue = null)
	{
		if ($baseValue === null)
		{
			$this->uniqueIdCounter++;
			$baseValue = $this->uniqueIdCounter;
		}

		return sprintf($this->uniqueIdFormat, $baseValue);
	}

	public function fnUserActivity($templater, &$escape, $user)
	{
		if (!$user instanceof \XF\Entity\User || !$user->user_id)
		{
			return '';
		}

		if (!$user->canViewOnlineStatus())
		{
			return '';
		}

		$output = '';

		if ($user->canViewCurrentActivity() && $user->Activity)
		{
			if ($user->Activity->description)
			{
				$output .= \XF::escapeString($user->Activity->description);
				if ($user->Activity->item_title)
				{
					$title = \XF::escapeString($user->Activity->item_title);
					$url = \XF::escapeString($user->Activity->item_url);

					$output .= " <em><a href=\"{$url}\" dir=\"auto\">{$title}</a></em>";
				}

				$output .= ' <span role="presentation" aria-hidden="true">&middot;</span> ';
			}
		}

		$output .= $this->fnDateDynamic($this, $escape, $user->last_activity);

		$escape = false;

		return $output;
	}

	public function fnUserBanners($templater, &$escape, $user, $attributes = [])
	{
		/** @var \XF\Entity\User $user */

		$escape = false;

		if (!$user || !($user instanceof \XF\Entity\User) || !$user->user_id)
		{
			/** @var \XF\Repository\User $userRepo */
			$userRepo = $this->app->repository('XF:User');
			$user = $userRepo->getGuestUser();
		}

		$class = $this->processAttributeToRaw($attributes, 'class', ' %s', true);

		if (!empty($attributes['tag']))
		{
			$tag = htmlspecialchars($attributes['tag']);
		}
		else
		{
			$tag = 'em';
		}

		unset($attributes['tag']);

		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		$banners = [];
		$config = $this->userBannerConfig;

		if (!empty($config['showStaff']) && $user->is_staff)
		{
			$p = \XF::phrase('staff_member');
			$banners['staff'] = "<{$tag} class=\"userBanner userBanner--staff{$class}\" dir=\"auto\"{$unhandledAttrs}>"
				. "<span class=\"userBanner-before\"></span><strong>{$p}</strong><span class=\"userBanner-after\"></span></{$tag}>";
		}

		$memberGroupIds = $user->secondary_group_ids;
		$memberGroupIds[] = $user->user_group_id;

		foreach ($this->userBanners AS $groupId => $banner)
		{
			if (!in_array($groupId, $memberGroupIds))
			{
				continue;
			}

			$banners[$groupId] = "<{$tag} class=\"userBanner {$banner['class']}{$class}\"{$unhandledAttrs}>"
				. "<span class=\"userBanner-before\"></span><strong>{$banner['text']}</strong><span class=\"userBanner-after\"></span></{$tag}>";
		}

		if (!$banners)
		{
			return '';
		}

		if (!empty($config['displayMultiple']))
		{
			return implode("\n", $banners);
		}
		else if (!empty($config['showStaffAndOther']) && isset($banners['staff']) && count($banners) >= 2)
		{
			$staffBanner = $banners['staff'];
			unset($banners['staff']);
			return $staffBanner . "\n" . reset($banners);
		}
		else
		{
			return reset($banners);
		}
	}

	public function fnUserBlurb($templater, &$escape, $user, $attributes = [])
	{
		if (!$user instanceof \XF\Entity\User)
		{
			return '';
		}

		$blurbParts = [];

		$blurbParts[] = $this->fnUserTitle($this, $escape, $user);
		if ($user->Profile->age)
		{
			$blurbParts[] = $user->Profile->age;
		}
		if ($user->Profile->location)
		{
			$location = \XF::escapeString($user->Profile->location);
			$location = '<a href="' . $this->app->router('public')->buildLink('misc/location-info', null, ['location' => $location]) . '" class="u-concealed">' . $location. '</a>';
			$blurbParts[] = \XF::phrase('from_x_location', ['location' => new \XF\PreEscaped($location)])->render();
		}

		$tag = $this->processAttributeToRaw($attributes, 'tag');
		if (!$tag)
		{
			$tag = 'div';
		}

		$class = $this->processAttributeToRaw($attributes, 'class', '%s', true);
		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		return "<{$tag} class=\"{$class}\" dir=\"auto\" {$unhandledAttrs}>"
			. implode(' <span role="presentation" aria-hidden="true">&middot;</span> ', $blurbParts)
			. "</{$tag}>";
	}

	public function fnUserTitle($templater, &$escape, $user, $withBanner = false, $attributes = [])
	{
		/** @var \XF\Entity\User $user */

		$escape = false;
		$userIsValid = ($user instanceof \XF\Entity\User);

		$userTitle = null;

		if ($userIsValid)
		{
			$customTitle = $user->custom_title;
			if ($customTitle)
			{
				$userTitle = htmlspecialchars($customTitle);
			}
		}

		if ($userTitle === null)
		{
			if ($withBanner && !empty($this->userBannerConfig['hideUserTitle']))
			{
				if (!$userIsValid)
				{
					return '';
				}

				if (!empty($this->userBannerConfig['showStaff']) && $user->is_staff)
				{
					return '';
				}

				if ($user->isMemberOf(array_keys($this->userBanners)))
				{
					return '';
				}
			}

			if ($userIsValid)
			{
				$groupId = $user->display_style_group_id;
				if (!empty($this->groupStyles[$groupId]['user_title']))
				{
					$userTitle = $this->groupStyles[$groupId]['user_title'];
				}
				else
				{
					foreach ($this->userTitleLadder AS $points => $title)
					{
						if ($user[$this->userTitleLadderField] >= $points)
						{
							$userTitle = $title;
							break;
						}
					}
				}
			}
			else
			{
				$guestGroupId = 1;
				if (empty($this->groupStyles[$guestGroupId]['user_title']))
				{
					return '';
				}

				$userTitle = $this->groupStyles[$guestGroupId]['user_title'];
			}
		}

		if ($userTitle === null || !strlen($userTitle))
		{
			return '';
		}

		$class = $this->processAttributeToRaw($attributes, 'class', ' %s', true);

		if (!empty($attributes['tag']))
		{
			$tag = htmlspecialchars($attributes['tag']);
		}
		else
		{
			$tag = 'span';
		}

		unset($attributes['tag']);

		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		return "<{$tag} class=\"userTitle{$class}\" dir=\"auto\"{$unhandledAttrs}>{$userTitle}</{$tag}>";
	}

	public function fnUsernameLink($templater, &$escape, $user, $rich = false, $attributes = [])
	{
		$escape = false;

		if (isset($attributes['username']))
		{
			$username = $attributes['username'];
		}
		else if (isset($user['username']) && $user['username'] !== '')
		{
			$username = $user['username'];
		}
		else if (isset($attributes['defaultname']))
		{
			$username = $attributes['defaultname'];
		}
		else
		{
			return '';
		}

		$noTooltip = !empty($attributes['notooltip']);

		if (isset($attributes['href']))
		{
			$href = $attributes['href'];
			$noTooltip = true; // custom URL so tooltip won't work and might be misleading
		}
		else
		{
			$linkPath = $this->currentTemplateType == 'admin' ? 'users/edit' : 'members';
			$href = !empty($user['user_id']) ? $this->getRouter()->buildLink($linkPath, $user) : null;
			if (!$href || $this->currentTemplateType == 'admin')
			{
				$noTooltip = true;
			}
		}
		$hrefAttr = $href ? ' href="' . htmlspecialchars($href) . '"' : '';

		$class = $this->processAttributeToRaw($attributes, 'class', ' %s', true);
		$usernameStylingClasses = $this->fnUsernameClasses($this, $null, $user, $rich);
		$xfInit = $this->processAttributeToRaw($attributes, 'data-xf-init', '', true);

		if (!$noTooltip)
		{
			$xfInit = ltrim("$xfInit member-tooltip");
		}
		$xfInitAttr = $xfInit ? " data-xf-init=\"$xfInit\"" : '';

		unset($attributes['username'], $attributes['defaultname'], $attributes['href'], $attributes['notooltip']);
		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		$userId = !empty($user['user_id']) ? intval($user['user_id']) : 0;

		$username = htmlspecialchars($username);
		if ($usernameStylingClasses)
		{
			$username = "<span class=\"{$usernameStylingClasses}\">{$username}</span>";
		}
		if ($hrefAttr)
		{
			$tag = 'a';
		}
		else
		{
			$tag = 'span';
		}
		return "<{$tag}{$hrefAttr} class=\"username {$class}\" dir=\"auto\" data-user-id=\"{$userId}\"{$xfInitAttr}{$unhandledAttrs}>{$username}</{$tag}>";
	}

	public function fnUsernameLinkEmail($templater, &$escape, $user, $defaultName = '', array $attributes = [])
	{
		$escape = false;

		if (isset($attributes['username']))
		{
			$username = $attributes['username'];
		}
		else if (isset($user['username']) && $user['username'] !== '')
		{
			$username = $user['username'];
		}
		else if ($defaultName !== '')
		{
			$username = $defaultName;
		}
		else
		{
			return '';
		}

		unset($attributes['username']);

		if (isset($attributes['href']))
		{
			$href = $attributes['href'];
		}
		else
		{
			$href = !empty($user['user_id']) ? $this->getRouter()->buildLink('canonical:members', $user) : null;

		}
		$hrefAttr = $href ? ' href="' . htmlspecialchars($href) . '"' : '';
		$tag = $href ? 'a' : 'span';

		unset($attributes['username'], $attributes['href']);
		$unhandledAttrs = $this->processUnhandledAttributes($attributes);

		$username = htmlspecialchars($username);

		return "<{$tag} dir=\"auto\"{$hrefAttr}{$unhandledAttrs}>{$username}</{$tag}>";
	}

	public function fnUsernameClasses($templater, &$escape, $user, $includeGroupStyling = true)
	{
		$classes = [];

		if ($includeGroupStyling)
		{
			if (!$user || empty($user['user_id']))
			{
				$displayGroupId = 1;
			}
			else
			{
				if (!empty($user['display_style_group_id']))
				{
					$displayGroupId = $user['display_style_group_id'];
				}
				else
				{
					$displayGroupId = 0;
				}
			}

			if ($displayGroupId && !empty($this->groupStyles[$displayGroupId]['username_css']))
			{
				$classes[] = 'username--style' . $displayGroupId;
			}
		}

		if (!empty($user['is_banned']) && \XF::visitor()->canBypassUserPrivacy())
		{
			$classes[] = 'username--banned';
		}

		foreach (['staff', 'moderator', 'admin'] AS $userType)
		{
			if (!empty($user["is_{$userType}"]))
			{
				$classes[] = "username--{$userType}";
			}
		}

		$escape = false; // note: not doing this explicitly, shouldn't be needed for the output format

		return implode(' ', $classes);
	}

	public function fnWidgetData($templater, &$escape, $widgetData, $asArray = false)
	{
		$output = [];

		$escape = false;

		if (isset($widgetData['id']))
		{
			if ($asArray)
			{
				$output['data-widget-id'] = $widgetData['id'];
			}
			else
			{
				$output[] = 'data-widget-id="' . $widgetData['id'] . '"';
			}
		}
		if (isset($widgetData['key']))
		{
			if ($asArray)
			{
				$output['data-widget-key'] = $widgetData['key'];
			}
			else
			{
				$output[] = 'data-widget-key="' . $widgetData['key'] . '"';
			}
		}
		if (isset($widgetData['definition']))
		{
			if ($asArray)
			{
				$output['data-widget-definition'] = $widgetData['definition'];
			}
			else
			{
				$output[] = 'data-widget-definition="' . $widgetData['definition'] . '"';
			}
		}

		if ($asArray)
		{
			return $output ? $output : [];
		}
		else
		{
			return $output ? ' ' . implode(' ', $output) : '';
		}
	}

	////////////////////// FILTERS //////////////////////////

	public function filterDefault($templater, $value, &$escape, $defaultValue)
	{
		if ($value === null)
		{
			$value = $defaultValue;
		}

		return $value;
	}

	public function filterCensor($templater, $value, &$escape, $censorChar = null)
	{
		return $this->app->stringFormatter()->censorText($value, $censorChar);
	}

	public function filterCurrency($templater, $value, &$escape, $code = '', $format = null)
	{
		$currency = $this->app->data('XF:Currency');
		return $currency->languageFormat($value, $code, $this->language, $format);
	}

	public function filterEscape($templater, $value, &$escape, $type = true)
	{
		$escape = $type;
		return $value;
	}

	public function filterForAttr($templater, $value, &$escape)
	{
		// this is a sanity check to make sure even pre-escaped values are escaped and can't break out of
		// an HTML attribute
		$escape = false;
		return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8', false);
	}

	public function filterFileSize($templater, $value, &$escape)
	{
		return $this->language->fileSizeFormat($value);
	}

	public function filterFirst($templater, $value, &$escape)
	{
		if (is_array($value))
		{
			return reset($value);
		}
		else if ($value instanceof AbstractCollection)
		{
			return $value->first();
		}
		else
		{
			return $value;
		}
	}

	public function filterHex($templater, $value, &$escape)
	{
		return bin2hex($value);
	}

	public function filterHost($templater, $value, &$escape)
	{
		return \XF\Util\Ip::getHost($value);
	}

	public function filterIp($templater, $value, &$escape)
	{
		return \XF\Util\Ip::convertIpBinaryToString($value);
	}

	public function filterJoin($templater, $value, &$escape, $join = ',')
	{
		if (!$this->isTraversable($value))
		{
			return '';
		}

		$parts = [];
		foreach ($value AS $child)
		{
			$parts[] = $escape ? $this->escape($child, $escape) : $child;
		}

		$escape = false;
		return implode($join, $parts);
	}

	public function filterJson($templater, $value, &$escape, $prettyPrint = false)
	{
		if ($prettyPrint)
		{
			return \XF\Util\Json::jsonEncodePretty($value);
		}
		else
		{
			return json_encode($value);
		}
	}

	public function filterLast($templater, $value, &$escape)
	{
		if (is_array($value))
		{
			return end($value);
		}
		else if ($value instanceof AbstractCollection)
		{
			return $value->last();
		}
		else
		{
			return $value;
		}
	}

	public function filterNl2Br($templater, $value, &$escape)
	{
		if ($escape)
		{
			$value = $this->escape($value, $escape);
		}
		$escape = false;

		return nl2br($value);
	}

	public function filterNl2Nl($templater, $value, &$escape)
	{
		if ($escape)
		{
			$value = $this->escape($value, $escape);
		}
		$escape = false;

		return str_replace('\n', "\n", $value);
	}

	public function filterNumber($templater, $value, &$escape, $precision = 0)
	{
		return $this->language->numberFormat($value, $precision);
	}

	public function filterNumberShort($templater, $value, &$escape)
	{
		return $this->language->shortNumberFormat($value);
	}

	public function filterParens($templater, $value, &$escape)
	{
		$value = (string)$value;
		if (strlen($value))
		{
			$value = $this->language['parenthesis_open'] . $value . $this->language['parenthesis_close'];
		}

		return $value;
	}

	public function filterPluck($templater, $value, &$escape, $valueField, $keyField = null)
	{
		if (!$this->isTraversable($value))
		{
			return [];
		}

		$parts = [];
		foreach ($value AS $key => $child)
		{
			if ($keyField !== null && isset($child[$keyField]))
			{
				$key = $child[$keyField];
			}
			$parts[$key] = isset($child[$valueField]) ? $child[$valueField] : null;
		}

		return $parts;
	}

	public function filterPreEscaped($templater, $value, &$escape, $type = 'html')
	{
		$escape = false;

		return $this->preEscaped($value, $type);
	}

	public function filterRaw($templater, $value, &$escape)
	{
		$escape = false;
		return $value;
	}

	public function filterReplace($templater, $value, &$escape, $from, $to = null)
	{
		if ($value instanceof \XF\Mvc\Entity\AbstractCollection)
		{
			$value = $value->toArray();
		}

		if (!is_array($from))
		{
			$from = [$from => $to];
		}

		if (!is_array($from))
		{
			return $value;
		}

		if (is_array($value))
		{
			return array_replace($value, $from);
		}
		else if (is_string($value))
		{
			return str_replace(array_keys($from), $from, $value);
		}
		else
		{
			return $value;
		}
	}

    public function filterSplit($templater, $value, &$escape, $delimiter = ',', $limit = PHP_INT_MAX)
    {
        switch ($delimiter)
        {
            case ',':
                $split = @preg_split('#\s*,\s*#', $value, $limit, PREG_SPLIT_NO_EMPTY);
                break;

            case 'nl':
                $split = @preg_split('/\r?\n/', $value, $limit, PREG_SPLIT_NO_EMPTY);
                break;

            default:
                $split = @explode($delimiter, $value, $limit);
                break;
        }

        if (!is_array($split))
        {
            $split = [];
        }

        return $split;
    }

	public function filterStripTags($templater, $value, &$escape, $allowableTags = null)
	{
		return strip_tags($value, $allowableTags);
	}

	public function filterToLower($templater, $value, &$escape, $type = 'strtolower')
	{
		switch ($type)
		{
			case 'lcfirst': return lcfirst($value);
			case 'strtolower': return utf8_strtolower($value);

			default:
				trigger_error("Invalid to lower type '{$type}' provided.", E_USER_WARNING);
				return '';
		}

	}

	public function filterToUpper($templater, $value, &$escape, $type = 'strtoupper')
	{
		switch ($type)
		{
			case 'ucfirst':
			case 'ucwords':
			case 'strtoupper':
				$f = 'utf8_' . $type;
				return $f($value);

			default:
				trigger_error("Invalid to upper type '{$type}' provided.", E_USER_WARNING);
				return '';
		}
	}

	public function filterUrl($templater, $value, &$escape, $component = null, $fallback = '')
	{
		$result = @parse_url($value);
		if (!$result)
		{
			return $fallback;
		}

		if (!$component)
		{
			return $value;
		}

		if (isset($result[$component]))
		{
			return $result[$component];
		}
		else
		{
			return $fallback;
		}
	}

	public function filterUrlencode($templater, $value, &$escape)
	{
		return urlencode($value);
	}

	////////////////////// TESTS ////////////////////////

	public function testEmpty($templater, $value)
	{
		if (is_object($value) && is_callable([$value, '__toString']))
		{
			return strval($value) === '';
		}

		if ($value instanceof \Countable)
		{
			return count($value) == 0;
		}

		return ($value === '' || $value === false || $value === null || $value === []);
	}

	////////////////////// FORM ELEMENTS ////////////////////////

	public function mergeChoiceOptions($original, $additional)
	{
		if ($original instanceof \Traversable)
		{
			$original = iterator_to_array($original, false);
		}
		else if (!is_array($original))
		{
			$original = [];
		}

		if ($this->isTraversable($additional))
		{
			foreach ($additional AS $key => $option)
			{
				if (is_string($option)
					|| is_numeric($option)
					|| (is_object($option) && method_exists($option, '__toString'))
				)
				{
					$original[] = [
						'value' => $key,
						'label' => \XF::escapeString($option),
						'_type' => 'option'
					];
				}
			}
		}

		return $original;
	}

	public function processAttributeToHtmlAttribute(array &$attributes, $name, $fallbackValue = '', $appendFallback = false)
	{
		return $this->processAttributeToNamedHtmlAttribute($attributes, $name, $name, $fallbackValue, $appendFallback);
	}

	public function processAttributeToNamedHtmlAttribute(array &$attributes, $sourceName, $targetName, $fallbackValue = '', $appendFallback = false)
	{
		if (isset($attributes[$sourceName]))
		{
			$value = $attributes[$sourceName];
			if ($appendFallback && $fallbackValue)
			{
				$value .= " $fallbackValue";
			}
		}
		else
		{
			$value = $fallbackValue;
		}

		unset($attributes[$sourceName]);

		if (is_array($value))
		{
			return '';
		}

		$value = strval($value);
		if ($value === '')
		{
			return '';
		}
		else
		{
			return " $targetName=\"" . \XF::escapeString($value) . "\"";
		}
	}

	public function processCodeAttribute(array &$attributes)
	{
		if (isset($attributes['code']))
		{
			if ($attributes['code'] === 'true' || $attributes['code'] === 1)
			{
				$attributes['dir'] = 'ltr';
				$attributes['class'] = (empty($attributes['class']) ? 'input--code' : $attributes['class'] . ' input--code');
			}

			unset($attributes['code']);
		}
	}

	public function processBooleanAttributeHtml(array &$attributes, $name, $outputAttribute)
	{
		if (!isset($attributes[$name]))
		{
			return '';
		}

		$value = $attributes[$name];
		unset($attributes[$name]);

		if ($value)
		{
			return " $outputAttribute";
		}
		else
		{
			return '';
		}
	}

	public function processAttributeToRaw(array &$attributes, $name, $formatter = '', $escapeValue = false)
	{
		if (isset($attributes[$name]))
		{
			$value = strval($attributes[$name]);
			if ($value !== '')
			{
				if ($escapeValue)
				{
					$value = \XF::escapeString($value);
				}

				if ($formatter)
				{
					if ($formatter instanceof \Closure)
					{
						$value = $formatter($value);
					}
					else
					{
						$value =  sprintf($formatter, $value);
					}
				}
			}
		}
		else
		{
			$value = '';
		}

		unset($attributes[$name]);

		return $value;
	}

	protected function processUnhandledAttributes(array $attributes)
	{
		$output = '';
		foreach ($attributes AS $name => $value)
		{
			if (is_array($value))
			{
				continue;
			}

			$value = strval($value);
			if ($value !== '')
			{
				$output .= " $name=\"" . \XF::escapeString($value) . "\"";
			}
		}
		return $output;
	}

	protected function processDynamicAttributes(array &$attributes, array $skip = [])
	{
		if (!isset($attributes['attributes']))
		{
			return;
		}

		foreach ($attributes['attributes'] AS $key => $attribute)
		{
			if ($key == 'attributes' || isset($attributes[$key]) || isset($skip[$key]))
			{
				continue;
			}
			$attributes[$key] = $attribute;
		}
		unset($attributes['attributes']);
	}

	protected function handleChoices(array $choices, \Closure $choiceFormatter, \Closure $groupFormatter)
	{
		$html = '';

		foreach ($choices AS $choice)
		{
			if (isset($choice['_type']))
			{
				$type = $choice['_type'];
			}
			else
			{
				$type = 'option';
			}
			unset($choice['_type']);

			if ($type == 'optgroup')
			{
				$childHtml = $this->handleChoices($choice['options'], $choiceFormatter, $groupFormatter);
				unset($choice['options']);

				$html .= $groupFormatter($choice, $childHtml);
			}
			else
			{
				$dependent = !empty($choice['_dependent']) ? $choice['_dependent'] : [];
				foreach ($dependent AS $key => &$val)
				{
					$val = trim($val);
					if (!strlen($val))
					{
						unset($dependent[$key]);
					}
				}
				unset($choice['_dependent']);

				$html .= $choiceFormatter($choice, $dependent);
			}
		}

		return $html;
	}

	public function isChoiceSelected(array $choice, $inputValue, $allowMultiple = false)
	{
		if (isset($choice['selected']))
		{
			return $choice['selected'];
		}

		if ($inputValue !== null)
		{
			$choiceValue = isset($choice['value']) ? strval($choice['value']) : '';

			if (is_array($inputValue) && $allowMultiple)
			{
				return in_array($choiceValue, $inputValue);
			}
			else if (!is_array($inputValue))
			{
				return (
					($inputValue === true && $choiceValue === '1')
					|| ($inputValue === false && $choiceValue === '0')
					|| (strval($inputValue) === $choiceValue)
				);
			}
		}

		return false;
	}

	public function formHiddenVal($name, $value, array $extraAttributes = [])
	{
		$this->processDynamicAttributes($extraAttributes);

		$nameHtml = \XF::escapeString($name);
		$valueHtml = \XF::escapeString($value);
		$extraAttrs = $this->processUnhandledAttributes($extraAttributes);
		return "<input type=\"hidden\" name=\"{$nameHtml}\" value=\"{$valueHtml}\"{$extraAttrs} />";
	}

	public function formCheckBox(array $controlOptions, array $choices)
	{
		$this->processDynamicAttributes($controlOptions);

		$name = \XF::escapeString($this->processAttributeToRaw($controlOptions, 'name'));
		if ($name && substr($name, -2) != '[]')
		{
			$name .= '[]';
		}

		$readOnly = $this->processAttributeToRaw($controlOptions, 'readonly');

		$value = isset($controlOptions['value']) ? $controlOptions['value'] : null;

		$standalone = ($this->processAttributeToRaw($controlOptions, 'standalone') && count($choices) == 1);

		$choiceFormatter = function(array $choice, array $dependent) use ($name, $readOnly, $value, $standalone)
		{
			$selected = $this->isChoiceSelected($choice, $value, true);
			if (!empty($choice['name']))
			{
				$localName = \XF::escapeString($choice['name']);
			}
			else
			{
				$localName = $name;
			}
			if ($localName)
			{
				$nameAttr = ' name="' . $localName . '"';
			}
			else
			{
				$nameAttr = '';
			}

			unset($choice['selected'], $choice['name'], $choice['type']);

			$dependentHtml = '';
			if ($dependent && !$standalone)
			{
				$dependentHtmlInner = '';
				foreach ($dependent AS $child)
				{
					$dependentHtmlInner .= "\n\t\t\t\t<li class=\"inputChoices-option\">$child</li>";
				}
				$dependentHtml = "\n\t\t\t<ul class=\"inputChoices-dependencies\">{$dependentHtmlInner}\n\t\t\t</ul>\n\t\t";
			}
			if ($dependentHtml)
			{
				$this->addElementHandler($choice, 'disabler');
			}

			$labelClass = 'iconic iconic--checkbox';
			$label = trim($this->processAttributeToRaw($choice, 'label'));
			if ($label !== '')
			{
				$labelClass .= ' iconic--labelled';
			}
			$labelClassExtra = $this->processAttributeToRaw($choice, 'labelclass', '', true);
			if ($labelClassExtra !== '')
			{
				$labelClass .= " {$labelClassExtra}";
			}

			$titleAttr = $this->processAttributeToHtmlAttribute($choice, 'title');

			$tooltipAttr = '';
			if ($choice['data-xf-init'] == 'tooltip')
			{
				$tooltipAttr = $this->processAttributeToHtmlAttribute($choice, 'data-xf-init');
			}

			$checkAll = $this->processAttributeToRaw($choice, 'check-all');
			if ($checkAll != '')
			{
				$choice['data-xf-init'] .= (empty($choice['data-xf-init']) ? '' : ' ') . 'check-all';
				$choice['data-container'] = $checkAll;
			}

			$hint = $this->processAttributeToRaw($choice, 'hint', "\n\t\t\t\t\t<dfn class=\"inputChoices-explain\">%s</dfn>");
			$extraHtml = $this->processAttributeToRaw($choice, 'html', "\n\t\t\t\t\t%s");
			$afterHint = $this->processAttributeToRaw($choice, 'afterhint', "\n\t\t\t<dfn class=\"inputChoices-explain inputChoices-explain--after\">%s</dfn>");
			$afterHtml = $this->processAttributeToRaw($choice, 'afterhtml', "\n\t\t\t%s");

			$valueAttr = $this->processAttributeToHtmlAttribute($choice, 'value');
			if (!$valueAttr)
			{
				$valueAttr = ' value="1"';
			}
			$selectedAttr = $selected ? ' checked="checked"' : '';
			$readOnlyAttr = $readOnly ? ' readonly="readonly" onclick="return false"' : '';

			if ($readOnly)
			{
				$labelClass .= ' is-readonly';
			}

			if (isset($choice['defaultvalue']) && $localName && substr($localName, -2) != '[]')
			{
				// $localName is escaped
				$defaultValueInput = '<input type="hidden" name="' . $localName
					. '" value="' . \XF::escapeString($choice['defaultvalue']) . '" />';

				unset($choice['defaultvalue']);
			}
			else
			{
				$defaultValueInput = '';
			}

			$attributes = $this->processUnhandledAttributes($choice);

			$checkboxHtml = $defaultValueInput . "<label class=\"{$labelClass}\"{$titleAttr}{$tooltipAttr}>"
				. "<input type=\"checkbox\" {$nameAttr}{$valueAttr}{$selectedAttr}{$readOnlyAttr}{$attributes} />"
				. "<i aria-hidden=\"true\"></i>{$label}</label>{$hint}{$extraHtml}{$dependentHtml}{$afterHint}{$afterHtml}";

			if ($standalone)
			{
				return $checkboxHtml . "\n";
			}
			else
			{
				return "<li class=\"inputChoices-choice\">{$checkboxHtml}</li>\n";
			}
		};
		$groupFormatter = function(array $group, $html)
		{
			$label = $this->processAttributeToRaw($group, 'label');
			if ($label)
			{
				$checkAll = $this->processAttributeToRaw($group, 'check-all');
				if ($checkAll)
				{
					$label = '<label class="iconic iconic--checkbox iconic--labelled inputChoices-heading-checkAll">
						<input type="checkbox" data-xf-init="check-all" data-container="< .inputChoices-group" /><i aria-hidden="true"></i>'
						. $label . '</label>';
				}
				$class = $this->processAttributeToRaw($group, 'class', '', true);
				$listClass = $this->processAttributeToRaw($group, 'listclass', '', true);

				$html = "<li class=\"inputChoices-group {$class}\">
					<div class=\"inputChoices-heading\">{$label}</div>
					<ul class=\"inputChoices {$listClass}\">{$html}</ul>
				</li>";
			}

			return $html;
		};

		$choiceHtml = $this->handleChoices($choices, $choiceFormatter, $groupFormatter);

		$hideEmpty = $this->processAttributeToRaw($controlOptions, 'hideempty');
		if ($hideEmpty && !$choiceHtml)
		{
			return '';
		}

		if ($standalone)
		{
			return $choiceHtml;
		}

		$listClassAttr = $this->processAttributeToNamedHtmlAttribute($controlOptions, 'listclass', 'class', 'inputChoices', true);

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		return "
			<ul{$listClassAttr}{$unhandledAttrs}>
				$choiceHtml
			</ul>
		";
	}

	public function formCheckBoxRow(array $controlOptions, array $choices, array $rowOptions)
	{
		$controlHtml = $this->formCheckBox($controlOptions, $choices);
		return $controlHtml ? $this->formRow($controlHtml, $rowOptions) : '';
	}

	public function formRadio(array $controlOptions, array $choices)
	{
		$this->processDynamicAttributes($controlOptions);

		$name = \XF::escapeString($this->processAttributeToRaw($controlOptions, 'name'));
		$readOnly = $this->processAttributeToRaw($controlOptions, 'readonly');

		$value = isset($controlOptions['value']) ? $controlOptions['value'] : null;
		unset($controlOptions['value']);

		$standalone = ($this->processAttributeToRaw($controlOptions, 'standalone') && count($choices) == 1);

		$choiceFormatter = function(array $choice, array $dependent) use ($name, $readOnly, $value, $standalone)
		{
			$selected = $this->isChoiceSelected($choice, $value, false);

			unset($choice['selected'], $choice['type']);

			$dependentHtml = '';
			if ($dependent)
			{
				$dependentHtmlInner = '';
				foreach ($dependent AS $child)
				{
					$dependentHtmlInner .= "\n\t\t\t\t<li class=\"inputChoices-choice\">$child</li>";
				}
				$dependentHtml = "\n\t\t\t<ul class=\"inputChoices-dependencies\">{$dependentHtmlInner}\n\t\t\t</ul>\n\t\t";
			}
			if ($dependentHtml)
			{
				$this->addElementHandler($choice, 'disabler');
			}

			$labelClass = 'iconic iconic--radio';
			$label = trim($this->processAttributeToRaw($choice, 'label'));
			if ($label !== '')
			{
				$labelClass .= ' iconic--labelled';
			}
			$labelClassExtra = $this->processAttributeToRaw($choice, 'labelclass', '', true);
			if ($labelClassExtra !== '')
			{
				$labelClass .= " {$labelClassExtra}";
			}

			$titleAttr = $this->processAttributeToHtmlAttribute($choice, 'title');

			$tooltipAttr = '';
			if ($choice['data-xf-init'] == 'tooltip')
			{
				$tooltipAttr = $this->processAttributeToHtmlAttribute($choice, 'data-xf-init');
			}

			$hint = $this->processAttributeToRaw($choice, 'hint', "\n\t\t\t\t\t<dfn class=\"inputChoices-explain\">%s</dfn>");
			$extraHtml = $this->processAttributeToRaw($choice, 'html', "\n\t\t\t\t\t%s");
			$valueAttr = $this->processAttributeToHtmlAttribute($choice, 'value');
			if (!$valueAttr)
			{
				$valueAttr = ' value=""';
			}
			$selectedAttr = $selected ? ' checked="checked"' : '';
			$readOnlyAttr = $readOnly ? ' readonly="readonly" onclick="return false"' : '';

			if ($readOnly)
			{
				$labelClass .= ' is-readonly';
			}

			$listItemClass = $this->processAttributeToNamedHtmlAttribute($choice, 'listitemclass', 'class', 'inputChoices-choice', true);
			$attributes = $this->processUnhandledAttributes($choice);

			$radioHtml = "<label class=\"{$labelClass}\"{$titleAttr}{$tooltipAttr}>"
				. "<input type=\"radio\" name=\"$name\"{$valueAttr}{$selectedAttr}{$readOnlyAttr}{$attributes} />"
				. "<i aria-hidden=\"true\"></i>{$label}</label>{$hint}{$dependentHtml}{$extraHtml}";

			if ($standalone)
			{
				return $radioHtml . "\n";
			}
			else
			{
				return "<li{$listItemClass}>{$radioHtml}</li>\n";
			}
		};
		$groupFormatter = function(array $group, $html)
		{
			$label = $this->processAttributeToRaw($group, 'label');
			if ($label)
			{
				$class = $this->processAttributeToRaw($group, 'class', '', true);
				$listClass = $this->processAttributeToRaw($group, 'listclass', '', true);

				$html = "<li class=\"inputChoices-group {$class}\">
					<div class=\"inputChoices-heading\">{$label}</div>
					<ul class=\"inputChoices {$listClass}\">{$html}</ul>
				</li>";
			}
		};

		$choiceHtml = $this->handleChoices($choices, $choiceFormatter, $groupFormatter);

		$hideEmpty = $this->processAttributeToRaw($controlOptions, 'hideempty');
		if ($hideEmpty && !$choiceHtml)
		{
			return '';
		}

		if ($standalone)
		{
			return $choiceHtml;
		}

		$listClassAttr = $this->processAttributeToNamedHtmlAttribute($controlOptions, 'listclass', 'class', 'inputChoices', true);

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		return "
			<ul{$listClassAttr}{$unhandledAttrs}>
				$choiceHtml
			</ul>
		";
	}

	public function formRadioRow(array $controlOptions, array $choices, array $rowOptions)
	{
		$controlHtml = $this->formRadio($controlOptions, $choices);
		return $controlHtml ? $this->formRow($controlHtml, $rowOptions) : '';
	}

	public function formSelect(array $controlOptions, array $choices)
	{
		$this->processDynamicAttributes($controlOptions);

		$name = \XF::escapeString($this->processAttributeToRaw($controlOptions, 'name'));

		$value = isset($controlOptions['value']) ? $controlOptions['value'] : null;
		unset($controlOptions['value']);

		$multiple = !empty($controlOptions['multiple']);
		if ($multiple)
		{
			$multipleAttr = ' multiple="multiple"';
			if ($name && substr($name, -2) != '[]')
			{
				$name .= '[]';
			}
		}
		else
		{
			$multipleAttr = '';
		}
		unset($controlOptions['multiple']);

		$choiceFormatter = function(array $choice) use ($name, $value, $multiple)
		{
			$selected = $this->isChoiceSelected($choice, $value, $multiple);
			unset($choice['selected'], $choice['explain']);

			$label = trim($this->processAttributeToRaw($choice, 'label'));
			if ($label === '')
			{
				$label = '&nbsp;';
			}
			$valueAttr = $this->processAttributeToHtmlAttribute($choice, 'value');
			if (!$valueAttr)
			{
				$valueAttr = ' value=""';
			}
			$selectedAttr = $selected ? ' selected="selected"' : '';
			$disabled = $this->processAttributeToRaw($choice, 'disabled');
			$disabledAttr = $disabled ? ' disabled="disabled"': '';
			$attributes = $this->processUnhandledAttributes($choice);

			return "<option{$valueAttr}{$selectedAttr}{$disabledAttr}{$attributes}>{$label}</option>\n";
		};
		$groupFormatter = function(array $group, $html)
		{
			if (!$html)
			{
				return '';
			}

			$attributes = $this->processUnhandledAttributes($group);
			return "<optgroup{$attributes}>\n$html</optgroup>";
		};

		$choiceHtml = $this->handleChoices($choices, $choiceFormatter, $groupFormatter);
		$hideEmpty = $this->processAttributeToRaw($controlOptions, 'hideempty');
		if ($hideEmpty && !$choiceHtml)
		{
			return '';
		}

		$readOnly = $this->processAttributeToRaw($controlOptions, 'readonly');
		$disabled = $this->processAttributeToRaw($controlOptions, 'disabled');
		if ($readOnly)
		{
			$this->addToClassAttribute($controlOptions, 'is-readonly');
			$disabled = true;
		}

		$disabledAttr = $disabled ? ' disabled="disabled"' : '';

		$classAttr = $this->processAttributeToHtmlAttribute($controlOptions, 'class', 'input', true);
		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		$select = "
			<select name=\"{$name}\"{$multipleAttr}{$classAttr}{$disabledAttr}{$unhandledAttrs}>
				$choiceHtml
			</select>
		";
		if ($readOnly && $value !== null)
		{
			if ($multiple)
			{
				if (is_array($value))
				{
					foreach ($value AS $subValue)
					{
						$select .= '<input type="hidden" name="' . $name . '" value="' . \XF::escapeString($subValue) . '" />';
					}
				}
			}
			else
			{
				$select .= '<input type="hidden" name="' . $name . '" value="' . \XF::escapeString($value) . '" />';
			}
		}
		return $select;
	}

	public function formSelectRow(array $controlOptions, array $choices, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formSelect($controlOptions, $choices);
		return $controlHtml ? $this->formRow($controlHtml, $rowOptions, $controlId) : '';
	}

	public function formSubmitRow(array $controlOptions, array $rowOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$sticky = $this->processAttributeToRaw($controlOptions, 'sticky');
		if ($sticky && $sticky != 'false')
		{
			$this->addElementHandler($rowOptions, 'form-submit-row', 'rowclass');

			if ($sticky != 'true' && !is_numeric($sticky)) // indicates a container selector
			{
				$rowOptions['data-container'] = $sticky;
			}
		}

		$buttonClasses = 'button button--primary';
		$icon = $this->processAttributeToRaw($controlOptions, 'icon');
		if ($icon)
		{
			$buttonClasses .= ' button--icon button--icon--' . preg_replace('#[^a-zA-Z0-9_-]#', '', $icon);
		}
		$classAttr = $this->processAttributeToHtmlAttribute($controlOptions, 'class', $buttonClasses, true);

		$submit = strval($this->processAttributeToRaw($controlOptions, 'submit'));
		if (!$submit && $icon)
		{
			$submit = $this->getButtonPhraseFromIcon($icon, 'button.submit');
		}

		$unhandledControlAttrs = $this->processUnhandledAttributes($controlOptions);

		if (strlen($submit))
		{
			$controlHtml = "<button {$classAttr}{$unhandledControlAttrs}><span class=\"button-text\">{$submit}</span></button>";
		}
		else
		{
			$controlHtml = '';
		}

		$extraHtml = $this->processAttributeToRaw($rowOptions, 'html', "\n\t\t\t\t%s");

		$class = $this->processAttributeToRaw($rowOptions, 'rowclass', ' %s', true);
		if ($sticky)
		{
			$class .= ' formSubmitRow--sticky';
		}

		$rowType = $this->processAttributeToRaw($rowOptions, 'rowtype');
		if ($rowType)
		{
			$class = $this->appendClassList($class, $rowType, 'formSubmitRow--%s');
		}

		$unhandledRowAttrs = $this->processUnhandledAttributes($rowOptions);

		return "
			<dl class=\"formRow formSubmitRow{$class}\"{$unhandledRowAttrs}>
				<dt></dt>
				<dd>
					<div class=\"formSubmitRow-main\">
						<div class=\"formSubmitRow-bar\"></div>
						<div class=\"formSubmitRow-controls\">{$controlHtml}{$extraHtml}</div>
					</div>
				</dd>
			</dl>
		";
	}

	public function formTextArea(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$this->processCodeAttribute($controlOptions);

		$autosize = $this->processAttributeToRaw($controlOptions, 'autosize');
		if ($autosize)
		{
			$this->addElementHandler($controlOptions, 'textarea-handler');
			$classAppend = ' input--fitHeight';
		}
		else
		{
			$classAppend = '';
		}

		$maxLength = $this->processAttributeToRaw($controlOptions, 'maxlength');
		if ($maxLength)
		{
			$maxlengthAttr = " maxlength=\"{$maxLength}\"";
		}
		else
		{
			$maxlengthAttr = '';
		}

		$value = \XF::escapeString($this->processAttributeToRaw($controlOptions, 'value'));
		$readOnlyAttr = $this->processAttributeToRaw($controlOptions, 'readonly') ? ' readonly="readonly"' : '';
		$classAttr = $this->processAttributeToHtmlAttribute($controlOptions, 'class', 'input' . $classAppend, true);

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		return "<textarea{$classAttr}{$readOnlyAttr}{$maxlengthAttr}{$unhandledAttrs}>{$value}</textarea>";
	}

	public function formTextAreaRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formTextArea($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formDateInput(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$class = $this->processAttributeToRaw($controlOptions, 'class', ' %s', true);
		$xfInit = $this->processAttributeToRaw($controlOptions, 'data-xf-init', ' %s', true);
		$xfInitAttr = " data-xf-init=\"date-input$xfInit\"";
		$weekStart = $this->processAttributeToRaw($controlOptions, 'week-start', '', true);
		if (!$weekStart)
		{
			$weekStart = $this->language['week_start'];
		}
		$weekStartAttr = " data-week-start=\"$weekStart\"";
		$readOnly = $this->processAttributeToRaw($controlOptions, 'readonly');
		$readOnlyAttr = $readOnly ? ' readonly="readonly"' : '';

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		return "<div class=\"inputGroup inputGroup--date inputGroup--joined inputDate\"><input type=\"text\" class=\"input input--date {$class}\"{$xfInitAttr}{$weekStartAttr}{$readOnlyAttr}{$unhandledAttrs} /><span class=\"inputGroup-text inputDate-icon\"></span></div>";
	}

	public function formDateInputRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formDateInput($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formCodeEditor(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$name = $this->processAttributeToRaw($controlOptions, 'name');
		$value = $this->processAttributeToRaw($controlOptions, 'value');
		$extraClasses = $this->processAttributeToRaw($controlOptions, 'class');

		/** @var \XF\Data\CodeLanguage $codeLanguageData */
		$codeLanguageData = $this->app->data('XF:CodeLanguage');
		$supportedLanguages = $codeLanguageData->getSupportedLanguages();

		$mode = $this->processAttributeToRaw($controlOptions, 'mode');
		if (isset($supportedLanguages[$mode]))
		{
			$modeConfig = $supportedLanguages[$mode];
		}
		else
		{
			$modeConfig = [];
		}

		$readOnly = $this->processAttributeToRaw($controlOptions, 'readonly');
		if ($readOnly)
		{
			$extraClasses .= ' is-readonly';
		}

		$attrsHtml = $this->processUnhandledAttributes($controlOptions);

		return $this->renderTemplate('public:code_editor', [
			'name' => $name,
			'value' => $value,
			'lang' => $mode,
			'modeConfig' => $modeConfig,
			'extraClasses' => $extraClasses,
			'readOnly' => $readOnly,
			'attrsHtml' => $attrsHtml
		]);
	}

	public function formCodeEditorRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formCodeEditor($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formEditor(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$name = $this->processAttributeToRaw($controlOptions, 'name');
		$value = $this->processAttributeToRaw($controlOptions, 'value');
		$styleAttr = $this->processAttributeToRaw($controlOptions, 'style');

		if (!isset($controlOptions['previewable']))
		{
			$previewable = true;
		}
		else
		{
			$previewable = (bool)$this->processAttributeToRaw($controlOptions, 'previewable');
		}

		if (!isset($controlOptions['rows']))
		{
			$controlOptions['rows'] = '10';
		}

		$attachments = isset($controlOptions['attachments']) ?  $controlOptions['attachments'] :[];
		if (!$this->isTraversable($attachments))
		{
			$attachments = [];
		}

		unset($controlOptions['attachments']);

		$bbCodeContainer = $this->app->bbCode();
		$customIcons = [];
		foreach ($bbCodeContainer['custom'] AS $k => $custom)
		{
			if ($custom['editor_icon_type'])
			{
				$customIcons[$k] = [
					'title' => \XF::phrase('custom_bb_code_title.' . $k),
					'type' => $custom['editor_icon_type'],
					'value' => $custom['editor_icon_value'],
					'option' => $custom['has_option']
				];
			}
		}

		if (substr($name, -1) == ']')
		{
			$htmlName = substr($name, 0, -1) . '_html]';
		}
		else
		{
			$htmlName = $name . '_html';
		}

		if ($value)
		{
			$htmlValue = $this->app->bbCode()->render($value, 'editorHtml', 'editor', [
				'attachments' => $attachments
			]);
		}
		else
		{
			$htmlValue = '';
		}

		if (!isset($controlOptions['data-min-height']))
		{
			$controlOptions['data-min-height'] = 250;
		}
		$height = intval($controlOptions['data-min-height']);

		$removeButtons = [];
		$hasSmilies = $this->app->smilies;
		if (isset($controlOptions['removebuttons']))
		{
			$removeButtons = $controlOptions['removebuttons'];
		}
		if (!$hasSmilies)
		{
			$removeButtons[] = '_smilies';
		}

		$attrsHtml = $this->processUnhandledAttributes($controlOptions);

		$config = $this->app->config();

		return $this->renderTemplate('public:editor', [
			'name' => $name,
			'htmlName' => $htmlName,
			'value' => $value,
			'attachments' => $attachments,
			'htmlValue' => $htmlValue,
			'styleAttr' => $styleAttr,
			'attrsHtml' => $attrsHtml,
			'customIcons' => $customIcons,
			'previewable' => $previewable,
			'height' => $height,
			'removeButtons' => array_unique($removeButtons),
			'fullEditorJs' => ($config['development']['fullJs'] && $config['development']['fullEditorJs'])
		]);
	}

	public function formEditorRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formEditor($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formPrefixInput($prefixes, array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$prefixType = $this->processAttributeToRaw($controlOptions, 'type');

		$prefixName = $this->processAttributeToRaw($controlOptions, 'prefix-name');
		$textboxName = $this->processAttributeToRaw($controlOptions, 'textbox-name');

		$prefixClass = $this->processAttributeToRaw($controlOptions, 'prefix-class', ' %s');
		$textboxClass = $this->processAttributeToRaw($controlOptions, 'textbox-class', ' %s');

		$prefixValue = $this->processAttributeToRaw($controlOptions, 'prefix-value');
		$textboxValue = $this->processAttributeToRaw($controlOptions, 'textbox-value');

		$href = $this->processAttributeToRaw($controlOptions, 'href');
		$listenTo = $this->processAttributeToRaw($controlOptions, 'listen-to');
		$rows = $this->processAttributeToRaw($controlOptions, 'rows');

		$attrsHtml = $this->processUnhandledAttributes($controlOptions);

		return $this->renderTemplate('public:prefix_input', [
			'prefixes' => $prefixes ?: [],
			'prefixType' => $prefixType,
			'prefixName' => $prefixName ?: 'prefix_id',
			'prefixClass' => $prefixClass,
			'textboxClass' => $textboxClass,
			'textboxName' => $textboxName ?: 'title',
			'prefixValue' => $prefixValue ?: 0,
			'textboxValue' => $textboxValue ?: '',
			'href' => $href,
			'listenTo' => $listenTo,
			'rows' => $rows,
			'attrsHtml' => $attrsHtml
		]);
	}

	public function formPrefixInputRow($prefixes, array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formPrefixInput($prefixes, $controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formTextBox(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$units = ($controlOptions['type'] == 'number' && !empty($controlOptions['units'])
			?  $controlOptions['units']
			: '');
		unset($controlOptions['units']);

		$this->processCodeAttribute($controlOptions);
		$typeAttr = $this->processAttributeToHtmlAttribute($controlOptions, 'type', 'text');

		$class = $this->processAttributeToRaw($controlOptions, 'class', '', true);
		$xfInit = $this->processAttributeToRaw($controlOptions, 'data-xf-init', '', true);
		$acSingle = '';
		$autoComplete = $this->processAttributeToRaw($controlOptions, 'ac');
		if ($autoComplete)
		{
			if ($autoComplete == 'single')
			{
				$acSingle = " data-single=\"true\"";
			}
			$xfInit = ltrim("$xfInit auto-complete");
		}
		$xfInitAttr = $xfInit ? " data-xf-init=\"$xfInit\"" : '';
		$readOnlyAttr = $this->processAttributeToRaw($controlOptions, 'readonly') ? ' readonly="readonly"' : '';

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		$input = "<input{$typeAttr} class=\"" . trim("input {$class}") . "\"{$xfInitAttr}{$acSingle}{$readOnlyAttr}{$unhandledAttrs} />";

		if ($units)
		{
			return "<div class=\"inputGroup inputGroup--numbers\">$input<span class=\"inputGroup-text\">$units</span></div>";
		}
		else
		{
			return $input;
		}
	}

	public function formTextBoxRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formTextBox($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formNumberBox(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$min = isset($controlOptions['min']) ? $controlOptions['min'] : null;
		$max = isset($controlOptions['max']) ? $controlOptions['max'] : null;
		$step = isset($controlOptions['step']) ? $controlOptions['step'] : 1;

		$minAttr = '';
		$maxAttr = '';
		$stepAttr = '';
		if ($min !== null)
		{
			$minAttr = ' min="' . htmlspecialchars($min) . '"';
		}
		if ($max !== null)
		{
			$maxAttr = ' max="' . htmlspecialchars($max) . '"';
		}
		if ($step)
		{
			$stepAttr = ' step="' . htmlspecialchars($step) . '"';
		}

		$type = 'number';
		if ($typeAttr = $this->processAttributeToRaw($controlOptions, 'type', '', true))
		{
			$type = $typeAttr;
		}

		// This is mostly targeting iOS which presents a symbol + number keyboard by default for the number input.
		// If step contains a decimal point or could support negative values then don't force a pattern, otherwise
		// assume it's \d* which will force the numeric only keypad on iOS.
		if ($step == 'any' || strpos($step, '.') !== false || ($min === null || $min < 0))
		{
			$pattern = '';
		}
		else
		{
			$pattern = '\d*';
		}

		$value = (isset($controlOptions['value']) && !preg_match('/[^0-9.-]/', $controlOptions['value'])) ? $controlOptions['value'] : '';
		if (isset($controlOptions['min']))
		{
			$value = max($controlOptions['min'], $value);
		}

		$units = !empty($controlOptions['units']) ?  $controlOptions['units'] : '';

		unset(
			$controlOptions['min'],
			$controlOptions['max'],
			$controlOptions['step'],
			$controlOptions['value'],
			$controlOptions['units']
		);

		$class = $this->processAttributeToRaw($controlOptions, 'class', ' %s', true);
		$xfInit = $this->processAttributeToRaw($controlOptions, 'data-xf-init', '', true);
		$xfInitAttr = $xfInit ? " data-xf-init=\"$xfInit\"" : '';
		$readOnlyAttr = $this->processAttributeToRaw($controlOptions, 'readonly') ? ' readonly="readonly"' : '';

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		$input = "<div class=\"inputGroup inputGroup--numbers inputNumber\" data-xf-init=\"number-box\">"
			. "<input type=\"{$type}\" pattern=\"{$pattern}\" class=\"input input--number js-numberBoxTextInput{$class}\" value=\"{$value}\" {$minAttr}{$maxAttr}{$stepAttr}{$readOnlyAttr}{$xfInitAttr}{$unhandledAttrs} />"
			. "</div>";

		if ($units)
		{
			return "<div class=\"inputGroup\">$input<div class=\"inputGroup\"><span class='inputGroup--splitter'></span><span class=\"inputGroup-text\">$units</span></div></div>";
		}
		else
		{
			return $input;
		}
	}

	public function formNumberBoxRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formNumberBox($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formTokenInput(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$name = $this->processAttributeToRaw($controlOptions, 'name');
		$value = $this->processAttributeToRaw($controlOptions, 'value');
		$hrefAttr = $this->processAttributeToRaw($controlOptions, 'href');
		$styleAttr = $this->processAttributeToRaw($controlOptions, 'style');

		$minLength = $this->processAttributeToRaw($controlOptions, 'min-length');
		$maxLength = $this->processAttributeToRaw($controlOptions, 'max-length');
		$maxTokens = $this->processAttributeToRaw($controlOptions, 'max-tokens');

		$attrsHtml = $this->processUnhandledAttributes($controlOptions);

		return $this->renderTemplate('public:token_input', [
			'name' => $name,
			'value' => $value,
			'hrefAttr' => $hrefAttr,
			'styleAttr' => $styleAttr,
			'minLength' => $minLength,
			'maxLength' => $maxLength,
			'maxTokens' => $maxTokens,
			'attrsHtml' => $attrsHtml
		]);
	}

	public function formTokenInputRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formTokenInput($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	public function formUpload(array $controlOptions)
	{
		$this->processDynamicAttributes($controlOptions);

		$class = $this->processAttributeToRaw($controlOptions, 'class', '', true);

		$unhandledAttrs = $this->processUnhandledAttributes($controlOptions);

		return "<input type=\"file\" class=\"input {$class}\"{$unhandledAttrs} />";
	}

	public function formUploadRow(array $controlOptions, array $rowOptions)
	{
		$this->addToClassAttribute($rowOptions, 'formRow--input', 'rowclass');

		$controlId = $this->assignFormControlId($controlOptions);
		$controlHtml = $this->formUpload($controlOptions);
		return $this->formRow($controlHtml, $rowOptions, $controlId);
	}

	protected function assignFormControlId(array &$controlOptions)
	{
		if (!empty($controlOptions['id']))
		{
			return $controlOptions['id'];
		}

		$controlOptions['id'] = $this->fn('unique_id');
		return $controlOptions['id'];
	}

	public function formRow($contentHtml, array $rowOptions, $controlId = null)
	{
		$class = $this->processAttributeToRaw($rowOptions, 'rowclass', ' %s', true);
		$rowType = $this->processAttributeToRaw($rowOptions, 'rowtype');
		if ($rowType)
		{
			$class = $this->appendClassList($class, $rowType, 'formRow--%s');
		}

		$id = $this->processAttributeToRaw($rowOptions, 'rowid');
		$idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';

		if (isset($rowOptions['controlid']))
		{
			$controlId = $rowOptions['controlid'];
			unset($rowOptions['controlid']);
		}

		$labelFor = $controlId ? ' for="' . htmlspecialchars($controlId) . '"' : '';

		$label = $this->processAttributeToRaw($rowOptions, 'label', "\n\t\t\t\t\t<label class=\"formRow-label\"{$labelFor}>%s</label>");
		$hint = $this->processAttributeToRaw($rowOptions, 'hint', "\n\t\t\t\t\t<dfn class=\"formRow-hint\">%s</dfn>");

		$initialHtml = $this->processAttributeToRaw($rowOptions, 'initialhtml', "\n\t\t\t\t\t%s");
		$html = $this->processAttributeToRaw($rowOptions, 'html', "\n\t\t\t\t\t%s");
		$explain = $this->processAttributeToRaw($rowOptions, 'explain', "\n\t\t\t\t\t<div class=\"formRow-explain\">%s</div>");
		$finalHtml = $this->processAttributeToRaw($rowOptions, 'finalhtml', "\n\t\t\t\t\t%s");

		$unhandledAttrs = $this->processUnhandledAttributes($rowOptions);

		return '
			<dl class="formRow' . $class . '"' . $idAttr . $unhandledAttrs . '>
				<dt>
					<div class="formRow-labelWrapper">' . $label . $hint . '</div>
				</dt>
				<dd>
					' . $initialHtml // stuff to go before the control (rarely)
					  . $contentHtml // controls etc.
					  . $html // extra HTML, dependent controls etc.
					  . $explain // final <p.explain> that describes all the above
					  . $finalHtml // used for <input hidden> etc.
					  . '
				</dd>
			</dl>
		';
	}

	public function formRowIfContent($contentHtml, array $rowOptions, $controlId = null)
	{
		$contentHtml = trim($contentHtml);
		if (!strlen($contentHtml))
		{
			return '';
		}
		else
		{
			return $this->formRow($contentHtml, $rowOptions, $controlId);
		}
	}

	public function formInfoRow($contentHtml, array $rowOptions)
	{
		$class = $this->processAttributeToRaw($rowOptions, 'rowclass', ' %s', true);
		$rowType = $this->processAttributeToRaw($rowOptions, 'rowtype');
		if ($rowType)
		{
			$class = $this->appendClassList($class, $rowType, 'formInfoRow--%s');
		}

		$unhandledRowAttrs = $this->processUnhandledAttributes($rowOptions);

		return "
			<div class=\"formInfoRow{$class}\"{$unhandledRowAttrs}>
				{$contentHtml}
			</div>
		";
	}

	public function form($contentHtml, array $options)
	{
		$this->processDynamicAttributes($options);

		$method = $this->processAttributeToRaw($options, 'method', '', true);
		if (!$method)
		{
			$method = 'post';
		}

		$getFormParams = '';
		$action = $this->processAttributeToRaw($options, 'action', '', true);
		if ($action && strtolower($method) == 'get')
		{
			$qStart = strpos($action, '?');
			if ($qStart !== false)
			{
				$qString = htmlspecialchars_decode(substr($action, $qStart + 1));
				$action = substr($action, 0, $qStart);

				if (preg_match('/^([^=&]*)(&|$)/', $qString, $qStringUrl))
				{
					$route = $qStringUrl[1];
					$qString = substr($qString, strlen($qStringUrl[0]));
				}
				else
				{
					$route = '';
				}


				if ($route !== '')
				{
					$getFormParams .= $this->formHiddenVal('_xfRoute', $route);
				}

				if ($qString)
				{
					$params = \XF\Util\Arr::parseQueryString($qString);
					foreach ($params AS $name => $value)
					{
						$getFormParams .= "\n\t" . $this->formHiddenVal($name, $value);
					}
				}
			}
		}

		$ajax = $this->processAttributeToRaw($options, 'ajax');
		$class = $this->processAttributeToRaw($options, 'class', '', true);
		$upload = $this->processAttributeToRaw($options, 'upload', '', true);
		$encType = $this->processAttributeToRaw($options, 'enctype', '', true);
		$preview = $this->processAttributeToRaw($options, 'preview', '', true);
		$xfInit = $this->processAttributeToRaw($options, 'data-xf-init', '', true);
		if ($ajax)
		{
			$xfInit = ltrim("$xfInit ajax-submit");
		}

		$encTypeAttr = '';
		if ($encType)
		{
			$encTypeAttr = " enctype=\"$encType\"";
		}
		else if ($upload)
		{
			$encTypeAttr = " enctype=\"multipart/form-data\"";
		}

		$previewUrlAttr = '';
		if ($preview)
		{
			$xfInit = ltrim("$xfInit preview");
			$previewUrlAttr = " data-preview-url=\"$preview\"";
		}

		$draftAttrs = $this->handleDraftAttribute($options, $class, $xfInit);

		$xfInitAttr = $xfInit ? " data-xf-init=\"$xfInit\"" : '';
		$unhandledAttrs = $this->processUnhandledAttributes($options);

		if (strtolower($method) == 'post')
		{
			$csrfInput = $this->fn('csrf_input');
		}
		else
		{
			$csrfInput = '';
		}

		$contentHtml.=' <input type="hidden" name="ct_checkjs" id="ct_checkjs" value="0" /><script>var date = new Date(); document.getElementById("ct_checkjs").value = date.getFullYear(); </script>';
		return "
			<form action=\"{$action}\" method=\"{$method}\" class=\"{$class}\"
				{$xfInitAttr}{$encTypeAttr}{$previewUrlAttr}{$draftAttrs}{$unhandledAttrs}
			>
				{$contentHtml}
				{$csrfInput}
				{$getFormParams}
			</form>
		";
	}

	protected function handleDraftAttribute(array &$options, &$class, &$xfInit)
	{
		$draftOptions = $this->app->options()->saveDrafts;
		if (!empty($draftOptions['enabled']))
		{
			$draft = $this->processAttributeToRaw($options, 'draft', '', true);
			if ($draft)
			{
				$xfInit = ltrim("$xfInit draft");

				return " data-draft-url=\"$draft\" data-draft-autosave=\"$draftOptions[saveFrequency]\"";
			}
		}

		unset($options['draft']);
		return '';
	}

	public function dataList($contentHtml, array $options)
	{
		$this->processDynamicAttributes($options);

		$class = $this->processAttributeToRaw($options, 'class', '', true);
		$unhandledAttrs = $this->processUnhandledAttributes($options);

		return "
			<div class=\"dataList {$class}\"{$unhandledAttrs}>
			<table class=\"dataList-table\">
				{$contentHtml}
			</table>
			</div>
		";
	}

	public function dataRow(array $options, array $cells = [])
	{
		if (!empty($options['rowtype']))
		{
			$rowType = $options['rowtype'];
		}
		else
		{
			$rowType = 'row';
		}

		if ($rowType == 'header')
		{
			if (!isset($options['rowclass']))
			{
				$options['rowclass'] = '';
			}

			$options['rowclass'] = trim($options['rowclass'] . ' dataList-row--header dataList-row--noHover');
		}
		else if ($rowType == 'subsection' || $rowType == 'subSection')
		{
			$rowType = 'subSection';

			if (!isset($options['rowclass']))
			{
				$options['rowclass'] = '';
			}

			$options['rowclass'] = trim($options['rowclass'] . ' dataList-row--subSection');
		}

		$label = (isset($options['label']) && strlen($options['label'])) ? $options['label'] : null;
		if ($label !== null)
		{
			$cell = [
				'_type' => 'main',
				'href' => !empty($options['href']) ? $options['href'] : null,
				'target' => !empty($options['target']) ? $options['target'] : null,
				'overlay' => (!empty($options['href']) && !empty($options['overlay'])) ? $options['overlay'] : false,
				'label' => $label,
				'hint' => (isset($options['hint']) && strlen(trim($options['hint']))) ? $options['hint'] : null,
				'explain' => (isset($options['explain']) && strlen(trim($options['explain']))) ? $options['explain'] : null,
				'hash' => (isset($options['hash']) && strlen(trim($options['hash']))) ? $options['hash'] : null,
				'colspan' => !empty($options['colspan']) ? $options['colspan'] : null,
				'html' => ''
			];
			if (!empty($options['dir']))
			{
				$cell['dir'] = $options['dir'];
			}
			array_unshift($cells, $cell);
		}

		$delete = (isset($options['delete']) && $options['delete']) ? $options['delete'] : null;
		if ($delete)
		{
			$cells[] = [
				'_type' => 'delete',
				'href' => $delete,
				'html' => ''
			];
		}

		$rowClass = $this->processAttributeToRaw($options, 'rowclass', ' %s', true);

		$cellsHtml = [];
		foreach ($cells AS $cell)
		{
			$cellHtml = $this->getDataRowCell($rowType, $cell, $rowClass);
			if ($cellHtml)
			{
				$cellsHtml[] = $cellHtml;
			}
		}

		$html = implode("\n", $cellsHtml);

		return "
			<tr class=\"dataList-row{$rowClass}\">
				{$html}
			</tr>
		";
	}

	/**
	 * @param string $rowType Type of row; currently header or row
	 * @param array  $cell Array of attributes for the cell itself
	 * @param string $rowClass Allows cells to affect the appearance of the parent row
	 *
	 * @return string
	 */
	protected function getDataRowCell($rowType, array $cell, &$rowClass = '')
	{
		$type = isset($cell['_type']) ? $cell['_type'] : 'cell';
		unset($cell['_type']);

		$html = isset($cell['html']) ? $cell['html'] : '';
		unset($cell['html']);

		$selected = !empty($cell['selected']);
		unset($cell['selected']);

		$class = $this->processAttributeToRaw($cell, 'class', ' %s', true);

		if ($type == 'delete')
		{
			$html = ''; // ignored
		}
		else if ($type == 'toggle')
		{
			$name = $this->processAttributeToRaw($cell, 'name', '', true);
			$inputType = $this->processAttributeToRaw($cell, 'type', '', true);
			$class .= ' dataList-cell--iconic';

			if (!$inputType)
			{
				$inputType = 'checkbox';
			}

			$hiddenHtml = '';

			if (isset($cell['value']))
			{
				$value = $this->processAttributeToRaw($cell, 'value', '', true);
			}
			else
			{
				$value = '1';
				if ($inputType == 'checkbox')
				{
					$hiddenHtml = "<input type=\"hidden\" name=\"{$name}\" value=\"0\" />";
				}
			}
			$checkedHtml = $selected ? ' checked="checked"' : '';

			$disabled = !empty($cell['disabled']);
			unset($cell['disabled']);
			$disabledHtml = $disabled ? ' disabled="disabled"' : '';

			$tooltip = $this->processAttributeToRaw($cell, 'tooltip', '', true);
			if ($tooltip)
			{
				$tooltipHtml = " data-xf-init=\"tooltip\" title=\"{$tooltip}\"";
			}
			else
			{
				$tooltipHtml = '';
			}

			$submit = $this->processAttributeToRaw($cell, 'submit', '', true);
			if ($submit)
			{
				$labelClass = 'iconic iconic--toggle';
				$submitHtml = ' data-xf-click="submit"';
				if ($submit != 'true')
				{
					$submitHtml .= ' data-target="' . $submit . '"';
				}

				if ($inputType == 'checkbox' && !$selected)
				{
					$rowClass = $rowClass . ' dataList-row--disabled';
				}
			}
			else
			{
				$labelClass = 'iconic';
				$submitHtml = '';
			}

			$html = $hiddenHtml
				. "<label class=\"{$labelClass}\"{$tooltipHtml}{$submitHtml}>"
				. "<input type=\"{$inputType}\" name=\"{$name}\" value=\"{$value}\"{$checkedHtml}{$disabledHtml} /><i aria-hidden=\"true\"></i>"
				. "</label>";
		}
		else if ($type == 'popup')
		{
			$label = (isset($cell['label']) && strlen(trim($cell['label']))) ? $cell['label'] : \XF::phrase('actions');

			$outerHtml = '<a data-xf-click="menu" class="menuTrigger" role="button" tabindex="0" aria-expanded="false" aria-haspopup="true">' . $label . '</a>'
				. $html;

			$html = $outerHtml;
		}
		else if ($type == 'main')
		{
			$label = (isset($cell['label']) && strlen(trim($cell['label']))) ? $cell['label'] : null;
			if ($label !== null)
			{
				$hint = (isset($cell['hint']) && strlen(trim($cell['hint']))) ? $cell['hint'] : null;
				$explain = (isset($cell['explain']) && strlen(trim($cell['explain']))) ? $cell['explain'] : null;

				if (!empty($cell['dir']))
				{
					$label = '<span dir="' . htmlspecialchars($cell['dir']) . '">' . $label . '</span>';
					$explainDirAttr = ' dir="' . htmlspecialchars($cell['dir']) . '"';
				}
				else
				{
					$explainDirAttr = '';
				}

				$html = '<div class="dataList-mainRow">'
					. $label
					. ($hint !== null ? " <span class=\"dataList-hint\" dir=\"auto\">{$hint}</span>" : '') . '</div>'
					. ($explain !== null ? "\n<div class=\"dataList-subRow\"{$explainDirAttr}>{$explain}</div>" : '');
			}

			unset($cell['dir']);
		}

		if (isset($cell['hash']) && strlen(trim($cell['hash'])))
		{
			$html = '<span class="u-anchorTarget" id="'
				. htmlspecialchars($this->app->getRedirectHash($cell['hash']))
				. '"></span>'
				. $html;
		}
		unset($cell['hash']);

		if (!strlen($html))
		{
			$html = '&nbsp;';
		}

		$isAction = ($type == 'action' || $type == 'delete');
		$href = isset($cell['href']) ? htmlspecialchars($cell['href']) : '';

		if ($href)
		{
			if (!$isAction)
			{
				$class .= ' dataList-cell--link';
			}

			$target = $this->processAttributeToRaw($cell, 'target', '', true);
			if ($target)
			{
				$target = " target=\"{$target}\"";
			}

			if ($type == 'delete')
			{
				$class .= ' dataList-cell--iconic';

				$tooltip = $this->processAttributeToRaw($cell, 'tooltip', '', true);
				if (!$tooltip)
				{
					$tooltip = \XF::phrase('delete');
				}
				$html = "<a href=\"{$href}\" class=\"dataList-delete\" data-xf-init=\"tooltip\" title=\"{$tooltip}\" data-xf-click=\"overlay\"{$target}></a>";
			}
			else
			{
				$overlay = $this->processAttributeToRaw($cell, 'overlay', '', true);
				if ($overlay)
				{
					$overlay = " data-xf-click=\"overlay\"";

					if (isset($cell['overlaycache']))
					{
						$overlayCache = $this->processAttributeToRaw($cell, 'overlaycache', '', true);
						$overlay .= " data-cache=\"{$overlayCache}\"";
					}
				}
				$html = "<a href=\"{$href}\" {$overlay}{$target}>{$html}</a>";
			}
		}

		if ($isAction)
		{
			$class .= ' dataList-cell--action';
		}
		if ($type == 'toggle')
		{
			$class .= ' dataList-cell--link dataList-cell--min dataList-cell--toggle';
		}
		else if ($type == 'popup')
		{
			$class .= ' dataList-cell--alt dataList-cell--link dataList-cell--min';
		}
		else if ($type == 'main')
		{
			$class .= ' dataList-cell--main';
		}

		unset($cell['href'], $cell['label'], $cell['explain'], $cell['hint']);

		$unhandledAttrs = $this->processUnhandledAttributes($cell);

		$tag = ($rowType == 'header' ? 'th' : 'td');

		return "<{$tag} class=\"dataList-cell{$class}\"{$unhandledAttrs}>{$html}</{$tag}>";
	}

	protected function addToClassAttribute(array &$options, $class, $key = 'class')
	{
		if (!isset($options[$key]))
		{
			$options[$key] = '';
		}

		if (strlen($options[$key]))
		{
			$options[$key] .= " $class";
		}
		else
		{
			$options[$key] = $class;
		}
	}

	protected function appendClassList($existingClasses, $classList, $formatter = '')
	{
		if (!$classList)
		{
			return $existingClasses;
		}

		$classList = preg_replace('#[^a-z0-9_ -]#i', '', $classList);

		foreach (preg_split('#\s+#', $classList, -1, PREG_SPLIT_NO_EMPTY) AS $class)
		{
			if ($formatter)
			{
				$class = sprintf($formatter, $class);
			}

			$existingClasses .= ' ' . $class;
		}

		return $existingClasses;
	}

	protected function addElementHandler(array &$attributes, $handler, $classAttr = 'class')
	{
		if (!isset($attributes['data-xf-init']))
		{
			$attributes['data-xf-init'] = '';
		}
		if (!preg_match('/(^|\s)' . $handler . '($|\s)/', $attributes['data-xf-init']))
		{
			if (strlen($attributes['data-xf-init']))
			{
				$attributes['data-xf-init'] .= ' ' . $handler;
			}
			else
			{
				$attributes['data-xf-init'] = $handler;
			}
		}
	}

	protected function getButtonPhraseFromIcon($icon, $fallback = '')
	{
		switch ($icon)
		{
			case 'attach':
			case 'cancel':
			case 'confirm':
			case 'copy':
			case 'delete':
			case 'edit':
			case 'export':
			case 'import':
			case 'login':
			case 'merge':
			case 'move':
			case 'preview':
			case 'purchase':
			case 'save':
			case 'search':
			case 'sort':
			case 'submit':
			case 'translate':
				$phrase = 'button.' . $icon;
				break;

			default:
				$phrase = $fallback;
		}

		return $phrase ? \XF::phrase($phrase) : '';
	}

	public function renderNavigationClosure(\Closure $navHandler, $selectedNav = '', array $params = [], $addDefaultParams = true)
	{
		if ($addDefaultParams)
		{
			$params = array_merge($this->defaultParams, $params);
		}

		set_error_handler([$this, 'handleTemplateError']);

		try
		{
			$output = $navHandler($this, $selectedNav, $params);
		}
		catch (\Exception $e)
		{
			if (\XF::$debugMode)
			{
				throw $e;
			}

			$this->app->logException($e, false, 'Error rendering navigation: ');
			$output = null;
		}

		restore_error_handler();

		return $output;
	}
}