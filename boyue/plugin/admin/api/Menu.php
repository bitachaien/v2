<?php
namespace plugin\admin\api;

use plugin\admin\app\model\Rule;

class Menu
{

    
    public static function get($key)
    {
        $menu = Rule::where('key', $key)->first();
        return $menu ? $menu->toArray() : null;
    }

    
    public static function find($id): array
    {
        return Rule::find($id)->toArray();
    }

    
    public static function add(array $menu)
    {
        $item = new Rule;
        foreach ($menu as $key => $value) {
            $item->$key = $value;
        }
        $item->save();
        return $item->id;
    }

    
    public static function import(array $menu_tree)
    {
        if (is_numeric(key($menu_tree)) && !isset($menu_tree['key'])) {
            foreach ($menu_tree as $item) {
                static::import($item);
            }
            return;
        }
        $children = $menu_tree['children'] ?? [];
        unset($menu_tree['children']);
        if ($old_menu = Menu::get($menu_tree['key'])) {
            $pid = $old_menu['id'];
            Rule::where('key', $menu_tree['key'])->update($menu_tree);
        } else {
            $pid = static::add($menu_tree);
        }
        foreach ($children as $menu) {
            $menu['pid'] = $pid;
            static::import($menu);
        }
    }

    
    public static function delete($key)
    {
        $item = Rule::where('key', $key)->first();
        if (!$item) {
            return;
        }
        
        $delete_ids = $children_ids = [$item['id']];
        while($children_ids) {
            $children_ids = Rule::whereIn('pid', $children_ids)->pluck('id')->toArray();
            $delete_ids = array_merge($delete_ids, $children_ids);
        }
        Rule::whereIn('id', $delete_ids)->delete();
    }

    
    public static function column($menu, $column = null, $index = null)
    {
        $values = [];
        if (is_numeric(key($menu)) && !isset($menu['key'])) {
            foreach ($menu as $item) {
                $values = array_merge($values, static::column($item, $column, $index));
            }
            return $values;
        }

        $children = $menu['children'] ?? [];
        unset($menu['children']);
        if ($column === null) {
            if ($index) {
                $values[$menu[$index]] = $menu;
            } else {
                $values[] = $menu;
            }
        } else {
            if (is_array($column)) {
                $item = [];
                foreach ($column as $f) {
                    $item[$f] = $menu[$f] ?? null;
                }
                if ($index) {
                    $values[$menu[$index]] = $item;
                } else {
                    $values[] = $item;
                }
            } else {
                $value = $menu[$column] ?? null;
                if ($index) {
                    $values[$menu[$index]] = $value;
                } else {
                    $values[] = $value;
                }
            }
        }
        foreach ($children as $child) {
            $values = array_merge($values, static::column($child, $column, $index));
        }
        return $values;
    }

}