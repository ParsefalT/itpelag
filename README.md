# ITPelag

Небольшая учётная система с **двойной записью**: каждая операция — это транзакция с проводками по дебету и кредиту. Система следит, чтобы суммы сходились, а проведённые документы нельзя было случайно переписать.

Стек: **Laravel 13**, админка **MoonShine 4**, **PostgreSQL**, **REST API** и **Swagger** для ручной проверки запросов.

---

Главные сущности:

- **Account** — счёт
- **Transaction** — операция (дата, описание)
- **JournalEntry** — проводка (счёт, сумма, дебет или кредит)

### Что нужно

Docker, Docker Compose и Git.

### 1. Клонировать и настроить `.env`

```bash
git clone <repository-url> itpelag
cd itpelag/project
cp .env.example .env
```

```env
APP_URL=http://localhost:92

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=leadger
DB_USERNAME=user
DB_PASSWORD=password
```

### 3. Настройка приложения

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

`php artisan moonshine:user` — создать администратора для входа в `/admin`.

---

## Админка

**http://localhost:92/admin**

## API

Базовый путь: `/api/v1`. Авторизация: **HTTP Basic Auth**.

## Swagger

Интерактивная документация: **http://localhost:92/api/documentation**

1. Нажать **Authorize**
2. Username: `api@example.com`, Password: `password`
3. Для POST сначала выполнить **GET /v1/accounts** и взять реальные `account_id`

Перегенерация спецификации:

```bash
make bash
composer swagger
# или в контейнере: php artisan l5-swagger:generate
```

## Тестовые данные после seed

- счета «Блинчики», «Кекс», «Милка»
- две сбалансированные транзакции
- API-пользователь `api@example.com`

Повторный seed не падает на дубликатах — сидеры идемпотентные.

## Тесты

```bash
make bash
php artisan make test
```
