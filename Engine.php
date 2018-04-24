<?php

namespace Acms\Plugins\GoogleSheets;

use DB;
use SQL;
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
     * @var \Field
     */
    protected $formField;

    /**
     * @var \Field
     */
    protected $config;

    /**
     * Engine constructor.
     * @param string $code
     */
    public function __construct($code, $module)
    {
        $field = $this->loadFrom($code);
        if (empty($field)) {
            throw new \RuntimeException('Not Found Form.');
        }
        $this->formField = $field;
        $this->module = $module;
        $this->code = $code;
        $this->config = $field->getChild('mail');
    }

    /**
     * Update Google Sheets
     */
    public function send()
    {
        $field = $this->module->Post->getChild('field');
        $checkItems = array(
            'formId' => $this->config->get('spreadsheet_submit_formid'),
            'time' => $this->config->get('spreadsheet_submit_date'),
            'url' => $this->config->get('spreadsheet_submit_url'),
            'ipAddr' => $this->config->get('spreadsheet_submit_ip'),
            'ua' => $this->config->get('spreadsheet_submit_agent'),
        );
        $values = array();

        foreach ($checkItems as $item => $check) {
            if ($check !== 'true') {
                continue;
            }
            $method = 'get' .ucwords($item);
            if (is_callable(array('self', $method))) {
                $values[] = call_user_func(array($this, $method));
            }
        }
        foreach ($field->_aryField as $key => $val) {
            $values[] = $this-> getCellData($field->get($key));
        }
        $this->update($values);
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
        $requests = array();
        $requests[] = $request;

        // Prepare the update
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
            'requests' => $requests
        ));
        // Execute the request
        $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

        if (!$response->valid()) {
            throw new \RuntimeException('Failed to update the sheet.');
        }
    }


    /**
     * @param string $code
     * @return bool|Field
     */
    protected function loadFrom($code)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('form');
        $SQL->addWhereOpr('form_code', $code);
        $row = $DB->query($SQL->get(dsn()), 'row');

        if (!$row) {
            return false;
        }
        $Form = new Field();
        $Form->set('code', $row['form_code']);
        $Form->set('name', $row['form_name']);
        $Form->set('scope', $row['form_scope']);
        $Form->set('log', $row['form_log']);
        $Form->overload(unserialize($row['form_data']), true);

        return $Form;
    }

    /**
     * @param string $value
     * @return \Google_Service_Sheets_CellData
     */
    private function getCellData($value)
    {
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
