<?php
namespace Acms\Plugins\GoogleSheets\POST\GoogleSheets;

use ACMS_POST;
use Acms\Plugins\GoogleSheets\Api;

class Auth extends ACMS_POST
{
    public function post()
    {
        $api = new Api();
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
