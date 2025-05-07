# Sử dụng

1. Đăng nhập vào trang Moodle với tài khoản `admin` và thực hiện các bước sau:
2. Tải plugin lên:

   Vào `Site administration` > `Plugins` > `Install plugins`.

   Chọn Plugin type là `Enrolment method (enrol)`.

   Tải lên gói ZIP của plugin Sepay, đánh dấu xác nhận và tiến hành cài đặt.

3. Kích hoạt plugin Sepay:
   Đi đến `Enrolments` > `Manage enrol plugins`, tìm `Sepay` trong danh sách và nhấn `Enable`.
4. Cấu hình Sepay:
   Nhấp vào `Settings` bên cạnh `Sepay` để mở trang cấu hình.

   Nhập các thông tin Webhook `Sepay`

   `API Key`: Xem phần **[Thêm Webhook](#thêm-webhook)** ở bên dưới

   `Pattern`: Là tiền tố để nhận diện code thanh toán được cấu hình tại `Cấu hình công ty` -> `Cấu hình chung` -> [`Cấu trúc mã thanh toán`](https://my.sepay.vn/company/configuration)

   Chọn các tùy chọn phù hợp và nhấn Save changes để lưu cấu hình.

5. Cài đặt phương thức ghi danh bằng Sepay cho khóa học:

   Chọn một khóa học từ danh sách.

   Vào `Course administration` > `Participants` > `Enrolment methods`.

   Chọn `Sepay` từ danh sách trong mục `Add method`.

   Điền các thông tin như:

   • `Custom instance name`

   • `Enrol cost`

   Sau đó nhấn `Add method` để hoàn tất.

# Thêm Webhook

Cấu hình webhook SePay bằng cách

1. Truy cập https://my.sepay.vn/webhooks
2. Nhấn nút `Thêm Webhooks`
3. Mục `Thuộc tính WebHooks`: https://ten_mien_cua_ban.com/enrol/sepay/webhook.php
4. Mục `Cấu hình chứng thực WebHooks`: Kiểu chứng thực chọn `API Key` > API Key là 1 đoạn ký tự, bạn có thể tự nhập hoặc dùng công cụ bất kỳ sinh ngẫu nhiên
