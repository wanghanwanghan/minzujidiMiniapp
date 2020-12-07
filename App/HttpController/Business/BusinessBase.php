<?php

namespace App\HttpController\Business;

use App\HttpController\Index;
use App\HttpController\Service\ExportExcelService;
use wanghanwanghan\someUtils\control;

class BusinessBase extends Index
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //重写writeJson
    function writeJson($statusCode = 200, $paging = null, $result = null, $msg = null)
    {
        if (!$this->response()->isEndResponse()) {

            if (!empty($paging) && is_array($paging)) {
                foreach ($paging as $key => $val) {
                    $paging[$key] = (int)$val;
                }
            }

            $data = [
                'code' => $statusCode,
                'paging' => $paging,
                'result' => $result,
                'msg' => $msg
            ];

            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);

            return true;

        } else {
            return false;
        }
    }

    function exportExcel($entList)
    {
        $config = [
            'path' => FILE_PATH,
        ];

        $fileName   = __FUNCTION__.'.xlsx';

        $xlsxObject = new \Vtiful\Kernel\Excel($config);

        // Init File
        $fileObject = $xlsxObject->fileName($fileName);

        // Writing data to a file ......
        (new ExportExcelService())->export($fileObject,$entList);

        // Output
        $filePath = $fileObject->output();

        $this->response()->write(file_get_contents($filePath));
        $this->response()->withHeader('Content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->response()->withHeader('Content-Disposition', 'attachment;filename='.$fileName);
        $this->response()->withHeader('Cache-Control','max-age=0');
        $this->response()->withStatus(200);
        $this->response()->end();

        return true;
    }

    //计算分页
    function exprOffset($page, $pageSize): int
    {
        return ($page - 1) * $pageSize;
    }

    function createPaging($page, $pageSize, $total): array
    {
        return [
            'page' => (int)$page,
            'pageSize' => (int)$pageSize,
            'total' => (int)$total,
        ];
    }



}
