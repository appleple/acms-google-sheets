<?php

namespace Acms\Plugins\GoogleSheets\GET\GoogleSheets;

use ACMS_GET;
use Template;
use ACMS_Corrector;
use Acms\Plugins\GoogleSheets\Api;

class Callback extends ACMS_GET
{
    public function get()
    {
        $api = new Api();
        $client = $api->getClient();
        $base_uri = BASE_URL.'bid/'.BID.'/admin/app_google_sheets_index';
        $code = $this->Get->get('code');
        $client->authenticate($code);
        $accessToken = $client->getAccessToken();
        $api->updateAccessToken($accessToken);
        redirect($base_uri);
    }
}
