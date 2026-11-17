<?php

namespace CleanTalk;

use Cleantalk\Custom\Funcs as CleantalkFuncs;

class Listener
{
    public static function appStart(\XF\App $app)
    {
        require_once \XF::getRootDirectory() . '/src/addons/CleanTalk/lib/autoload.php';

        if ( !headers_sent() ) {
            CleantalkFuncs::ctSetCookie();
        }

        try {
            CleantalkFuncs::ctRemoteCalls();
            CleantalkFuncs::apbctRunCron();
        } catch ( \Exception $e ) {
            \XF::logException($e, false, 'CleanTalk request initialization: ');
        }

        if (
            $app instanceof \XF\Pub\App
            && $app->request()->isGet()
            && $app->options()->ct_sfw
        ) {
            CleantalkFuncs::sfwCheck();
        }
    }
}
