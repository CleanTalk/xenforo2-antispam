<?php

namespace CleanTalk\XF\Pub\Controller;

use Cleantalk\Custom\Funcs;
use CleanTalk\XF\Spam\Checker\CleanTalkContent;

class Thread extends XFCP_Thread
{
    protected function finalizeThreadReply(\XF\Service\Thread\Replier $replier)
    {
        parent::finalizeThreadReply($replier);

        if ( !is_null(CleanTalkContent::ctHash()) ) {
            $post = $replier->getPost();
            Funcs::setCtHash($post->getEntityId(), CleanTalkContent::ctHash());
        }
    }
}
