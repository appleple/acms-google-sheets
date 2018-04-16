<?php

class AAPP_GoogleSpreadSheet_GET_GoogleSpreadSheet_Api_Admin extends ACMS_GET
{
    public function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $client = (new AAPP_GoogleSpreadSheet_Api())->getClient();
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
