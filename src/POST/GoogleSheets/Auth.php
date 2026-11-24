<?php

namespace Acms\Plugins\GoogleSheets\POST\GoogleSheets;

use ACMS_POST;
use Acms\Services\Facades\Logger;
use Acms\Plugins\GoogleSheets\Api;

class Auth extends ACMS_POST
{
    /**
     * @inheritDoc
     */
    public function post()
    {
        if (!sessionWithAdministration()) {
            Logger::notice('【Google Sheets plugin】管理者以外からの要求のため、処理を中断しました。', ['module' => static::class]);
            http_response_code(403);
            die();
        }

        $api = new Api();
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
