<?php

namespace Acms\Plugins\GoogleSheets\POST\GoogleSheets;

use ACMS_POST;
use Acms\Services\Facades\Session;
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
        // 認可の要求と戻りを同じ管理者のセッションに結びつけ、第三者が用意した認可コードの差し込みを防ぐ
        $state = bin2hex(random_bytes(16));
        $session = Session::handle();
        $session->set('google_sheets_oauth_state', $state);
        $session->save();
        $client->setState($state);
        $this->redirect($client->createAuthUrl());
    }
}
