<x-mail::message>
# Thông tin tài khoản đăng nhập

Chào bạn,

Hệ thống vừa tạo một tài khoản cho bạn để theo dõi và tương tác với quy trình **{{ $instanceName }}**.

Dưới đây là thông tin đăng nhập của bạn:

- **Email:** {{ $email }}
- **Mật khẩu tạm thời:** {{ $password }}

*Lưu ý: Bạn sẽ được yêu cầu đổi mật khẩu trong lần đăng nhập đầu tiên.*

<x-mail::button :url="$loginUrl" color="primary">
Đăng nhập ngay
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>