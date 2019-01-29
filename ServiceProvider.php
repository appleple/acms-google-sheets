<?php
namespace Acms\Plugins\GoogleSheets;

use ACMS_App;
use Acms\Services\Common\HookFactory;
use Acms\Services\Common\InjectTemplate;
use Acms\Services\Facades\Storage;

class ServiceProvider extends ACMS_App
{
    public $version     = '1.0.0';
    public $name        = 'Google Sheets';
    public $author      = 'com.appleple';
    public $module      = false;
    public $menu        = 'google_sheets_index';
    public $desc        = 'フォームの内容を Google スプレッドシートに流し込むためのアプリです。';

    /**
     * サービスの起動処理
     */
    public function init()
    {
        require_once dirname(__FILE__).'/vendor/autoload.php';
        $hook = HookFactory::singleton();
        $hook->attach('GoogleSheets', new Hook);
        $inject = InjectTemplate::singleton();

        if (ADMIN === 'app_google_sheets_index') {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'GoogleSheets/theme/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'GoogleSheets/theme/index.html');
        } else if (ADMIN === 'app_google_sheets_callback') {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'GoogleSheets/theme/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'GoogleSheets/theme/callback.html');
        }
        $inject->add('admin-form', PLUGIN_DIR . 'GoogleSheets/theme/form.html');
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
