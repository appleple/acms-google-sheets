<?php

namespace Acms\Plugins\GoogleSpreadSheet;

use DB;
use SQL;
use Field;
use Field_Validation;
use Acms\Plugins\GoogleSpreadSheet\Api;
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
        $this->config = $field->getChild('mail');
    }

    /**
     * Send
     */
    public function send()
    {
        $client = (new Api())->getClient();

        if (!$client->getAccessToken()) {
            return;
        }

        $isTimeChecked = $this->config->get('spreadsheet_submit_date');
        $isFormIdChecked = $this->config->get('spreadsheet_submit_formid');
        $isUrlChecked = $this->config->get('spreadsheet_submit_url');
        $isIpChecked = $this->config->get('spreadsheet_submit_ip');
        $isAgentChecked = $this->config->get('spreadsheet_submit_agent');

        $field = $this->module->Post->getChild('field');
        $service = new Google_Service_Sheets($client);
        $keys = array_keys($field->_aryField);

        $values = array();
        $spreadsheetId = $this->config->get('spreadsheet_id');
        $spreadsheetGid = $this->config->get('sheet_id');

        if (!$spreadsheetGid) {
            $spreadsheetGid = 0;
        }

        if ($isFormIdChecked) {
            $cellData = $this->getCellData($id);
            $values[] = $cellData;
        }

        if ($isTimeChecked) {
            $cellData = $this->getCellData(date('Y-m-d H:i:s', REQUEST_TIME));
            $values[] = $cellData;
        }

        if ($isUrlChecked) {
            $cellData = $this->getCellData(REQUEST_URL);
            $values[] = $cellData;
        }

        if ($isIpChecked) {
            $cellData = $this->getCellData(REMOTE_ADDR);
            $values[] = $cellData;
        }

        if ($isAgentChecked) {
            $cellData = $this->getCellData(UA);
            $values[] = $cellData;
        }

        foreach ($keys as $key) {
            $cellData = $this->getCellData($field->get($key));
            $values[] = $cellData;
        }

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


        try {
            // Execute the request
            $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

            if ($response->valid()) {
                // Success, the row has been added
                return true;
            }
        } catch (Exception $e) {

            // Something went wrong
            error_log($e->getMessage());
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

    private function getCellData($value)
    {
        $cellData = new Google_Service_Sheets_CellData();
        $extendedValue = new Google_Service_Sheets_ExtendedValue();
        $extendedValue->setStringValue($value);
        $cellData->setUserEnteredValue($extendedValue);
        return $cellData;
    }
}
