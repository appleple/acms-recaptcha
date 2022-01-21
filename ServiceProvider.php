<?php

namespace Acms\Plugins\ReCaptcha;

use ACMS_App;
use Acms\Services\Common\HookFactory;
use Acms\Services\Common\InjectTemplate;

class ServiceProvider extends ACMS_App
{
    /**
     * @var string
     */
    public $version = '3.0.5';

    /**
     * @var string
     */
    public $name = 'reCAPTCHA';

    /**
     * @var string
     */
    public $author = 'com.appleple';

    /**
     * @var bool
     */
    public $module = false;

    /**
     * @var bool|string
     */
    public $menu = 'recaptcha_index';

    /**
     * @var string
     */
    public $desc = 'フォーム送信にreCAPTCHAを導入して不正なアクセスを防ぎます。';

    /**
     * サービスの初期処理
     */
    public function init()
    {
        $hook = HookFactory::singleton();
        $hook->attach('ReCaptchaHook', new Hook);

        if (ADMIN === 'app_recaptcha_index') {
            $inect = InjectTemplate::singleton();
            $inect->add('admin-main', PLUGIN_DIR . 'ReCaptcha/template/index.html');
            $inect->add('admin-topicpath', PLUGIN_DIR . 'ReCaptcha/template/topicpath.html');
        }
    }

    /**
     * インストールする前の環境チェック処理
     *
     * @return bool
     */
    public function checkRequirements()
    {
        return true;
    }

    /**
     * インストールするときの処理
     * データベーステーブルの初期化など
     *
     * @return void
     */
    public function install()
    {

    }

    /**
     * アンインストールするときの処理
     * データベーステーブルの始末など
     *
     * @return void
     */
    public function uninstall()
    {

    }

    /**
     * アップデートするときの処理
     *
     * @return bool
     */
    public function update()
    {
        return true;
    }

    /**
     * 有効化するときの処理
     *
     * @return bool
     */
    public function activate()
    {
        return true;
    }

    /**
     * 無効化するときの処理
     *
     * @return bool
     */
    public function deactivate()
    {
        return true;
    }
}
