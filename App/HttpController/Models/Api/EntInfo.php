<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class EntInfo extends ModelBase
{
    protected $tableName = 'miniapp_ent_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
