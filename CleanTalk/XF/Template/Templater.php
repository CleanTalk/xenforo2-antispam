<?php

namespace CleanTalk\XF\Template;

require_once \XF::getRootDirectory() . '/src/addons/CleanTalk/lib/autoload.php';

use Cleantalk\Custom\Funcs as CleantalkFuncs;

class Templater extends \XF\Template\Templater
{
    public function form($contentHtml, array $options)
    {
        $form = parent::form($contentHtml, $options);

        $input = '<input type="hidden" name="ct_checkjs" id="ct_checkjs" value="0" /><script>var date = new Date(); document.getElementById("ct_checkjs").value = date.getFullYear(); var d = new Date(), 
			ctTimeMs = new Date().getTime(),
			ctMouseEventTimerFlag = true, //Reading interval flag
			ctMouseData = "[",
			ctMouseDataCounter = 0;
		
		function ctSetCookie(c_name, value) {
			document.cookie = c_name + "=" + encodeURIComponent(value) + "; path=/; samesite=lax";
		}
		
		ctSetCookie("ct_ps_timestamp", Math.floor(new Date().getTime()/1000));
		ctSetCookie("ct_fkp_timestamp", "0");
		ctSetCookie("ct_pointer_data", "0");
		ctSetCookie("ct_timezone", "0");
		setTimeout(function(){
			ctSetCookie("ct_timezone", d.getTimezoneOffset()/60*(-1));
		},1000);
		
		//Reading interval
		var ctMouseReadInterval = setInterval(function(){
				ctMouseEventTimerFlag = true;
			}, 150);
			
		//Writting interval
		var ctMouseWriteDataInterval = setInterval(function(){
				var ctMouseDataToSend = ctMouseData.slice(0,-1).concat("]");
				ctSetCookie("ct_pointer_data", ctMouseDataToSend);
			}, 1200);
		
		//Stop observing function
		function ctMouseStopData(){
			if(typeof window.addEventListener == "function")
				window.removeEventListener("mousemove", ctFunctionMouseMove);
			else
				window.detachEvent("onmousemove", ctFunctionMouseMove);
			clearInterval(ctMouseReadInterval);
			clearInterval(ctMouseWriteDataInterval);				
		}
		
		//Logging mouse position each 300 ms
		var ctFunctionMouseMove = function output(event){
			if(ctMouseEventTimerFlag == true){
				var mouseDate = new Date();
				ctMouseData += "[" + Math.round(event.pageY) + "," + Math.round(event.pageX) + "," + Math.round(mouseDate.getTime() - ctTimeMs) + "],";
				ctMouseDataCounter++;
				ctMouseEventTimerFlag = false;
				if(ctMouseDataCounter >= 100)
					ctMouseStopData();
			}
		}
		
		//Stop key listening function
		function ctKeyStopStopListening(){
			if(typeof window.addEventListener == "function"){
				window.removeEventListener("mousedown", ctFunctionFirstKey);
				window.removeEventListener("keydown", ctFunctionFirstKey);
			}else{
				window.detachEvent("mousedown", ctFunctionFirstKey);
				window.detachEvent("keydown", ctFunctionFirstKey);
			}
		}
		
		//Writing first key press timestamp
		var ctFunctionFirstKey = function output(event){
			var KeyTimestamp = Math.floor(new Date().getTime()/1000);
			ctSetCookie("ct_fkp_timestamp", KeyTimestamp);
			ctKeyStopStopListening();
		}

		if(typeof window.addEventListener == "function"){
			window.addEventListener("mousemove", ctFunctionMouseMove);
			window.addEventListener("mousedown", ctFunctionFirstKey);
			window.addEventListener("keydown", ctFunctionFirstKey);
		}else{
			window.attachEvent("onmousemove", ctFunctionMouseMove);
			window.attachEvent("mousedown", ctFunctionFirstKey);
			window.attachEvent("keydown", ctFunctionFirstKey);
		}</script>';

        $form = str_replace('</form>', $input . '</form>', $form);

        return $form;
    }

    public function renderTemplate(
        $template,
        array $params = [],
        $addDefaultParams = true,
        \XF\Template\ExtensionSet $extensionOverrides = null
    ) {
        $output = parent::renderTemplate($template, $params, $addDefaultParams, $extensionOverrides);
        static $show_flag = true;

        if ( $show_flag ) {
            $show_flag = false;

            if ( !headers_sent() ) {
                CleantalkFuncs::ctSetCookie();
            }

            try {
                CleantalkFuncs::ctRemoteCalls();
                CleantalkFuncs::apbctRunCron();
            } catch (\Exception $e) {
                error_log(var_export($e->getMessage(), true));
            }

            if ( $this->app->options()->ct_sfw && $_SERVER["REQUEST_METHOD"] === 'GET' && $_SERVER['SCRIPT_NAME'] !== '/admin.php' ) {
                CleantalkFuncs::sfwCheck();
            }

            if ( $_SERVER["REQUEST_METHOD"] === 'GET' && $_SERVER['SCRIPT_NAME'] === '/admin.php' ) {
                //key error render
                $key_error = isset($this->app->options()['ct_apikey_error'])
                    ? $this->app->options()['ct_apikey_error']
                    : '';
                if ( !empty($key_error) ) {
                    preg_match('/name="options\[ct_apikey\]".*xf[uU]id.*>/', $output, $matches);
                    $replace_with = '<div class="formRow-explain" style="color:red">'
                        . 'CleanTalk Error: '
                        . $key_error
                        . '</div>';
                    if ( isset($matches[0]) ) {
                        $replace_this = $matches[0];
                        $output = str_replace($replace_this, $replace_this . $replace_with, $output);
                    }
                }
            }
        }

        if ( $this->app->options()->ct_footerlink ) {
            $footer = "<li><div id='cleantalk_footer_link' style='width:100%;margin-right:250px;'><a href='https://cleantalk.org/xenforo-antispam-addon'>Anti-spam by CleanTalk</a> for Xenforo!</div></li>";
            $output = str_replace(
                '<div class="p-footer-row-opposite">',
                '<div class="p-footer-row-opposite"><ul class="p-footer-linkList">' . $footer,
                $output
            );
        }

        return $output;
    }
}
