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

	
}