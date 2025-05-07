<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Listens for Instant Payment Notification from SePay
 *
 * This script waits for Payment notification from SePay,
 * and sets up the enrolment for that user.
 *
 * @package    enrol_sepay
 * @copyright  2025 SePay.vn<your@email.address>
 * @author     Nguyen Tran Chung<nguyentranchung52th@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

// This script does not require login.
require("../../config.php"); // phpcs:ignore
define('NO_MOODLE_COOKIES', true); // Không cần phiên đăng nhập
require_once("lib.php");
require_once($CFG->libdir . '/enrollib.php');

// Make sure we are enabled in the first place.
if (!enrol_is_enabled('sepay')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_sepay');
}

$data = new stdClass();

header('Content-Type: application/json');

$plugin = enrol_get_plugin('sepay');
$expected_key = trim($plugin->get_config('apikey'));

// 1. Xác thực header Authorization: Apikey YOUR_API_KEY
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (stripos($auth_header, 'Apikey ') !== 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid Authorization header format']);
    exit;
}

$provided_key = trim(substr($auth_header, 7));

if (empty($provided_key) || $provided_key !== $expected_key) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// 2. Đọc JSON input
$input = file_get_contents('php://input');
$sepay_data = json_decode($input, true);

if (!is_array($sepay_data) || empty($sepay_data['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON or missing code']);
    exit;
}

$content = $sepay_data['content'];
$transferAmount = $sepay_data['transferAmount'];
$bankAccount = $plugin->get_config('account');

if ($sepay_data['transferType'] !== 'in') {
    http_response_code(200);
    echo json_encode(['message' => 'Ignored: transferType is not "in"']);
    exit;
}

if ($sepay_data['gateway'] !== $plugin->get_config('bank')) {
    http_response_code(200);
    echo json_encode(['message' => 'Ignored: gateway is not ' . $plugin->get_config('bank')]);
    exit;
}

if ($sepay_data['accountNumber'] !== $bankAccount && $sepay_data['subAccount'] !== $bankAccount) {
    http_response_code(200);
    echo json_encode(['message' => 'Ignored: accountNumber or subAccount is not ' . $bankAccount]);
    exit;
}

$pattern = $plugin->get_config('pattern', 'sepay');

// Lấy ra user id hoặc order id ví dụ: SE_123456, SE_abcd-efgh
preg_match('/\b' . $pattern . '-(\d+)-(\d+)/', $content, $matches);

// 3. Kiểm tra định dạng code: FIV_course_123456
if (! isset($matches[0]) && !isset($matches[1])) {
    http_response_code(200);
    echo json_encode(['message' => 'Ignored: Invalid code format']);
    exit;
}

// course shortname
$data->userid           = (int)$matches[2];
$data->courseid         = (int)$matches[1];
$data->timeupdated      = time();

// 4. Tìm user và khóa học
$user = $DB->get_record("user", array("id" => $data->userid), "*", IGNORE_MISSING);
$course = $DB->get_record("course", array("id" => $data->courseid), "*", IGNORE_MISSING);

$PAGE->set_context($context);

if (!$user || !$course) {
    http_response_code(404);
    echo json_encode(['error' => 'User or course not found']);
    exit;
}

// 5. Tìm enrol instance của plugin sepay
$instances = enrol_get_instances($course->id, true);
$instance = null;

foreach ($instances as $inst) {
    if ($inst->enrol === 'sepay') {
        $instance = $inst;
        break;
    }
}

if (!$instance) {
    http_response_code(500);
    echo json_encode(['error' => 'SEPay enrolment instance not found in course']);
    exit;
}

// Check that amount paid is the correct amount
if ((int)$instance->cost <= 0) {
    $cost = (int) $plugin->get_config('cost');
} else {
    $cost = (int) $instance->cost;
}

if ($transferAmount < $cost) {
    \enrol_paypal\util::message_paypal_error_to_admin("Amount paid is not enough ($transferAmount < $cost)", $sepay_data);
    die;
}

// 6. Ghi danh user nếu chưa có
$context = context_course::instance($course->id, IGNORE_MISSING);
$roleid = $instance->roleid ?? 5; // mặc định student

if ($instance->enrolperiod) {
    $timestart = time();
    $timeend   = $timestart + $instance->enrolperiod;
} else {
    $timestart = 0;
    $timeend   = 0;
}

if (!is_enrolled($context, $user)) {
    $plugin->enrol_user($instance, $user->id, $roleid, time());
    echo json_encode(['message' => 'User enrolled successfully']);
} else {
    echo json_encode(['message' => 'User already enrolled']);
}
