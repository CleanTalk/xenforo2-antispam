<?php

namespace CleanTalk\XF\SubContainer;

use XF\Container;

class Spam extends XFCP_Spam
{
    public function initialize()
    {
        $initialize = parent::initialize();
        $container = $this->container;
        $parent = $this->parent;
        if ( $this->app->options()->ct_checkreg ) {
            $container->set('userProviders', function (Container $c) use ($parent) {
                $providers[] = 'CleanTalk\XF\Spam\Checker\CleanTalkUser';
                $this->app->fire('spam_user_providers', [$this, $parent, &$providers]);
                return $providers;
            }, false);
        }

        if ( $this->app->options()->ct_checkcontent || $this->app->options()->ct_check_pm ) {
            $container->set('contentProviders', function (Container $c) use ($parent) {
                $providers[] = 'CleanTalk\XF\Spam\Checker\CleanTalkContent';
                $this->app->fire('spam_content_providers', [$this, $parent, &$providers]);
                return $providers;
            }, false);
        }
    }
}
