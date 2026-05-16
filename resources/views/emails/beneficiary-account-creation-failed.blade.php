<x-mail::message>
# Lỗi tạo tài khoản người thụ hưởng

Chào bạn,

Hệ thống đã gặp lỗi khi cố gắng tạo tự động tài khoản cho người thụ hưởng có email: **{{ $beneficiaryEmail }}** trong quy trình **{{ $instanceName }}**.

Quy trình vẫn đang tiếp tục, nhưng người thụ hưởng sẽ không thể đăng nhập để tương tác với hệ thống. Vui lòng kiểm tra lại email hoặc tạo tài khoản thủ công cho họ.

<x-mail::button :url="$url" color="primary">
Xem quy trình
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>