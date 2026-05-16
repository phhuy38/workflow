<x-mail::message>
# Bạn có một task mới cần xử lý

Chào bạn,

Một công việc mới vừa được giao cho bạn trong hệ thống quy trình. Dưới đây là thông tin chi tiết:

- **Tên công việc:** {{ $stepName }}
- **Thuộc quy trình:** {{ $instanceName }}
- **Hạn chót hoàn thành:** {{ $deadline ?? 'Không có' }}

<x-mail::button :url="$url" color="primary">
Xem chi tiết và xử lý
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>