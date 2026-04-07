<?php

namespace Acms\Plugins\ReCaptcha\Services;

class ReCaptcha
{
    /**
     * ReCaptcha エンドポイント
     *
     * @var string
     */
    protected $endpoint = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * reCAPTCHAトークンをGoogleに送信して検証する
     *
     * @param string $secret
     * @param string $token
     * @param float $minScore
     * @return bool
     */
    public function verify(string $secret, string $token, float $minScore): bool
    {
        if ($token === '') {
            return false;
        }
        $api = $this->endpoint . '?secret=' . $secret . '&response=' . $token;
        try {
            $curl = curl_init($api);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl);
            }

            if ($result === false || $status !== 200) {
                throw new \RuntimeException($status . ' : Failed to get the json.');
            }
            $json = json_decode($result, true);
            if (
                isset($json['success']) &&
                isset($json['score']) &&
                $json['success'] === true &&
                $json['score'] >= $minScore
            ) {
                return true;
            }
        } catch (\Throwable $th) {
            if (class_exists('AcmsLogger')) {
                \AcmsLogger::error('【reCAPTCHA plugin】reCAPTCHAの検証に失敗しました', \Common::exceptionArray($th));
            } else {
                userErrorLog('ACMS Warning: reCAPTCHA: ' . $th->getMessage());
            }
        }
        return false;
    }
}
