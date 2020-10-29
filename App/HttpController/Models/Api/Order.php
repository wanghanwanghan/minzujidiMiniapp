<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class Order extends ModelBase
{
    protected $tableName = 'miniapp_order';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
