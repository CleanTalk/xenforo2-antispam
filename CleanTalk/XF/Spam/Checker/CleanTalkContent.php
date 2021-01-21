<?php
namespace CleanTalk\XF\Spam\Checker;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Antispam/Cleantalk.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Common/Helper.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Antispam/CleantalkRequest.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Antispam/CleantalkResponse.php';

use CleanTalk\Antispam\Cleantalk;
use CleanTalk\Antispam\CleantalkRequest;
use CleanTalk\Antispam\CleantalkResponse;
use CleanTalk\Common\Helper as CleantalkHelper;

use XF\Util\Arr;

class CleanTalkContent extends \XF\Spam\Checker\AbstractProvider implements \XF\Spam\Checker\ContentCheckerInterface
{
    protected function getType()
    {
        return 'CleanTalkContent';
    }

    public function check(\XF\Entity\User $user, $message, array $extraParams = [])
    {
        
		$option = $this->app()->options()->spamPhrases;
		if( method_exists( 'Arr', 'stringToArray' ) ) {
            $phrases = Arr::stringToArray($option['phrases'], '/\r?\n/');
        } else {
		    $phrases = self::stringToArray( $option['phrases'], '/\r?\n/' );
        }

        $decision = 'allowed';

	    foreach ($phrases AS $phrase)
	    {
		    $phrase = trim($phrase);
		    if (!strlen($phrase))
		    {
			    continue;
		    }

		    $origPhrase = $phrase;

		    if ($phrase[0] != '/')
		    {
			    $phrase = preg_quote($phrase, '#');
			    $phrase = str_replace('\\*', '[\w"\'/ \t]*', $phrase);
			    $phrase = '#(?<=\W|^)(' . $phrase . ')(?=\W|$)#iu';
		    }
		    else
		    {
			    if (preg_match('/\W[\s\w]*e[\s\w]*$/', $phrase))
			    {
				    // can't run a /e regex
				    continue;
			    }
		    }

		    try
		    {
			    if (preg_match($phrase, $message))
			    {
				    $decision = $option['action'] == 'moderate' ? 'moderated' : 'denied';

				    $this->logDetail('spam_phrase_matched_x', [
					    'phrase' => $origPhrase
				    ]);

				    break;
			    }
		    }
		    catch (\ErrorException $e) {}
	    }

        try
        {
            $isSpam = $this->isSpam($user, $message, $extraParams);
            if (isset($isSpam['decision']))
            {
                switch ($this->app->options->ct_block_type)
                {
                    case 'rejected': $decision = 'denied'; break;
                    case 'moderate': $decision = 'moderated'; break;
                    case 'automoderate': $decision = ($isSpam['stop_queue'] == 1) ? $decision = 'denied' : $decision = 'moderated'; break;
                }
                $ct_matches[] = "Reason: ".$isSpam['reason'];
                $this->logDetail('cleantalk_matched_x', [
                    'matches' => implode(', ', $ct_matches)
                ]);
            }
        }
        catch (\GuzzleHttp\Exception\RequestException $e) { $this->app()->logException($e, false, 'CleanTalk error: '); }
        catch (\InvalidArgumentException $e) { $this->app()->logException($e, false, 'CleanTalk service error: '); }

        $this->logDecision($decision);
    }

    protected function isSpam(\XF\Entity\User $user, $message, $extraParams)
    {
        if ($user->message_count > 3 || (isset($extraParams['content_type']) && $extraParams['content_type'] == 'conversation_message' && !$this->app->options()->ct_check_pm))
            return;

        $decision = null;

        $page_set_timestamp = (isset($_COOKIE['ct_ps_timestamp']) ? $_COOKIE['ct_ps_timestamp'] : 0);
        $js_timezone = (isset($_COOKIE['ct_timezone']) ? $_COOKIE['ct_timezone'] : '');
        $first_key_timestamp = (isset($_COOKIE['ct_fkp_timestamp']) ? $_COOKIE['ct_fkp_timestamp'] : '');
        $pointer_data = (isset($_COOKIE['ct_pointer_data']) ? json_decode($_COOKIE['ct_pointer_data']) : '');

        $sender_info = json_encode(
            array(
                'REFFERRER' => (isset($_SERVER['HTTP_REFERER']))?htmlspecialchars((string) $_SERVER['HTTP_REFERER']):null,
                'post_url' => (isset($_SERVER['HTTP_REFERER']))?htmlspecialchars((string) $_SERVER['HTTP_REFERER']):null,
                'USER_AGENT' => (isset($_SERVER['HTTP_USER_AGENT']))?htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']):null,
                'js_timezone' => $js_timezone,
                'mouse_cursor_positions' => $pointer_data,
                'key_press_timestamp' => $first_key_timestamp,
                'page_set_timestamp' => $page_set_timestamp,
                'cookies_enabled' => $this->ctCookiesTest(),
                'REFFERRER_PREVIOUS' => isset($_COOKIE['ct_prev_referer']) ? $_COOKIE['ct_prev_referer'] : null,                
            )
        );

        $plugin_version = $this->app()->addOnManager()->getById('CleanTalk')->getJsonVersion();

        $ct = new Cleantalk();
        $ct->server_url = $this->app->options()->ct_server_url;
        $ct->work_url = $this->app->options()->ct_work_url;
        $ct->server_ttl = $this->app->options()->ct_server_ttl;
        $ct->server_changed = $this->app->options()->ct_server_changed;
                
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = $this->getApiKey();
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->message = $message;
        $ct_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
        $ct_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
        $ct_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
        $ct_request->agent = 'xenforo2-' . $plugin_version['version_id'];
        $ct_request->js_on = (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y")) ? 1 : 0;
        $ct_request->submit_time = time() - intval($page_set_timestamp);
        $ct_request->sender_info = $sender_info;
        $ct_result = $ct->isAllowMessage($ct_request);

	    self::ct_hash( $ct_result->id );

        //Set fastest server
        if ($ct->server_change)
        {
            $this->app->repository('XF:Option')->updateOption('ct_work_url',$ct->work_url);
            $this->app->repository('XF:Option')->updateOption('ct_server_ttl',$ct->server_ttl); 
            $this->app->repository('XF:Option')->updateOption('ct_server_changed',time());                       
        }

        if ($ct_result->errno == 0 && $ct_result->allow == 0)
        {
            $decision['decision'] = true;
            $decision['stop_queue'] = $ct_result->stop_queue;
            $decision['reason'] = $ct_result->comment;      
        }
        
        return $decision;
    }

    public function submitHam($contentType, $contentIds)
    {
        foreach ($this->getContentSpamCheckParams($contentType, $contentIds) AS $contentId => $params)
        {
            if ($params)
            {
                $this->_submitHam($params);
            }
        }
    }

    public function submitSpam($contentType, $contentIds)
    {
        foreach ($this->getContentSpamCheckParams($contentType, $contentIds) AS $contentId => $params)
        {
            if ($params)
            {
                $this->_submitSpam($params);
            }
        }
    }

    protected function ctCookiesTest()
    {   
        if(isset($_COOKIE['ct_cookies_test'])){
            
            $cookie_test = json_decode(stripslashes($_COOKIE['ct_cookies_test']), true);
            
            $check_srting = $this->getApiKey();
            foreach($cookie_test['cookies_names'] as $cookie_name){
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset($cokie_name);
            
            if($cookie_test['check_value'] == md5($check_srting)){
                return 1;
            }else{
                return 0;
            }
        }else{
            return null;
        }
    }  

    protected function getApiKey()
    {
        return trim($this->app->options()->ct_apikey);
    }

	/**
	 * Inner function - Stores ang returns cleantalk hash of current comment
	 * @param	string New hash or NULL
	 * @return 	string New hash or current hash depending on parameter
	 */
    public static function ct_hash( $new_hash = '' ) {

	    /**
	     * Current hash
	     */
	    static $hash;

	    if ( ! empty( $new_hash ) ) {
		    $hash = $new_hash;
	    }
	    return $hash;

    }

    /**
     * Split a string to an array based on pattern. Defaults to space/line break pattern.
     * Backward compatibility for Xenforo 2.0
     *
     * @param $string
     * @param string $pattern
     * @param int $limit
     *
     * @return array
     */
    private static function stringToArray($string, $pattern = '/\s+/', $limit = -1)
    {
        return (array)preg_split($pattern, trim($string), $limit, PREG_SPLIT_NO_EMPTY);
    }

}