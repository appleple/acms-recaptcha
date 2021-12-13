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
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $api);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if (empty($response) || $status !== 200) {
                throw new \RuntimeException($status . ' : Failed to get the json.');
            }
            $json = json_decode($response);
            if ($json->success === true) {
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
