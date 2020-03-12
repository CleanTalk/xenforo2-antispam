<?php
namespace CleanTalk\XF\Pub\Controller;

use CleanTalk\XF\Spam\Checker\CleanTalkContent;

class Thread extends XFCP_Thread
{

	protected function finalizeThreadReply(\XF\Service\Thread\Replier $replier)
	{

		parent::finalizeThreadReply( $replier );

		if( ! is_null( CleanTalkContent::ct_hash() ) ) {

			$post = $replier->getPost();

			$db = \XF::db();
			$db->update(
				'xf_post',
				array( 'ct_hash' => CleanTalkContent::ct_hash()  ),
				'post_id = ' . $post->getEntityId()
			);

		}

	}

}