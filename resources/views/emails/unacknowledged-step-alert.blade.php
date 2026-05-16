<x-mail::message>
# Cần chú ý: Bước chưa được nhận việc

Chào bạn,

Một bước trong quy trình mà bạn khởi tạo đã được giao nhưng người phụ trách chưa xác nhận nhận việc.

- **Công việc:** {{ $stepName }}
- **Người phụ trách:** {{ $executorName }}
- **Thời gian đã chờ:** {{ $timeWaited }}

Bạn có thể liên hệ nhắc nhở hoặc cấu hình lại người phụ trách nếu cần thiết.

<x-mail::button :url="$url" color="primary">
Xem chi tiết và nhắc nhở
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>