<?php
// 菜单路径：后台 -> 功能设置 ->LDAP
$lang->admin->menuList->feature['subMenu']['ldap']  = array('link' => "{$lang->ldap->common}|ldap|index", 'links' => array('ldap|index&field=roleList'), 'exclude' => 'set,required');