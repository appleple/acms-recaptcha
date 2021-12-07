<?php

namespace Acms\Plugins\ReCaptcha;

use Config;

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
        $config = Config::loadDefaultField();
        $config->overload(Config::loadBlogConfig(BID));

        $secret = $config->get('google_recaptcha_secret');
        $response = $thisModule->Post->get('g-recaptcha-token');
        $api = $this->endpoint . "?secret=${secret}&response=${response}";
        $valid = false;
        
        try {
            $req = \Http::init($api, 'GET');
            $response = $req->send();
            $check = $response->getResponseBody();
            $check = json_decode($check);
            if ($check->success === true) {
                $valid = true;
            }
        } catch (\Exception $e) {
            userErrorLog('ACMS Warning: reCAPTCHA: ' . $e->getMessage());
        }
        
        if (!$valid) {
            $thisModule->Post->setValidator('g-recaptcha', 'validator', false);
            $thisModule->Post->set('error', 'forbidden');
        }
    }
}
