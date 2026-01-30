<x-mail::message>
# Você recebeu um convite

{{ $inviterName }} convidou você para o workspace **{{ $workspaceName }}**.

<x-mail::button :url="route('workspaces.invitations.show', $invitation->token)">
Aceitar convite
</x-mail::button>

Se você não esperava este e-mail, pode ignorá-lo.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
