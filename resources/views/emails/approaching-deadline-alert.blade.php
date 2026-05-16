<x-mail::message>
# {{ $isOverdue ? 'Quá hạn công việc' : 'Công việc sắp đến hạn' }}

Chào bạn,

Bước công việc **{{ $stepName }}** thuộc quy trình **{{ $instanceName }}** {{ $isOverdue ? 'đã quá hạn' : 'sắp đến hạn' }} xử lý.

- **Hạn chót:** {{ $deadline }}

Vui lòng xử lý sớm để đảm bảo tiến độ chung của quy trình.

<x-mail::button :url="$url" color="primary">
Xem chi tiết và xử lý
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>