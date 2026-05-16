<x-mail::message>
# Thông báo: Đã tự động đóng quy trình

Chào bạn,

Quy trình **{{ $instanceName }}** đã tự động được hệ thống chuyển sang trạng thái hoàn thành.

Hành động này được thực hiện vì tất cả các bước trong quy trình đã được hoàn thành, nhưng trạng thái tổng thể của quy trình vẫn chưa được cập nhật.

<x-mail::button :url="$url" color="primary">
Xem chi tiết
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>