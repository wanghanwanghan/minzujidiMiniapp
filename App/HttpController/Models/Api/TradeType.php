<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class TradeType extends ModelBase
{
    protected $tableName = 'miniapp_ent_trade_type';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
