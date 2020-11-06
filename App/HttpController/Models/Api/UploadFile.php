<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class UploadFile extends ModelBase
{
    protected $tableName = 'miniapp_upload_file';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
