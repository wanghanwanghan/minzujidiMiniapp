<?php

namespace App\HttpController\Models\Admin;

use App\HttpController\Models\ModelBase;

class SupervisorPhoneEntName extends ModelBase
{
    protected $tableName = 'miniapp_supervisor_uid_entname';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
