<?php

namespace App\HttpController\Models\Admin;

use App\HttpController\Models\ModelBase;

class AddrUse extends ModelBase
{
    protected $tableName = 'miniapp_use_addr';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
