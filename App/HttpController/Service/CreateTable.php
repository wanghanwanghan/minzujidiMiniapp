<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\DDL\DDLBuilder;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;
use EasySwoole\Pool\Manager;

class CreateTable extends ServiceBase
{
    use Singleton;

    function __construct()
    {
        return parent::__construct();
    }

    //用户表
    function miniapp_user()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('用户表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('entName', 100)->setDefaultValue('')->setColumnComment('企业名称');
            $table->colVarChar('phone', 20)->setDefaultValue('');
            $table->colVarChar('email', 100)->setDefaultValue('');
            $table->colTinyInt('type', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('用户类型');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_index', 'phone');
        });

        $obj = Manager::getInstance()->get('miniapp')->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get('miniapp')->recycleObj($obj);

        return 'ok';
    }

    //公司类型表
    function miniapp_ent_type()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('公司类型表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('entType', 100)->setDefaultValue('')->setColumnComment('公司类型');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get('miniapp')->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get('miniapp')->recycleObj($obj);

        return 'ok';
    }

    //公司纳税类型表
    function miniapp_ent_tax_type()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('公司纳税类型表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('entTaxType', 100)->setDefaultValue('')->setColumnComment('公司纳税类型');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get('miniapp')->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get('miniapp')->recycleObj($obj);

        return 'ok';
    }

    //公司行业类型表
    function miniapp_ent_trade_type()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('公司行业类型表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('entTradeType', 100)->setDefaultValue('')->setColumnComment('公司行业类型');
            $table->colText('entTradeRange')->setColumnComment('公司经营范围');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get('miniapp')->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get('miniapp')->recycleObj($obj);

        return 'ok';
    }

    //订单表
    function miniapp_order()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('订单表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('orderId', 50)->setDefaultValue('')->setColumnComment('订单号');
            $table->colVarChar('entName', 100)->setDefaultValue('')->setColumnComment('企业名称');
            $table->colVarChar('phone', 20)->setDefaultValue('');
            $table->colTinyInt('userType', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('用户类型');
            $table->colTinyInt('taxType', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('纳税类型类型');
            $table->colTinyInt('modifyAddr', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('是否变更地址');
            $table->colTinyInt('modifyArea', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('是否跨区');
            $table->colTinyInt('proxy', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('是否代理记账');
            $table->colTinyInt('status', 3)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('订单状态');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('orderId_index', 'orderId');
            $table->indexNormal('phone_index', 'phone');
        });

        $obj = Manager::getInstance()->get('miniapp')->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get('miniapp')->recycleObj($obj);

        return 'ok';
    }
}
