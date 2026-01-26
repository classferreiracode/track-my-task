# Track My Task

Track My Task e um app Laravel + Inertia (Vue) para gerenciar tarefas com tracking de tempo e relatorios por periodo.

## Recursos

- Board estilo Trello com colunas configuraveis.
- Timer por tarefa (start/stop) e somatorios diarios, semanais e mensais.
- Exportacao de relatorio CSV (compatível com Excel) por periodo.
- Dashboard com KPIs basicos.

## Requisitos

- Docker (Laravel Sail)
- Node.js (para build do frontend)

## Como rodar

1. Copie o arquivo `.env` e ajuste o que precisar.
2. Suba os containers:

```
vendor/bin/sail up -d
```

3. Rode as migrations:

```
vendor/bin/sail artisan migrate
```

4. Rode o frontend:

```
vendor/bin/sail npm run dev
```

Abra a aplicacao no navegador:

```
vendor/bin/sail open
```

## Uso

- Acesse `Dashboard` para ver KPIs.
- Va em `Tasks` para criar tarefas, mover entre colunas e iniciar timers.
- Use o bloco `Exportar relatorio` para gerar um CSV do periodo selecionado.

## Testes

```
vendor/bin/sail artisan test --compact
```
