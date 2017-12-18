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
		$form = parent::form($contentHtml, $options);
        $input = '<input type="hidden" name="ct_checkjs" id="ct_checkjs" value="0" /><script>var date = new Date(); document.getElementById("ct_checkjs").value = date.getFullYear(); document.cookie = "ct_timestamp" + "=" + encodeURIComponent(Math.floor(new Date().getTime()/1000)) + "; path=/"</script>';
        $form = str_replace('</form>', $input . '</form>', $form);
        return $form;
	}
	public function renderTemplate($template, array $params = [], $addDefaultParams = true)
	{
		$output = parent::renderTemplate($template, $params, $addDefaultParams);
		if ($this->app->options()->ct_footerlink)
		{
			$footer = "<li><div id='cleantalk_footer_link' style='width:100%;margin-right:250px;'><a href='https://cleantalk.org/xenforo-antispam-addon'>Anti-spam by CleanTalk</a> for Xenforo!</div></li>";
			$output = str_replace('<li><a href="/index.php?misc/contact" data-xf-click="overlay">', $footer . '<li><a href="/index.php?misc/contact" data-xf-click="overlay">', $output);			
		}

		return $output;
	}

	
}