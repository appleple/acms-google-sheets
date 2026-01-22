<?php

namespace Acms\Plugins\GoogleSheets;

use Field;
use Google\Service\Sheets;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\RowData;
use Google\Service\Sheets\AppendCellsRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\CellData;
use Google\Service\Sheets\ExtendedValue;

class Engine
{
    /**
     * @var \ACMS_POST_Form
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
     * @param \ACMS_POST_Form $module
     */
    public function __construct(string $code, $module)
    {
        $info = $module->loadForm($code);
        if ($info === false) {
            throw new \RuntimeException('Not Found Form.');
        }
        $this->module = $module;
        $this->code = $code;
        $this->config = $info['data']->getChild('mail');
        $this->glue = $this->config->get('cell_glue', ',');
    }

    /**
     * Update Google Sheets
     * @return void
     */
    public function send(): void
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
     * @return void
     */
    protected function update(array $values): void
    {
        $client = (new Api())->getClient();
        if (!$client->getAccessToken()) {
            throw new \RuntimeException('Failed to get the access token.');
        }
        $service = new Sheets($client);
        $spreadsheetId = $this->config->get('spreadsheet_id');
        $spreadsheetGid = $this->config->get('sheet_id', 0);

        // Build the RowData
        $rowData = new RowData();
        $rowData->setValues($values);

        // Prepare the request
        $append_request = new AppendCellsRequest();
        $append_request->setSheetId($spreadsheetGid);
        $append_request->setRows($rowData);
        $append_request->setFields('userEnteredValue');

        // Set the request
        $request = new Request();
        $request->setAppendCells($append_request);

        // Add the request to the requests array
        $requests = [];
        $requests[] = $request;

        // Prepare the update
        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        // Execute the request
        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        // 例外が発生しなければ成功とみなす（Google APIクライアントが例外を投げる）
    }

    /**
     * @param array|string $value
     * @param string $glue
     * @return CellData
     */
    private function getCellData($value, string $glue = ','): CellData
    {
        if (is_array($value)) {
            $value = implode($glue, $value);
        }
        $cellData = new CellData();
        $extendedValue = new ExtendedValue();
        $extendedValue->setStringValue($value);
        $cellData->setUserEnteredValue($extendedValue);
        return $cellData;
    }

    /**
     * @return CellData
     */
    private function getTime(): CellData
    {
        return $this->getCellData(date('Y-m-d H:i:s', REQUEST_TIME));
    }

    /**
     * @return CellData
     */
    private function getFormId(): CellData
    {
        return $this->getCellData($this->code);
    }

    /**
     * @return CellData
     */
    private function getUrl(): CellData
    {
        return $this->getCellData(REQUEST_URL);
    }

    /**
     * @return CellData
     */
    private function getIpAddr(): CellData
    {
        return $this->getCellData(REMOTE_ADDR);
    }

    /**
     * @return CellData
     */
    private function getUa(): CellData
    {
        return $this->getCellData(UA);
    }
}
