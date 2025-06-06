<?php
// ------------------------------
// ترجمه متون (فارسـی/انگلیسی) ماژول دسترسی
// ------------------------------
if (!function_exists('lang')) {
    /**
     * دریافت متن ترجمه شده
     * @param string $key
     * @return string
     */
    function lang($key) {
        static $fa = [
            'access_denied'      => 'دسترسی غیرمجاز!',
            'login'              => 'ورود به سیستم',
            'logout'             => 'خروج',
            'username'           => 'نام کاربری',
            'password'           => 'رمز عبور',
            'change_password'    => 'تغییر رمز عبور',
            'dashboard'          => 'داشبورد',
            'users'              => 'کاربران',
            'roles'              => 'نقش‌ها',
            'permissions'        => 'مجوزها',
            'groups'             => 'گروه‌ها',
            'logs'               => 'لاگ‌ها',
            'meta'               => 'متای کاربر',
            '2fa'                => 'ورود دو مرحله‌ای',
            'api'                => 'API',
            'submit'             => 'ثبت',
            'cancel'             => 'انصراف',
            'delete'             => 'حذف',
            'edit'               => 'ویرایش',
            'save'               => 'ذخیره',
            'add'                => 'افزودن',
            'select'             => 'انتخاب',
        ];
        return $fa[$key] ?? $key;
    }
}