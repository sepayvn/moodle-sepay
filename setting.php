<?php
defined('MOODLE_INTERNAL') || die();

if (is_siteadmin()) {
    $settings->add(
        new admin_setting_configtext(
            'enrol_sepay/business',
            get_string('business', 'enrol_sepay'),
            'SePay merchant ID hoặc API key',
            '',
            PARAM_TEXT
        )
    );
}
