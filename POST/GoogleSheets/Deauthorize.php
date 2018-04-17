<?php
namespace Acms\Plugins\GoogleSheets\POST\GoogleSheets;

use ACMS_POST;
use DB;
use SQL;

class Deauthorize extends ACMS_POST
{
    public function post()
    {
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereOpr('config_key', 'google_spreadsheet_accesstoken');
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_sheets_index',
        )));
    }
}
