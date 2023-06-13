<?php
namespace Acms\Plugins\GoogleSheets\POST\GoogleSheets;

use ACMS_POST;
use DB;
use SQL;
use Cache;

class Deauthorize extends ACMS_POST
{
    public function post()
    {
        if (class_exists('Cache')) {
            Cache::flush('config');
        }
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereIn('config_key', array('google_spreadsheet_accesstoken', 'google_spreadsheet_refreshtoken'));
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_sheets_index',
        )));
    }
}
