<?php

class AAPP_GoogleSpreadSheet_GET_GoogleSpreadSheet_Api_Callback extends ACMS_GET
{
    public function get()
    {
        $api = new AAPP_GoogleSpreadSheet_Api();
        $client = $api->getClient();
        $base_uri = BASE_URL.'bid/'.BID.'/admin/app_google_spreadsheet_index';
        $code = $this->Get->get('code');
        $client->authenticate($code);
        $accessToken = $client->getAccessToken();
        $api->updateAccessToken($accessToken);
        redirect($base_uri);
    }
}
