<?php

namespace Acms\Plugins\ReCaptcha\GET;

use ACMS_GET;
use ACMS_Corrector;
use Template;

/**
 * Class ReCaptcha
 * @package Acms\Plugins\ReCaptcha\GET
 */
class ReCaptcha extends ACMS_GET
{
    public function get()
    {
        $key = config('google_recaptcha_sitekey');
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $tpl->add(null, array(
            'sitekey' => $key,
        ));
        return $tpl->get();
    }
}
