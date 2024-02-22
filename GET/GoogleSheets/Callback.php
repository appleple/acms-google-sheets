<?php

namespace Acms\Plugins\GoogleSheets\GET\GoogleSheets;

use ACMS_GET;
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
            $client->fetchAccessTokenWithAuthCode($code);
            $accessToken = $client->getAccessToken();
            $refreshToken = $client->getRefreshToken();
            $api->updateAccessToken(json_encode($accessToken), json_encode($refreshToken));

            redirect($base_uri);
        } catch (\Exception $e) {}

        return '';
    }
}
