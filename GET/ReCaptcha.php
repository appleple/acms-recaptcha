<?php

namespace Acms\Plugins\ReCaptcha\GET;

use ACMS_GET;
use ACMS_Corrector;
use Template;
use Config;

/**
 * Class ReCaptcha
 * @package Acms\Plugins\ReCaptcha\GET
 */
class ReCaptcha extends ACMS_GET
{
    public function get()
    {
        $config = Config::loadDefaultField();
        $config->overload(Config::loadBlogConfig(BID));

        $key = $config->get('google_recaptcha_sitekey');
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $tpl->add(null, array(
            'sitekey' => $key,
        ));
        return $tpl->get();
    }
}
