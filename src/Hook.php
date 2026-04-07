<?php

namespace Acms\Plugins\ReCaptcha;

use Config;
use Acms\Plugins\ReCaptcha\Services\ReCaptcha as ReCaptchaService;

class Hook
{
    /**
     * ログイン系モジュールとコンフィグキーのマッピング
     * instanceof で判定するため、サブクラスを親クラスより先に定義すること
     *
     * @var array<string, string>
     */
    private $loginModuleConfigMap = [
        // 管理画面ログイン系 (Admin namespace, Ver. 3.1.x 以降)
        // Admin_Tfa_Recovery は Member_Tfa_Recovery を継承するため先に定義
        // Admin_Login を継承する Admin_LoginWithEmail / Admin_LoginWithVerifyCode も含む
        // Admin_Tfa_Auth は id/pass 認証済みのため beforePostFire で明示的に除外
        //
        // NOTE: Tfa_Recovery に reCAPTCHA を適用できる理由
        //   Tfa_Auth は Signin 後に HMAC 署名済み takeover トークンで mail/pass を引き回す設計で、
        //   フォームには TOTP コードのみ入力させる（セッション内フロー）。
        //   一方 Tfa_Recovery は独立したページで mail/pass/recoveryCode を直接入力させる
        //   完全に未認証のフローのため、ボット対策として reCAPTCHA を適用できる。
        'ACMS_POST_Member_Admin_Tfa_Recovery'  => 'google_recaptcha_member_admin_tfa_recovery',
        'ACMS_POST_Member_Admin_Login'         => 'google_recaptcha_member_admin_login',
        'ACMS_POST_Member_Admin_ResetPassword' => 'google_recaptcha_member_admin_reset_password',
        // 会員ログイン系 (Member namespace, Ver. 3.1.x 以降)
        // Member_Signin を継承する Member_SigninWithEmail / Member_SigninWithVerifyCode / Member_SigninRedirect も含む
        // Member_Tfa_Auth は id/pass 認証済みのため beforePostFire で明示的に除外
        // Tfa_Recovery が reCAPTCHA 対象である理由は上記 NOTE を参照
        'ACMS_POST_Member_Tfa_Recovery'        => 'google_recaptcha_member_tfa_recovery',
        'ACMS_POST_Member_Signin'              => 'google_recaptcha_member_signin',
        'ACMS_POST_Member_Signup_Submit'       => 'google_recaptcha_member_signup',
        'ACMS_POST_Member_ResetPassword'       => 'google_recaptcha_member_reset_password',
        // 会員ログイン系 (Login namespace, Ver. 3.0.x 以前)
        'ACMS_POST_Login_Tfa_Recovery'         => 'google_recaptcha_login_tfa_recovery',
        'ACMS_POST_Login_Auth'                 => 'google_recaptcha_login_auth',
        'ACMS_POST_Login_Subscribe'            => 'google_recaptcha_login_subscribe',
        'ACMS_POST_Login_Remind'               => 'google_recaptcha_login_remind',
    ];

    /**
     * ブログコンフィグをロードして返す
     *
     * @return \Field
     */
    private function loadConfig(): \Field
    {
        $config = Config::loadDefaultField();
        $config->overload(Config::loadBlogConfig(BID));
        return $config;
    }

    /**
     * recaptcha.js のキャッシュバスティング付きパスを返す
     *
     * @return string
     */
    private function getScriptPath(): string
    {
        return cacheBusting(
            '/' . DIR_OFFSET . 'extension/plugins/ReCaptcha/assets/recaptcha.js',
            SCRIPT_DIR . '/extension/plugins/ReCaptcha/assets/recaptcha.js'
        );
    }

    /**
     * グローバル変数を拡張する
     * %{RECAPTCHA_HIGHER_THAN_V30} として Ver. 3.1.0 以上かを 1/0 で参照できる
     * %{RECAPTCHA_SITEKEY} としてサイトキーを参照できる
     * %{RECAPTCHA_JS} としてキャッシュバスティング付き recaptcha.js のパスを参照できる
     *
     * @param \Field $exVars
     * @return void
     */
    public function extendsGlobalVars(&$exVars)
    {
        $exVars->set('RECAPTCHA_HIGHER_THAN_V30', version_compare(VERSION, '3.1.0', '>=') ? '1' : '0');

        $config = $this->loadConfig();
        $sitekey = (string) $config->get('google_recaptcha_sitekey');
        $exVars->set('RECAPTCHA_SITEKEY', htmlspecialchars($sitekey, ENT_QUOTES, 'UTF-8'));
        $exVars->set('RECAPTCHA_JS', $this->getScriptPath());
    }

    /**
     * POSTモジュール処理前
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param \ACMS_POST $thisModule
     */
    public function beforePostFire($thisModule)
    {
        // フォーム送信モジュール (既存の動作を維持)
        if ($thisModule instanceof \ACMS_POST_Form_Submit) {
            $id = $thisModule->Post->get('id');
            $info = $thisModule->loadForm($id);
            if ($info === false) {
                return;
            }
            $mail = $info['data']->getChild('mail');
            if ($mail->get('recaptcha_void') !== 'on') {
                return;
            }
            if (!$this->verifyToken($thisModule)) {
                $thisModule->Post->setValidator('g-recaptcha', 'validator', false);
                $thisModule->Post->set('error', 'forbidden');
            }
            return;
        }

        // Tfa_Auth 系は id/pass 認証済みのステップのため reCAPTCHA 不要
        if (class_exists('ACMS_POST_Member_Admin_Tfa_Auth') && $thisModule instanceof \ACMS_POST_Member_Admin_Tfa_Auth) {
            return;
        }
        if (class_exists('ACMS_POST_Member_Tfa_Auth') && $thisModule instanceof \ACMS_POST_Member_Tfa_Auth) {
            return;
        }

        // ログイン関連モジュール
        $configKey = null;
        foreach ($this->loginModuleConfigMap as $class => $key) {
            if ($thisModule instanceof $class) {
                $configKey = $key;
                break;
            }
        }
        if ($configKey === null) {
            return;
        }
        $config = $this->loadConfig();
        if ($config->get($configKey) !== 'on') {
            return;
        }
        if (!$this->verifyToken($thisModule)) {
            $thisModule->Post->setValidator('g-recaptcha', 'validator', false);
            $method = new \ReflectionMethod($thisModule, 'addSystemError');
            $method->setAccessible(true);
            $method->invoke($thisModule, 'IllegalAccess');
        }
    }

    /**
     * HTTPレスポンス直前に呼ばれます
     * reCAPTCHA が必要なページに自動でスクリプトを注入します
     *
     * @param string &$contents レスポンス文字列
     * @return void
     */
    public function beforeResponse(&$contents)
    {
        $config = $this->loadConfig();
        $sitekey = (string) $config->get('google_recaptcha_sitekey');

        if ($sitekey === '') {
            return;
        }
        if (!$this->needsScript($config)) {
            return;
        }

        $sitekeySafe = htmlspecialchars($sitekey, ENT_QUOTES, 'UTF-8');
        $scriptPath = $this->getScriptPath();
        $script = <<<SCRIPT
<script src="https://www.google.com/recaptcha/api.js?render={$sitekeySafe}"></script>
<script id="acms-recaptcha-js" src="{$scriptPath}" data-sitekey="{$sitekeySafe}"></script>

SCRIPT;

        $contents = preg_replace('@(?=<\s*/\s*head[^\w]*>)@i', $script, $contents) ?? $contents;
    }

    /**
     * ページに reCAPTCHA スクリプトが必要かを判定する
     *
     * @param \Field $config
     * @return bool
     */
    private function needsScript($config): bool
    {
        // 3.1.x 以降: URLに対応したシステムページ定数と設定キーのマッピング
        if (version_compare(VERSION, '3.1.0', '>=')) {
            $configConstMap = [
                'google_recaptcha_member_signin'              => defined('IS_SYSTEM_SIGNIN_PAGE') ? IS_SYSTEM_SIGNIN_PAGE : 0,
                'google_recaptcha_member_signup'              => defined('IS_SYSTEM_SIGNUP_PAGE') ? IS_SYSTEM_SIGNUP_PAGE : 0,
                'google_recaptcha_member_reset_password'      => defined('IS_SYSTEM_RESET_PASSWORD_PAGE') ? IS_SYSTEM_RESET_PASSWORD_PAGE : 0,
                'google_recaptcha_member_tfa_recovery'        => defined('IS_SYSTEM_TFA_RECOVERY_PAGE') ? IS_SYSTEM_TFA_RECOVERY_PAGE : 0,
                'google_recaptcha_member_admin_login'         => defined('IS_SYSTEM_LOGIN_PAGE') ? IS_SYSTEM_LOGIN_PAGE : 0,
                'google_recaptcha_member_admin_reset_password' => defined('IS_SYSTEM_ADMIN_RESET_PASSWORD_PAGE') ? IS_SYSTEM_ADMIN_RESET_PASSWORD_PAGE : 0,
                'google_recaptcha_member_admin_tfa_recovery'  => defined('IS_SYSTEM_ADMIN_TFA_RECOVERY_PAGE') ? IS_SYSTEM_ADMIN_TFA_RECOVERY_PAGE : 0,
            ];
            foreach ($configConstMap as $configKey => $isPage) {
                if ($isPage === 1 && $config->get($configKey) === 'on') {
                    return true;
                }
            }
            return false;
        }

        // 3.0.x 系: ACMS_GET_Login が定義する IS_LOGIN_PAGE で判定
        // ログイン・登録・パスワードリセット・TFA すべてこの定数で識別される
        if (defined('IS_LOGIN_PAGE') && IS_LOGIN_PAGE) {
            $configConstMap = [
                'google_recaptcha_login_auth' => defined('ALT') && ALT === '',
                'google_recaptcha_login_subscribe' => defined('ALT') && ALT === 'subscribe',
                'google_recaptcha_login_remind' => defined('ALT') && ALT === 'remind',
                'google_recaptcha_login_tfa_recovery' => defined('ALT') && ALT === 'recovery',
            ];
            foreach ($configConstMap as $configKey => $isPage) {
                if ($isPage && $config->get($configKey) === 'on') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * reCAPTCHAトークンを検証する
     *
     * @param \ACMS_POST $thisModule
     * @return bool
     */
    private function verifyToken($thisModule): bool
    {
        $config = $this->loadConfig();
        $secret = (string) $config->get('google_recaptcha_secret');
        $score = (float) $config->get('google_recaptcha_score', 0.5);
        $token = (string) $thisModule->Post->get('g-recaptcha-token');
        $service = new ReCaptchaService($secret);
        return $service->verify($token, $score);
    }
}
