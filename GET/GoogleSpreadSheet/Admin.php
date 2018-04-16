<?php

namespace Acms\Plugins\GoogleSpreadSheet\GET\GoogleSpreadSheet;

use ACMS_GET;
use Template;
use ACMS_Corrector;
use Acms\Plugins\GoogleSpreadSheet\Api;

class Admin extends ACMS_GET
{
    public function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $client = (new Api())->getClient();
        $authorized = 'false';
        if ($client->getAccessToken() && !$client->isAccessTokenExpired()) {
            $authorized = 'true';
        }
        $Tpl->add(null, array(
            'authorized' => $authorized
        ));
        return $Tpl->get();
    }
}
