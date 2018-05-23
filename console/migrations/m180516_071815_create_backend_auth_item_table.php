<?php

use yii\db\Migration;

/**
 * Handles the creation of table `backend_auth_item`.
 */
class m180516_071815_create_backend_auth_item_table extends Migration
{
    const TBL_NAME = '{{%backend_auth_item}}';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB COMMENT="后台角色和权限表"';
        }

        $this->createTable(self::TBL_NAME, [
            'name' => $this->string(64)->notNull()->comment('角色或权限名称'),
            'type' => $this->tinyInteger()->notNull()->comment('类别：1角色，2权限'),
            'description' => $this->text()->comment('描述'),
            'rule_name' => $this->string(64)->comment('规则名称'),
            'data' => $this->text()->comment('数据'),
            'created_at' => $this->bigInteger()->unsigned()->notNull()->comment('创建时间'),
            'updated_at' => $this->bigInteger()->unsigned()->notNull()->comment('更新时间')
        ], $tableOptions);
        //添加主键及索引
        $this->addPrimaryKey('name', self::TBL_NAME, 'name');
        $this->createIndex('type', self::TBL_NAME, 'type');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable(self::TBL_NAME);
    }
}