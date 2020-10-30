<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class User extends ModelBase
{
    protected $tableName = 'miniapp_user';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
