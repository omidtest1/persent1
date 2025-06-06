<?php
// --- تنظیمات ماژول مدیریت دسترسی ---
$db_config = array (
  'host' => 'localhost',
  'port' => '3306',
  'dbname' => 'test_majol',
  'user' => 'root',
  'pass' => '',
  'charset' => 'utf8mb4',
  'type' => 'mysql',
);
$config = [
    'enable_2fa'        => true,
    'audit_log_enabled' => true,
    'use_jwt'           => true,
    'default_lang'      => 'fa',
    'jwt_secret'        => 'change_this_secret_key',
    'lang_path'         => __DIR__.'/lang/',
    'default_role'      => 'user',
    'tables'            => [
        'users'           => 'users',
        'roles'           => 'roles',
        'permissions'     => 'permissions',
        'permission_categories' => 'permission_categories',
        'groups'          => 'groups',
        'user_meta'       => 'user_meta',
        'role_permissions'=> 'role_permissions',
        'group_permissions'=> 'group_permissions',
        'group_users'     => 'group_users',
        'audit_log'       => 'audit_log',
        'access_request'  => 'access_request',
    ],
];
