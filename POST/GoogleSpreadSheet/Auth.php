<?php
namespace Acms\Plugins\GoogleSpreadSheet\POST\GoogleSpreadSheet;

use ACMS_POST;
use Acms\Plugins\GoogleSpreadSheet\Api;

class Auth extends ACMS_POST
{
    public function post()
    {
        $api = new Api();
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
