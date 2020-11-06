<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class EntDetail extends ModelBase
{
    protected $tableName = 'miniapp_ent_detail';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
