<?php

namespace CleanTalk\XF\Pub\Controller;

use Cleantalk\Custom\Funcs;
use CleanTalk\XF\Spam\Checker\CleanTalkContent;

class Forum extends XFCP_Forum
{
    protected function finalizeThreadCreate(\XF\Service\Thread\Creator $creator)
    {
        parent::finalizeThreadCreate($creator);

        if ( !is_null(CleanTalkContent::ctHash()) ) {
            $post = $creator->getPost();
            Funcs::setCtHash($post->getEntityId(), CleanTalkContent::ctHash());
        }
    }
}
