<?php

namespace Acms\Plugins\GoogleSheets;

use ACMS_App;
use Acms\Services\Common\HookFactory;
use Acms\Services\Common\InjectTemplate;

class ServiceProvider extends ACMS_App
{
    /**
     * @var string
     */
    public $version = '1.0.17';

    /**
     * @var string
     */
    public $name = 'Google Sheets';

    /**
     * @var string
     */
    public $author = 'com.appleple';

    /**
     * @var bool
     */
    public $module = false;

    /**
     * @var string
     */
    public $menu = 'google_sheets_index';

    /**
     * @var string
     */
    public $desc = 'フォームの内容を Google スプレッドシートに流し込むためのアプリです。';

    /**
     * サービスの起動処理
     */
    public function init()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';
        $hook = HookFactory::singleton();
        $hook->attach('GoogleSheets', new Hook);
        $inject = InjectTemplate::singleton();

        if (ADMIN === 'app_google_sheets_index') {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'GoogleSheets/template/admin/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'GoogleSheets/template/admin/main.html');
        } elseif (ADMIN === 'app_google_sheets_callback') {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'GoogleSheets/template/admin/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'GoogleSheets/template/admin/callback.html');
        }
        $inject->add('admin-form', PLUGIN_DIR . 'GoogleSheets/template/admin/form.html');
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
