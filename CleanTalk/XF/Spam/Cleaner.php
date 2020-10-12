<?php
namespace CleanTalk\XF\Spam;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Antispam/Cleantalk.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Antispam/CleantalkRequest.php';

use CleanTalk\Antispam\Cleantalk;
use CleanTalk\Antispam\CleantalkRequest;

class Cleaner extends XFCP_Cleaner
{

	protected function writeLog()
	{
		parent::writeLog();

		// Return if the api key not exist
		if( ! isset( $this->app->options()->ct_apikey ) || empty( $this->app->options()->ct_apikey ) ) {
			return;
		}

		$hashes = array();

		// Spam cleaner actions: Delete spammer's messages
		if( array_key_exists( 'post', $this->log ) ) {

			$post = $this->log['post'];

			if( isset( $post['postIds'] ) && ! empty( $post['postIds'] ) ) {

				$db = \XF::db();
				// Get users's posts hashes
				$post_hashes = $db->fetchAllColumn(
					'SELECT ct_hash FROM xf_post WHERE post_id IN (' . implode( ',', $post['postIds'] ) . ')'
				);

				$hashes = array_merge( $hashes, $post_hashes );

			}
		}

		// Spam cleaner actions: Delete spammer's threads
		if( array_key_exists( 'thread', $this->log ) ) {

			$thread = $this->log['thread'];

			if( isset( $thread['threadIds'] ) && ! empty( $thread['threadIds'] ) ) {

				$db = \XF::db();
				// Get first thread's posts hashes
				$thread_first_posts_hashes = $db->fetchAllColumn(
					 'SELECT `ct_hash` FROM `xf_post` WHERE `post_id` IN (
						    	SELECT `first_post_id` FROM `xf_thread` WHERE `thread_id` IN (' . implode( ',', $thread['threadIds'] ) . ')  
						    )'
				);

				$hashes = array_merge( $hashes, $thread_first_posts_hashes );

			}
		}

		if( $hashes ) {

			$hashes_str = '';
			foreach( $hashes as $hash ) {
				$hashes_str .=  $hash . ':0;';
			}

			$this->ct_send_feedback( $hashes_str );

		}

	}

	private function ct_send_feedback( $feedback_request )
	{

		$ct_request = new CleantalkRequest(array(
			// General
			'auth_key' => trim($this->app->options()->ct_apikey),
			// Additional
			'feedback' => $feedback_request,
		));

		$ct = new Cleantalk();

		$ct->server_url = $this->app->options()->ct_server_url;
		$ct->work_url = $this->app->options()->ct_work_url;
		$ct->server_ttl = $this->app->options()->ct_server_ttl;
		$ct->server_changed = $this->app->options()->ct_server_changed;

		$ct->sendFeedback($ct_request);

	}

}