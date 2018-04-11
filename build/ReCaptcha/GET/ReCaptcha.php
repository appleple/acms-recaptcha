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
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $Tpl->add(null, array(
            'sitekey' => config('google_recaptcha_sitekey'),
        ));

        return $Tpl->get();
    }
}
