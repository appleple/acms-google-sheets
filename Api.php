<?php

namespace Acms\Plugins\GoogleSheets;

use DB;
use SQL;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Config;
use Acms\Services\Facades\Cache;
use Google\Client;
use Google\Service\Sheets;
use Google\Exception as GoogleException;

class Api
{
    protected $client = null;

    protected $config;

    /**
     * Api constructor.
     */
    public function __construct()
    {
        $scopes = implode(' ', array(Sheets::SPREADSHEETS));

        $this->config = Config::loadDefaultField();
        $this->config->overload(Config::loadBlogConfig(BID));
        $accessToken = json_decode($this->config->get('google_spreadsheet_accesstoken'), true);
        $refreshToken = json_decode($this->config->get('google_spreadsheet_refreshtoken'), true);

        $this->client = new Client();
        $idJsonPath = $this->config->get('spreadsheet_clientid_json');
        $this->client->setApplicationName('ACMS');
        $this->client->setScopes($scopes);
        $this->setAuthConfig($idJsonPath);
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt("force");
        $redirect_uri = acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_sheets_callback',
        ));
        $this->client->setRedirectUri($redirect_uri);
        if ($accessToken) {
            $this->client->setAccessToken($accessToken);
            if ($this->client->isAccessTokenExpired()) {
                try {
                    $this->client->refreshToken($refreshToken);
                    $accessToken = $this->client->getAccessToken();
                    $refreshToken = $this->client->getRefreshToken();
                    $this->updateAccessToken(json_encode($accessToken), json_encode($refreshToken));
                } catch (\Exception $e) {
                    userErrorLog('ACMS Error: In GoogleSheets extension -> ' . $e->getMessage());
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
            throw new GoogleException("Invalid client secret JSON file.");
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

    public function updateAccessToken($accessToken, $refreshToken)
    {
        if (!!$accessToken) {
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
        if (!!$refreshToken) {
            $DB = DB::singleton(dsn());
            $RemoveSQL = SQL::newDelete('config');
            $RemoveSQL->addWhereOpr('config_blog_id', BID);
            $RemoveSQL->addWhereOpr('config_key', 'google_spreadsheet_refreshtoken');
            $DB->query($RemoveSQL->get(dsn()), 'exec');

            $InsertSQL = SQL::newInsert('config');
            $InsertSQL->addInsert('config_key', 'google_spreadsheet_refreshtoken');
            $InsertSQL->addInsert('config_value', $refreshToken);
            $InsertSQL->addInsert('config_blog_id', BID);
            $DB->query($InsertSQL->get(dsn()), 'exec');
        }
        if (class_exists('Cache')) {
            Cache::flush('config');
        }
    }
}
