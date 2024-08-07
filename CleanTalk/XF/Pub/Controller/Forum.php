<?php

namespace CleanTalk\XF\Pub\Controller;

use CleanTalk\XF\Spam\Checker\CleanTalkContent;

class Forum extends XFCP_Forum
{
    protected function finalizeThreadCreate(\XF\Service\Thread\Creator $creator)
    {
        parent::finalizeThreadCreate($creator);

        if ( !is_null(CleanTalkContent::ctHash()) ) {
            $post = $creator->getPost();

            $db = \XF::db();
            $db->update(
                'xf_post',
                array('ct_hash' => CleanTalkContent::ctHash()),
                'post_id = ' . $post->getEntityId()
            );
        }
    }
}
