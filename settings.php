<?php
// Đảm bảo chỉ admin mới truy cập được
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Trường nhập API Key
    $settings->add(new admin_setting_configtext(
        'enrol_sepay/apikey',
        get_string('apikey', 'enrol_sepay'),
        get_string('apikey_desc', 'enrol_sepay'),
        '',
        PARAM_TEXT
    ));

    // PATTERN
    $settings->add(new admin_setting_configtext(
        'enrol_sepay/pattern',
        get_string('pattern', 'enrol_sepay'),
        get_string('pattern_desc', 'enrol_sepay'),
        'sepay',
        PARAM_TEXT
    ));

    // Account
    $settings->add(new admin_setting_configtext(
        'enrol_sepay/account',
        get_string('account', 'enrol_sepay'),
        get_string('account_desc', 'enrol_sepay'),
        '',
        PARAM_TEXT
    ));

    // Bank
    $cache_key = 'enrol_sepay/bank_api_response';
    $cache_ttl = 60 * 60 * 24; // Cache trong 1 ngày

    if ($cached_response = get_config('enrol_sepay', $cache_key)) {
        $bank_api_response = json_decode($cached_response, true);
    } else {
        $bank_api_url = 'https://qr.sepay.vn/banks.json';
        $bank_api_response = json_decode(file_get_contents($bank_api_url), true);
        set_config($cache_key, json_encode($bank_api_response), 'enrol_sepay');
        set_config($cache_key . '_ttl', time() + $cache_ttl, 'enrol_sepay');
    }

    // Kiểm tra thời gian cache
    if (get_config('enrol_sepay', $cache_key . '_ttl') < time()) {
        // Cache đã hết hạn, cần gọi API lại
        $bank_api_url = 'https://qr.sepay.vn/banks.json';
        $bank_api_response = json_decode(file_get_contents($bank_api_url), true);
        set_config($cache_key, json_encode($bank_api_response), 'enrol_sepay');
        set_config($cache_key . '_ttl', time() + $cache_ttl, 'enrol_sepay');
    }


    $bank_options = array();
    foreach ($bank_api_response['data'] as $bank) {
        if (!$bank['supported']) {
            continue;
        }
        $bank_options[$bank['short_name']] = $bank['short_name'] . ' - ' . $bank['name'];
    }

    $settings->add(new admin_setting_configselect(
        'enrol_sepay/bank',
        get_string('bank', 'enrol_sepay'),
        get_string('bank_desc', 'enrol_sepay'),
        'OCB',
        $bank_options
    ));

    // Template
    $settings->add(new admin_setting_configselect(
        'enrol_sepay/template',
        get_string('template', 'enrol_sepay'),
        get_string('template_desc', 'enrol_sepay'),
        'compact',
        array(
            'compact' => get_string('setting_template_compact', 'enrol_sepay'),
            '' => get_string('setting_template_default', 'enrol_sepay'),
            'qronly' => get_string('setting_template_qronly', 'enrol_sepay')
        )
    ));


    $settings->add(new admin_setting_configcheckbox(
        'enrol_sepay/mailstudents',
        get_string('mailstudents', 'enrol_sepay'),
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_sepay/mailteachers',
        get_string('mailteachers', 'enrol_sepay'),
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_sepay/mailadmins',
        get_string('mailadmins', 'enrol_sepay'),
        '',
        0
    ));


    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'enrol_sepay_defaults',
        get_string('enrolinstancedefaults', 'admin'),
        get_string('enrolinstancedefaults_desc', 'admin')
    ));

    $options = array(
        ENROL_INSTANCE_ENABLED  => get_string('yes'),
        ENROL_INSTANCE_DISABLED => get_string('no')
    );
    $settings->add(new admin_setting_configselect(
        'enrol_sepay/status',
        get_string('status', 'enrol_sepay'),
        get_string('status_desc', 'enrol_sepay'),
        ENROL_INSTANCE_DISABLED,
        $options
    ));

    // Giá mặc định nếu admin không đặt ở mỗi khóa học
    $settings->add(new admin_setting_configtext(
        'enrol_sepay/cost',
        get_string('defaultcost', 'enrol_sepay'),
        '',
        0,
        PARAM_INT,
        10
    ));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect(
            'enrol_sepay/roleid',
            get_string('defaultrole', 'enrol_sepay'),
            get_string('defaultrole_desc', 'enrol_sepay'),
            $student->id ?? null,
            $options
        ));
    }

    $settings->add(new admin_setting_configduration(
        'enrol_sepay/enrolperiod',
        get_string('enrolperiod', 'enrol_sepay'),
        get_string('enrolperiod_desc', 'enrol_sepay'),
        0
    ));
}
