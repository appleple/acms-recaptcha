<?php

namespace Acms\Plugins\ReCaptcha;

class Hook
{
    /**
     * ReCaptcha エンドポイント
     *
     * @var string
     */
    protected $endpoint = "https://www.google.com/recaptcha/api/siteverify";

    /**
     * POSTモジュール処理前
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param \ACMS_POST $thisModule
     */
    public function beforePostFire($thisModule)
    {
        $moduleName = get_class($thisModule);
        if ($moduleName !== 'ACMS_POST_Form_Submit') {
            return;
        }
        $secret = config('google_recaptcha_secret');
        $response = $thisModule->Post->get('g-recaptcha-token');
        $api = $this->endpoint . "?secret=${secret}&response=${response}";
        $valid = false;

        if ($check = @file_get_contents($api)) {
            $check = json_decode($check);
            if ($check->success === true) {
                $valid = true;
            }
        }
        if (!$valid) {
            $thisModule->Post->setMethod('g-recaptcha', 'validator', false);
            $thisModule->Post->set('step', 'forbidden');
        }
    }
}
