<?php

namespace Acms\Plugins\GoogleSpreadSheet;
use Acms\Services\Facades\Storage;
use Google_Client;
use Google_Service_Sheets;

class Api
{
    public function __construct()
    {
        $scopes = implode(' ', array(
                Google_Service_Sheets::SPREADSHEETS)
        );
        $client = new Google_Client();
        $idJsonPath = config('spreadsheet_clientid_json');
        $client->setApplicationName('ACMS');
        $client->setScopes($scopes);
        $this->client = $client;
        try {
            $this->setAuthConfig($idJsonPath);
        } catch ( Exception $e ) {
            return;
        }
        $client->setAccessType('offline');
        $client->setApprovalPrompt("force");
        $redirect_uri = BASE_URL . 'bid/' . BID . '/admin/app_google_spreadsheet_callback';
        $base_uri = BASE_URL . 'bid/' . BID . '/admin/app_google_spreadsheet_index';
        $client->setRedirectUri($redirect_uri);
        $accessToken = json_decode(config('google_spreadsheet_accesstoken'), true);
        if ( $accessToken ) {
            $client->setAccessToken($accessToken);
            if ( $client->isAccessTokenExpired() ) {
                $refreshToken = $client->getRefreshToken();
                $client->refreshToken($refreshToken);
                $accessToken = $client->getAccessToken();
                $this->updateAccessToken($accessToken);
            }
        }
    }

    public function setAuthConfig($json)
    {
        if ( !Storage::exists($json) ) {
            throw new \RuntimeException('Failed to open ' . $json);
        }
        $json = file_get_contents($json);
        $data = json_decode($json);
        $key = isset($data->installed) ? 'installed' : 'web';
        if ( !isset($data->$key) ) {
            throw new Google_Exception("Invalid client secret JSON file.");
        }
        $this->client->setClientId($data->$key->client_id);
        $this->client->setClientSecret($data->$key->client_secret);
        if ( isset($data->$key->redirect_uris) ) {
            $this->client->setRedirectUri($data->$key->redirect_uris[0]);
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getAccessToken()
    {
        $accessToken = json_decode(config('google_spreadsheet_accesstoken'), true);
        return $accessToken;
    }

    public function updateAccessToken($accessToken)
    {
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereOpr('config_key', 'google_spreadsheet_accesstoken');
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        $InsertSQL = SQL::newInsert('config');
        $InsertSQL->addInsert('config_key', 'google_spreadsheet_accesstoken');
        $InsertSQL->addInsert('config_value', json_encode($accessToken));
        $InsertSQL->addInsert('config_blog_id', BID);
        $DB->query($InsertSQL->get(dsn()), 'exec');
    }
}
