<?php

namespace Acms\Plugins\GoogleSheets;

use DB;
use SQL;
use Acms\Services\Facades\LocalStorage;
use Acms\Services\Facades\Config;
use Acms\Services\Facades\Cache;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;
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
        $scopes = implode(' ', [Sheets::SPREADSHEETS]);

        $this->config = Config::loadDefaultField();
        $this->config->overload(Config::loadBlogConfig(BID));
        $accessTokenJson = $this->config->get('google_spreadsheet_accesstoken');
        $accessToken = $accessTokenJson ? json_decode($accessTokenJson, true) : null;
        if ($accessTokenJson && json_last_error() !== JSON_ERROR_NONE) {
            $accessToken = null;
        }
        $refreshTokenJson = $this->config->get('google_spreadsheet_refreshtoken');
        $refreshToken = $refreshTokenJson ? json_decode($refreshTokenJson, true) : null;
        if ($refreshTokenJson && json_last_error() !== JSON_ERROR_NONE) {
            $refreshToken = null;
        }

        $this->client = new Client();
        $idJsonPath = $this->config->get('spreadsheet_clientid_json');
        $this->client->setApplicationName('ACMS');
        $this->client->setScopes($scopes);
        if ($idJsonPath !== '') {
            $this->setAuthConfig($idJsonPath);
        }
        $this->client->setAccessType('offline');
        $this->client->setPrompt("consent");
        $redirect_uri = acmsLink([
            'bid' => BID,
            'admin' => 'app_google_sheets_callback',
        ]);
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
                    Logger::error(
                        '【Google Sheets】Google Sheets API のアクセストークンの更新に失敗しました。',
                        Common::exceptionArray($e)
                    );
                }
            }
        }
    }

    /**
     * @param string $json
     * @return void
     */
    public function setAuthConfig($json): void
    {
        if ($json === '') {
            throw new \InvalidArgumentException('Empty JSON file path.');
        }
        if (!LocalStorage::exists($json)) {
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

    /**
     * @return \Google\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return array|null
     */
    public function getAccessToken(): ?array
    {
        $accessTokenJson = $this->config->get('google_spreadsheet_accesstoken');
        $accessToken = $accessTokenJson ? json_decode($accessTokenJson, true) : null;
        if ($accessTokenJson && json_last_error() !== JSON_ERROR_NONE) {
            $accessToken = null;
        }
        return $accessToken;
    }

    /**
     * @param string|null $accessToken
     * @param string|null $refreshToken
     * @return void
     */
    public function updateAccessToken(?string $accessToken, ?string $refreshToken): void
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
