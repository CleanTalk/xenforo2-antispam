<?php

namespace CleanTalk\XF\Template;

use XF\App;
use XF\Language;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Router;
use XF\Util\Arr;

class Templater extends XFCP_Templater
{
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

	
}