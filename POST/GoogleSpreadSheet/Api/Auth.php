<?php

class AAPP_GoogleSpreadSheet_POST_GoogleSpreadSheet_Api_Auth extends ACMS_POST
{
    public function post()
    {
        $api = new AAPP_GoogleSpreadSheet_Api();
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
