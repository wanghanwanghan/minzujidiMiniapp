<?php

namespace App\HttpController\Models\Admin;

use App\HttpController\Models\ModelBase;

class SupervisorEntNameInfo extends ModelBase
{
    protected $tableName = 'miniapp_supervisor_entname_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
