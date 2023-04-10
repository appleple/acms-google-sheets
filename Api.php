<?php

namespace Acms\Plugins\GoogleSheets;

use Acms\Services\Facades\Storage;
use DB;
use SQL;
use Config;
use Cache;
use Google_Client;
use Google_Service_Sheets;
use Google_Exception;

class Api
{
    /**
     * Api constructor.
     */
    public function __construct()
    {
        $scopes = implode(' ', array(Google_Service_Sheets::SPREADSHEETS));
        $client = new Google_Client();

        $this->config = Config::loadDefaultField();
        $this->config->overload(Config::loadBlogConfig(BID));

        $idJsonPath = $this->config->get('spreadsheet_clientid_json');
        $client->setApplicationName('ACMS');
        $client->setScopes($scopes);
        $this->client = $client;
        $this->setAuthConfig($idJsonPath);
        $client->setAccessType('offline');
        $client->setApprovalPrompt("force");
        $redirect_uri = acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_sheets_callback',
        ));
        $client->setRedirectUri($redirect_uri);
        $accessToken = json_decode($this->config->get('google_spreadsheet_accesstoken'), true);
        if ($accessToken) {
            $client->setAccessToken($accessToken);
            if ($client->isAccessTokenExpired()) {
                $refreshToken = $client->getRefreshToken();
                try {
                    $client->refreshToken($refreshToken);
                    $accessToken = $client->getAccessToken();
                    $this->updateAccessToken(json_encode($accessToken));
                } catch (\Exception $e) {
                    userErrorLog('ACMS Error: In GoogleSheets extension -> ' . $e->getMessage());
                    $this->updateAccessToken('');
                }
            }
        }
    }

    public function setAuthConfig($json)
    {
        if (!Storage::exists($json)) {
            throw new \RuntimeException('Failed to open ' . $json);
        }
        $json = file_get_contents($json);
        $data = json_decode($json);
        $key = isset($data->installed) ? 'installed' : 'web';
        if (!isset($data->$key)) {
            throw new Google_Exception("Invalid client secret JSON file.");
        }
        $this->client->setClientId($data->$key->client_id);
        $this->client->setClientSecret($data->$key->client_secret);
        if (isset($data->$key->redirect_uris)) {
            $this->client->setRedirectUri($data->$key->redirect_uris[0]);
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getAccessToken()
    {
        $accessToken = json_decode($this->config->get('google_spreadsheet_accesstoken'), true);
        return $accessToken;
    }

    public function updateAccessToken($accessToken)
    {
        if (class_exists('Cache')) {
            Cache::flush('config');
        }
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereOpr('config_key', 'google_spreadsheet_accesstoken');
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        $InsertSQL = SQL::newInsert('config');
        $InsertSQL->addInsert('config_key', 'google_spreadsheet_accesstoken');
        $InsertSQL->addInsert('config_value', $accessToken);
        $InsertSQL->addInsert('config_blog_id', BID);
        $DB->query($InsertSQL->get(dsn()), 'exec');
    }
}
