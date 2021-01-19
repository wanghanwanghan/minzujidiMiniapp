<?php

namespace App\HttpController\Models\Admin;

use App\HttpController\Models\ModelBase;

class RecodeOrder extends ModelBase
{
    protected $tableName = 'recodeOrder';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
