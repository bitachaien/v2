<?php

namespace plugin\admin\app\model;

/**
 * YZZ菜单模型
 * @property integer $id 主键
 * @property integer $pid 父级ID
 * @property string $name 路由名称
 * @property string $title 菜单标题
 * @property string $icon 图标
 * @property string $path 路由路径
 * @property string $component 组件路径
 * @property integer $type 类型: 0=目录, 1=菜单, 2=按钮
 * @property integer $sort 排序
 * @property integer $status 状态: 0=禁用, 1=启用
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class YzzMenu extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $table = 'yzz_menus';

    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';
}
