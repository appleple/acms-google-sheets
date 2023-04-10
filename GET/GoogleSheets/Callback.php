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
        try {
            $api = new Api();
            $client = $api->getClient();
            $base_uri = acmsLink(array(
                'bid' => BID,
                'admin' => 'app_google_sheets_index',
            ));
            $code = $this->Get->get('code');
            $client->authenticate($code);
            $accessToken = $client->getAccessToken();
            $api->updateAccessToken(json_encode($accessToken));

            redirect($base_uri);
        } catch (\Exception $e) {}

        return '';
    }
}
