<?php

namespace Acms\Plugins\GoogleSheets\GET\GoogleSheets;

use ACMS_GET;
use Acms\Plugins\GoogleSheets\Api;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;

class Callback extends ACMS_GET
{
    /**
     * @inheritDoc
     */
    public function get()
    {
        try {
            $api = new Api();
            $client = $api->getClient();
            $base_uri = acmsLink([
                'bid' => BID,
                'admin' => 'app_google_sheets_index',
            ]);
            $code = $this->Get->get('code');
            $client->fetchAccessTokenWithAuthCode($code);
            $accessToken = $client->getAccessToken();
            $refreshToken = $client->getRefreshToken();
            $api->updateAccessToken(json_encode($accessToken), json_encode($refreshToken));

            redirect($base_uri);
        } catch (\Exception $e) {
            Logger::error(
                '【Google Sheets】Google Sheets API の認証コールバック処理に失敗しました。',
                Common::exceptionArray($e)
            );
        }

        return '';
    }
}
