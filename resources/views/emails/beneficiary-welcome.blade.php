<x-mail::message>
# Thông báo khởi tạo quy trình

Chào bạn,

Hệ thống vừa khởi tạo một quy trình mới liên quan đến bạn: **{{ $instanceName }}**.  
Người khởi tạo: **{{ $creatorName }}**.

Hiện tại, quy trình đang được các bộ phận liên quan xử lý. Bạn sẽ nhận được thông tin đăng nhập chính thức khi quy trình chuyển đến bước cần bạn trực tiếp tương tác hoặc kiểm tra thông tin.

Vui lòng theo dõi email để nhận các cập nhật tiếp theo.

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>