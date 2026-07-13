<?php

namespace Acms\Plugins\GoogleSheets\GET\GoogleSheets;

use ACMS_GET;
use Template;
use ACMS_Corrector;
use Acms\Plugins\GoogleSheets\Api;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;

class Admin extends ACMS_GET
{
    /**
     * @return string
     */
    public function get(): string
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        try {
            $api = new Api();
            $client = $api->getClient();
            $authorized = 'false';
            if ($client->getAccessToken() && !$client->isAccessTokenExpired()) {
                $authorized = 'true';
            }
            $Tpl->add(null, [
                'authorized' => $authorized
            ]);
        } catch (\Exception $e) {
            Logger::warning(
                '【Google Sheets】Google Sheets API の認証状態確認に失敗しました。',
                Common::exceptionArray($e)
            );
        }

        return $Tpl->get();
    }
}
