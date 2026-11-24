<?php

namespace Acms\Plugins\GoogleSheets\GET\GoogleSheets;

use ACMS_GET;
use Acms\Services\Facades\Session;
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
        if (!sessionWithAdministration()) {
            Logger::notice('【Google Sheets plugin】管理者以外からの要求のため、処理を中断しました。', ['module' => static::class]);
            http_response_code(403);
            die();
        }

        // 使い切りにするため、検証の成否にかかわらず先に破棄する
        $session = Session::handle();
        $expectedState = $session->get('google_sheets_oauth_state');
        $session->delete('google_sheets_oauth_state');
        $session->save();
        $state = $this->Get->get('state');
        if (
            !is_string($expectedState)
            || $expectedState === ''
            || !is_string($state)
            || !hash_equals($expectedState, $state)
        ) {
            Logger::notice('【Google Sheets plugin】OAuth の state が一致しないため、処理を中断しました。', ['module' => static::class]);
            http_response_code(403);
            die();
        }

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
