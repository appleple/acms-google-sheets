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
        if (!$formCode) {
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

        // Google スプレッドシートへの書き込みは、結果を画面に出さないため、応答を返したあとに行う（Google が遅くても、フォームの応答を待たせない）
        $this->runAfterResponse(function () use ($formCode, $thisModule) {
            try {
                $engine = new Engine($formCode, $thisModule);
                $engine->send();
            } catch (\Exception $e) {
                Logger::warning(
                    '【Google Sheets】Google Sheets plugin: ' . $e->getMessage(),
                    Common::exceptionArray($e)
                );
            }
        });
    }

    /**
     * 応答を返したあとに処理を実行する（本体が対応しているときだけ）
     *
     * Why not: Response::afterResponse() を直接呼ばない。この仕組みが無い古い本体では、呼び出しが失敗して、
     * フォームの送信に影響するため、本体に afterResponse() があるときだけ使い、無ければ従来どおり、その場で実行する。
     *
     * @param callable $task
     * @return void
     */
    private function runAfterResponse(callable $task)
    {
        if (class_exists('Acms\\Services\\Facades\\Application')) {
            try {
                $response = \Acms\Services\Facades\Application::make('response');
                if (is_object($response) && method_exists($response, 'afterResponse')) {
                    $response->afterResponse($task);
                    return;
                }
            } catch (\Throwable $e) {
                // 取得できないときは、その場で実行する
            }
        }
        $task();
    }
}
