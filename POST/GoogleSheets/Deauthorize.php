<?php
namespace Acms\Plugins\GoogleSheets\POST\GoogleSheets;

use ACMS_POST;
use DB;
use SQL;
use Cache;
use Acms\Plugins\GoogleSheets\Api;

class Deauthorize extends ACMS_POST
{
    public function post()
    {
        $api = new Api();
        $client = $api->getClient();
        $isRevoked = $client->revokeToken();

        if ($isRevoked === false) {
            return $this->Post;
        }

        $this->deleteAccessToken();

        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_sheets_index',
        )));
    }

    /**
     * コンフィグに保存されているアクセストークンを削除する
     */
    protected function deleteAccessToken()
    {
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereIn('config_key', array('google_spreadsheet_accesstoken', 'google_spreadsheet_refreshtoken'));
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        if (class_exists('Cache')) {
            Cache::flush('config');
        }
    }
}
