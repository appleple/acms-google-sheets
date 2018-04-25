<?php

namespace Acms\Plugins\GoogleSheets\GET\GoogleSheets;

use ACMS_GET;
use Template;
use ACMS_Corrector;
use Acms\Plugins\GoogleSheets\Api;

class Admin extends ACMS_GET
{
    public function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        try {
            $client = (new Api())->getClient();
            $authorized = 'false';
            if ($client->getAccessToken() && !$client->isAccessTokenExpired()) {
                $authorized = 'true';
            }
            $Tpl->add(null, array(
                'authorized' => $authorized
            ));
        } catch (\Exception $e) {}

        return $Tpl->get();
    }
}
