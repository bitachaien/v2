<?php

namespace plugin\admin\api;

class Install
{
    
    public static function install($version)
    {
        
        Menu::import(static::getMenus());
    }

    
    public static function uninstall($version)
    {
        
        foreach (static::getMenus() as $menu) {
            Menu::delete($menu['name']);
        }
    }

    
    public static function update($from_version, $to_version, $context = null)
    {
        
        if (isset($context['previous_menus'])) {
            static::removeUnnecessaryMenus($context['previous_menus']);
        }
        
        Menu::import(static::getMenus());
    }

    
    public static function beforeUpdate($from_version, $to_version)
    {
        
        return ['previous_menus' => static::getMenus()];
    }

    
    public static function getMenus()
    {
        clearstatcache();
        if (is_file($menu_file = __DIR__ . '/../config/menu.php')) {
            $menus = include $menu_file;
            return $menus ?: [];
        }
        return [];
    }

    
    public static function removeUnnecessaryMenus($previous_menus)
    {
        $menus_to_remove = array_diff(Menu::column($previous_menus, 'name'), Menu::column(static::getMenus(), 'name'));
        foreach ($menus_to_remove as $name) {
            Menu::delete($name);
        }
    }

}