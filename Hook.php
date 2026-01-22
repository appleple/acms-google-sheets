<?php

namespace Acms\Plugins\GoogleSheets;

use ACMS_POST_Form_Submit;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;

class Hook
{
    /**
     * POSTモジュール処理前
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param \ACMS_POST $thisModule
     * @return void
     */
    public function afterPostFire($thisModule): void
    {
        $formCode = $thisModule->Post->get('id');
        if(!$formCode) {
            return;
        }
        if (!($thisModule instanceof ACMS_POST_Form_Submit)) {
            return;
        }
        $info = $thisModule->loadForm($formCode);
        if (empty($info)) {
            return;
        }
        if ($info['data']->getChild('mail')->get('spreadsheet_void') !== 'on') {
            return;
        };
        if (!$thisModule->Post->isValidAll()) {
            return;
        }
        $step = $thisModule->Post->get('error');
        if (empty($step)) {
            $step = $thisModule->Get->get('step');
        }
        $step = $thisModule->Post->get('step', $step);
        if (in_array($step, ['forbidden', 'repeated'])) {
            return;
        }

        try {
            $engine = new Engine($formCode, $thisModule);
            $engine->send();
        } catch (\Exception $e) {
            Logger::warning(
                '【Google Sheets】Google Sheets plugin: ' . $e->getMessage(),
                Common::exceptionArray($e)
            );
        }
    }
}
