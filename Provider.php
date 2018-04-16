<?php

use Acms\Services\Facades\Storage;

class AAPP_GoogleSpreadSheet extends ACMS_APP
{
    public $version     = '1.0.0';
    public $name        = 'SpreadSheet';
    public $author      = 'com.appleple';
    public $module      = false;
    public $menu        = 'google_spreadsheet_index';
    public $desc        = 'フォームの内容を Google SpreadSheetに流し込むためのアプリです。';

    /**
     * サービスの起動処理
     */
    public function serviceProvider()
    {
        require_once dirname(__FILE__).'/vendor/autoload.php';
        $Hook = ACMS_Hook::singleton();
        if (HOOK_ENABLE && class_exists('AAPP_GoogleSpreadSheet_Hook')) {
            $Hook->attach('GoogleSpreadSheet', new AAPP_GoogleSpreadSheet_Hook());
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
        function makeFile($path)
        {
            $theme = config('theme');
            $to = THEMES_DIR . $theme . $path;
            $from = AAPP_LIB_DIR . 'GoogleSpreadSheet/theme' . $path;
            Storage::makeDirectory(THEMES_DIR . $theme . '/admin/app/google/spreadsheet');
    
            if (!Storage::exists($to)) {
                Storage::copy($from, $to);
            }
        }
        makeFile('/admin/app/google/spreadsheet/index.html');
        makeFile('/admin/app/google/spreadsheet/callback.html');
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
