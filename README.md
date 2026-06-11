# ITPelag

Небольшая учётная система с **двойной записью**: каждая операция — это транзакция с проводками по дебету и кредиту. Система следит, чтобы суммы сходились, а проведённые документы нельзя было случайно переписать.

Стек: **Laravel 13**, админка **MoonShine 4**, **PostgreSQL**, **REST API** и **Swagger** для ручной проверки запросов.

---

## Что умеет

- **План счетов** — код, название, тип (актив, доход, расход и т.д.)
- **Транзакции и проводки** — минимум две строки, дебет = кредит
- **Проведение** — сбалансированная транзакция помечается как проведённая (`is_posted`)
- **Защита проведённых** — их нельзя менять и удалять (и в админке, и через API)
- **ОСВ** — оборотно-сальдовая ведомость за выбранный период
- **Экспорт** транзакций в Excel и CSV
- **API** — создание транзакций, список счетов, остатки по счёту

В отчётах и остатках учитываются **только проведённые** транзакции. Черновики в цифры не попадают.

---

## Как устроен проект

```
itpelag/
├── docker/     # nginx, php-fpm, PostgreSQL
└── project/    # Laravel-приложение
```

Главные сущности:

- **Account** — счёт
- **Transaction** — операция (дата, описание)
- **JournalEntry** — проводка (счёт, сумма, дебет или кредит)

Бизнес-логика лежит в `app/Services/` (`LedgerService`, `TrialBalanceService`, `AccountBalanceService`).

---

## Быстрый старт

### Что нужно

Docker, Docker Compose и Git.

### 1. Клонировать и настроить `.env`

```bash
git clone <repository-url> itpelag
cd itpelag/project
cp .env.example .env
```

Для Docker обычно хватает таких значений:

```env
APP_URL=http://localhost:92

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=leadger
DB_USERNAME=user
DB_PASSWORD=password
```

### 2. Поднять контейнеры

Из корня репозитория (`itpelag/`):

```bash
make build
```

После запуска:

- приложение: **http://localhost:92**
- PostgreSQL с хоста: **localhost:5427** (порт из `docker/.env`)

### Предпочтительно команды через make

Полный список — в [`Makefile`](Makefile). Формат: `make <имя-команды>`.

| Команда | Что делает |
|--------|------------|
| `make build` | Сборка и запуск контейнеров |
| `make start` | Запуск контейнеров |
| `make restart` | Перезапуск |
| `make stop` | Остановка |
| `make bash` | Войти в контейнер `php-fpm` |
| `make ps` | Список запущенных контейнеров |
| `make db` | Войти в контейнер PostgreSQL |

Пример:

```bash
make build
```

### 3. Первичная настройка приложения

Удобнее через `make bash`, дальше команды внутри контейнера:

```bash
make bash

composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan moonshine:user
php artisan l5-swagger:generate
```

`moonshine:user` — создать администратора для входа в `/admin`.

---

## Админка

**http://localhost:92/admin**

В меню:

- **Счета** — план счетов (счёт с проводками удалить нельзя — только деактивировать)
- **Транзакции** — документы, проводки, фильтр, экспорт
- **ОСВ** — отчёт за период

---

## API

Базовый путь: `/api/v1`. Авторизация: **HTTP Basic Auth**.

После `db:seed`:

| | |
|---|---|
| Email | `api@example.com` |
| Пароль | `password` |

Основные методы:

| Метод | Путь | Зачем |
|-------|------|--------|
| GET | `/api/v1/accounts` | Список счетов (нужен `account_id` для POST) |
| GET | `/api/v1/accounts/{id}/balance` | Остаток по счёту |
| GET/POST | `/api/v1/transactions` | Список / создание |
| GET/PUT/DELETE | `/api/v1/transactions/{id}` | Просмотр / изменение / удаление |

Сначала узнайте `id` счетов:

```bash
curl -u api@example.com:password \
  -H "Accept: application/json" \
  http://localhost:92/api/v1/accounts
```

Пример создания транзакции (подставьте свои `account_id` из ответа выше):

```bash
curl -u api@example.com:password \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -X POST http://localhost:92/api/v1/transactions \
  -d '{
    "date": "2026-06-11",
    "description": "Продажа кексов",
    "entries": [
      {"account_id": 21, "amount": 100.00, "type": "debit"},
      {"account_id": 22, "amount": 100.00, "type": "credit"}
    ]
  }'
```

В проводках нужны поля **`amount`** и **`type`** (`debit` / `credit`), суммы дебета и кредита должны совпадать.

---

## Swagger

Интерактивная документация: **http://localhost:92/api/documentation**

1. Нажать **Authorize**
2. Username: `api@example.com`, Password: `password`
3. Для POST сначала выполнить **GET /v1/accounts** и взять реальные `account_id`

Перегенерация спецификации:

```bash
cd project && composer swagger
# или в контейнере: php artisan l5-swagger:generate
```

---

## Тестовые данные после seed

- счета «Блинчики», «Кекс», «Милка»
- две сбалансированные транзакции
- API-пользователь `api@example.com`

Повторный seed не падает на дубликатах — сидеры идемпотентные.

---

## Тесты

```bash
cd project
composer test
```

---

## Стек

PHP 8.4+, Laravel 13, MoonShine 4, PostgreSQL 14, l5-swagger, Docker.

Подробности по Laravel-части — в [`project/README.md`](project/README.md).

---

## Лицензия

MIT.
