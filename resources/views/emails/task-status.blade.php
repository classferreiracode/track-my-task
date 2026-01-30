<x-mail::message>
# Atualização de task

@switch($type)
@case('assigned')
Você foi atribuído a uma task.
@break
@case('status_changed')
Sua task foi movida para um novo status.
@break
@case('overdue')
Sua task está atrasada.
@break
@case('deleted')
Sua task foi removida.
@break
@case('completed')
Sua task foi concluída.
@break
@default
Houve uma atualização na sua task.
@endswitch

**Task:** {{ $payload['task_title'] ?? '—' }}

@if(!empty($payload['status']))
**Status:** {{ $payload['status'] }}
@endif

@if(!empty($payload['workspace']))
**Workspace:** {{ $payload['workspace'] }}
@endif

@if(!empty($payload['board']))
**Board:** {{ $payload['board'] }}
@endif

@if(!empty($payload['due_date']))
**Prazo:** {{ \Illuminate\Support\Carbon::parse($payload['due_date'])->format('d/m/Y') }}
@endif

@if(!empty($payload['assigned_by']))
**Atribuída por:** {{ $payload['assigned_by'] }}
@endif

@if(!empty($payload['completed_at']))
**Concluída em:** {{ \Illuminate\Support\Carbon::parse($payload['completed_at'])->format('d/m/Y H:i') }}
@endif

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
