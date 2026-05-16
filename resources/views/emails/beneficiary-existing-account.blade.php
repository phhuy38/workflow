<x-mail::message>
# Cập nhật thông tin quy trình

Chào bạn,

Tài khoản của bạn vừa được liên kết với một quy trình mới: **{{ $instanceName }}**.

Bạn có thể đăng nhập vào hệ thống để xem tiến độ chi tiết.

<x-mail::button :url="$loginUrl" color="primary">
Xem quy trình
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>