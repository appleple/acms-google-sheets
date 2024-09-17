<?php

namespace Acms\Plugins\GoogleSheets;

use Field;
use Google_Service_Sheets;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_RowData;
use Google_Service_Sheets_AppendCellsRequest;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_CellData;
use Google_Service_Sheets_ExtendedValue;

class Engine
{
    /**
     * @var \ACMS_POST
     */
    protected $module;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var \Field
     */
    protected $config;

    /**
     * @var string
     */
    protected $glue;

    /**
     * Engine constructor.
     * @param string $code
     */
    public function __construct($code, $module)
    {
        $info = $module->loadForm($code);
        if (empty($info)) {
            throw new \RuntimeException('Not Found Form.');
        }
        $this->module = $module;
        $this->code = $code;
        $this->config = $info['data']->getChild('mail');
        $this->glue = $this->config->get('cell_glue', ',');
    }

    /**
     * Update Google Sheets
     */
    public function send()
    {
        $field = $this->module->Post->getChild('field');
        $checkItems = [
            'formId' => $this->config->get('spreadsheet_submit_formid'),
            'time' => $this->config->get('spreadsheet_submit_date'),
            'url' => $this->config->get('spreadsheet_submit_url'),
            'ipAddr' => $this->config->get('spreadsheet_submit_ip'),
            'ua' => $this->config->get('spreadsheet_submit_agent'),
        ];
        $values = [];

        foreach ($checkItems as $item => $check) {
            if ($check !== 'true') {
                continue;
            }
            $method = 'get' . ucwords($item);
            if (is_callable([$this, $method])) {
                $values[] = $this->$method();
            }
        }
        foreach ($field->_aryField as $key => $val) {
            $values[] = $this->getCellData($field->getArray($key), $this->glue);
        }

        if ($this->config->get('spreadsheet_id')) {
            $this->update($values);
        }
    }

    /**
     * Send Google Sheets Api
     *
     * @param array $values
     */
    protected function update($values)
    {
        $client = (new Api())->getClient();
        if (!$client->getAccessToken()) {
            throw new \RuntimeException('Failed to get the access token.');
        }
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = $this->config->get('spreadsheet_id');
        $spreadsheetGid = $this->config->get('sheet_id', 0);

        // Build the RowData
        $rowData = new Google_Service_Sheets_RowData();
        $rowData->setValues($values);

        // Prepare the request
        $append_request = new Google_Service_Sheets_AppendCellsRequest();
        $append_request->setSheetId($spreadsheetGid);
        $append_request->setRows($rowData);
        $append_request->setFields('userEnteredValue');

        // Set the request
        $request = new Google_Service_Sheets_Request();
        $request->setAppendCells($append_request);

        // Add the request to the requests array
        $requests = [];
        $requests[] = $request;

        // Prepare the update
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        // Execute the request
        $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

        if (!$response->valid()) {
            throw new \RuntimeException('Failed to update the sheet.');
        }
    }

    /**
     * @param array|string $value
     * @param string $glue
     * @return \Google_Service_Sheets_CellData
     */
    private function getCellData($value, $glue = ',')
    {
        if (is_array($value)) {
            $value = implode($glue, $value);
        }
        $cellData = new Google_Service_Sheets_CellData();
        $extendedValue = new Google_Service_Sheets_ExtendedValue();
        $extendedValue->setStringValue($value);
        $cellData->setUserEnteredValue($extendedValue);
        return $cellData;
    }

    /**
     * @return \Google_Service_Sheets_CellData
     */
    private function getTime()
    {
        return $this->getCellData(date('Y-m-d H:i:s', REQUEST_TIME));
    }

    /**
     * @return \Google_Service_Sheets_CellData
     */
    private function getFormId()
    {
        return $this->getCellData($this->code);
    }

    /**
     * @return \Google_Service_Sheets_CellData
     */
    private function getUrl()
    {
        return $this->getCellData(REQUEST_URL);
    }

    /**
     * @return \Google_Service_Sheets_CellData
     */
    private function getIpAddr()
    {
        return $this->getCellData(REMOTE_ADDR);
    }

    /**
     * @return \Google_Service_Sheets_CellData
     */
    private function getUa()
    {
        return $this->getCellData(UA);
    }
}
