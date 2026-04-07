<?php

namespace Acms\Plugins\ReCaptcha\Services;

class ReCaptcha
{
    /**
     * ReCaptcha エンドポイント
     *
     * @var string
     */
    private const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';


    /**
     * reCAPTCHAシークレットキー
     * @var string
     */
    private $secret;

    /**
     * コンストラクタ
     *
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * reCAPTCHAトークンをGoogleに送信して検証する
     *
     * @param string $token
     * @param float $minScore
     * @return bool
     */
    public function verify(string $token, float $minScore): bool
    {
        if ($token === '') {
            return false;
        }
        $endpoint = $this::ENDPOINT . '?secret=' . $this->secret . '&response=' . $token;
        try {
            $curl = curl_init($endpoint);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl); // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
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
