<x-mail::message>
# Bạn có tin nhắn mới

Chào bạn,

Bạn vừa nhận được một tin nhắn mới từ **{{ $senderName }}** trong bước **{{ $stepName }}** của quy trình **{{ $instanceName }}**.

**Nội dung tin nhắn:**
> {{ $messageBody }}

<x-mail::button :url="$url" color="primary">
Xem và phản hồi
</x-mail::button>

Cảm ơn bạn,  
{{ config('app.name') }}
</x-mail::message>