<?php

namespace Acms\Plugins\GoogleSpreadSheet;

use DB;
use SQL;
use Field;
use Field_Validation;

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
    public function __construct($code)
    {
        $field = $this->loadFrom($code);
        if (empty($field)) {
            throw new \RuntimeException('Not Found Form.');
        }
        $this->formField = $field;
    }

    /**
     * Send
     */
    public function send()
    {
        if (!$client->getAccessToken()) {
            return;
        }

        $client = (new AAPP_GoogleSpreadSheet_Api())->getClient();
        $isTimeChecked = config('spreadsheet_submit_date');
        $isFormIdChecked = config('spreadsheet_submit_formid');
        $isUrlChecked = config('spreadsheet_submit_url');
        $isIpChecked = config('spreadsheet_submit_ip');
        $isAgentChecked = config('spreadsheet_submit_agent');
        $formIds = configArray('spreadsheet_form_id');
        
        $field = $thisModule->Post->_aryChild['field'];
        $service = new Google_Service_Sheets($client);
        $keys = array_keys($field->_aryMethod);
        $id = $thisModule->Post->get('id');

        foreach ( $formIds as $i => $formId ) {

            if ( $formId !== $id ) {
                continue;
            }

            $values = array();
            $spreadsheetId = config('spreadsheet_form_spreadsheet_id', '', $i);
            $spreadsheetGid = config('spreadsheet_form_sheet_id', '', $i);
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
                if( $response->valid() ) {
                    // Success, the row has been added
                    return true;
                }
            } catch (Exception $e) {
                // Something went wrong
                error_log($e->getMessage());
            }
        }
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
    
    private function getCellData($value) {
        $cellData = new Google_Service_Sheets_CellData();
        $extendedValue = new Google_Service_Sheets_ExtendedValue();
        $extendedValue->setStringValue($value);
        $cellData->setUserEnteredValue($extendedValue);
        return $cellData;
    }
}