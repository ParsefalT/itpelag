# ITPelag — учётная система с двойной записью

**ITPelag** — веб-приложение для ведения бухгалтерского учёта по принципу **двойной записи** (double-entry bookkeeping). Каждая операция оформляется транзакцией с проводками по дебету и кредиту; система контролирует баланс и защищает проведённые документы от изменений.

Проект построен на **Laravel 13** и **MoonShine 4** (админ-панель), с **REST API** для интеграций и **Swagger UI** для интерактивной документации.

---

## Возможности

| Область                | Описание                                                                                         |
| ---------------------- | ------------------------------------------------------------------------------------------------ |
| **План счетов**        | Счета с кодом, названием и типом (актив, пассив, доход, расход)                                  |
| **Транзакции**         | Документы с датой, описанием и набором проводок                                                  |
| **Двойная запись**     | Минимум две проводки; сумма дебета должна равняться сумме кредита                                |
| **Проведение**         | Транзакция автоматически помечается как проведённая (`is_posted`), когда проводки сбалансированы |
| **Защита проведённых** | Редактирование и удаление проведённых транзакций запрещено в админке и API                       |
| **ОСВ**                | Отчёт «Оборотно-сальдовая ведомость» с фильтром по периоду                                       |
| **Экспорт**            | Выгрузка транзакций в Excel и CSV                                                                |
| **REST API**           | CRUD транзакций и остаток по счёту (HTTP Basic Auth)                                             |
| **Swagger**            | OpenAPI 3.0 и UI на `/api/documentation`                                                         |

---

## Архитектура

```
itpelag/
├── docker/          # Docker Compose: nginx, php-fpm, PostgreSQL
└── project/         # Laravel-приложение
    ├── app/
    │   ├── Http/Controllers/Api/   # REST API
    │   ├── MoonShine/              # Админ-панель
    │   ├── OpenApi/                # Общие OpenAPI-схемы
    │   ├── Services/               # Бизнес-логика (Ledger, TrialBalance, …)
    │   └── Models/                 # Account, Transaction, JournalEntry
    ├── routes/api.php
    └── storage/api-docs/           # Сгенерированная OpenAPI-спецификация
```

### Основные сущности

- **Account** — счёт учёта (код, название, тип).
- **Transaction** — хозяйственная операция (дата, описание, флаг `is_posted`).
- **JournalEntry** — проводка: счёт, сумма по дебету или кредиту.

Расчёты сумм выполняются в **целых копейках** (integer cents), чтобы не зависеть от расширения `bcmath` в окружении.

### Сервисы

- `LedgerService` — создание, обновление и удаление транзакций с валидацией баланса.
- `AccountBalanceService` — обороты и сальдо по счёту с учётом типа счёта.
- `TrialBalanceService` — данные для отчёта ОСВ.

---

## Быстрый старт (Docker)

### Требования

- Docker и Docker Compose
- Git

### 1. Клонирование и окружение

```bash
git clone <repository-url> itpelag
cd itpelag/project
cp .env.example .env
```

В `.env` для Docker укажите (значения по умолчанию в примере):

```env
APP_URL=http://localhost:92

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=leadger
DB_USERNAME=user
DB_PASSWORD=password
```

### 2. Запуск контейнеров

```bash
cd ../docker
docker compose up -d --build
```

### Предпочтительно команды через make

полный список находиться в _Makefile_
типо коммпанды `make 'имя команды'`

```
сборка и запуска контейнеров - build:
запуска контейнеров - start:
перезапуск контейнеров - restart:
остановка контейнеров - stop:
зайти внутрь контейнера php-fpm - bash:
просмотр запущеных контейнеров - ps:
```

```bash
make build
```

Приложение будет доступно по адресу: **http://localhost:92**

PostgreSQL с хоста: `localhost:5427` (см. `docker/.env`).

### 3. Установка зависимостей и миграции

```bash
docker exec -it itpelag-php-fpm composer install
docker exec -it itpelag-php-fpm php artisan key:generate
docker exec -it itpelag-php-fpm php artisan migrate
docker exec -it itpelag-php-fpm php artisan db:seed
docker exec -it itpelag-php-fpm php artisan moonshine:user
docker exec -it itpelag-php-fpm php artisan l5-swagger:generate
```

Команда `moonshine:user` создаёт администратора для входа в панель.

---

## Админ-панель MoonShine

| URL                       | Описание       |
| ------------------------- | -------------- |
| http://localhost:92/admin | Вход в админку |

Разделы меню:

- **Счета** — план счетов
- **Транзакции** — документы и проводки (фильтр по счёту, экспорт)
- **ОСВ** — оборотно-сальдовая ведомость за период

---

## REST API

Базовый префикс: `/api/v1`. Авторизация: **HTTP Basic Auth**.

### Учётные данные (после `db:seed`)

| Поле   | Значение          |
| ------ | ----------------- |
| Email  | `api@example.com` |
| Пароль | `password`        |

### Эндпоинты

| Метод       | Путь                            | Описание                              |
| ----------- | ------------------------------- | ------------------------------------- |
| `GET`       | `/api/v1/transactions`          | Список транзакций                     |
| `POST`      | `/api/v1/transactions`          | Создание транзакции с проводками      |
| `GET`       | `/api/v1/transactions/{id}`     | Одна транзакция                       |
| `PUT/PATCH` | `/api/v1/transactions/{id}`     | Обновление (только непроведённой)     |
| `DELETE`    | `/api/v1/transactions/{id}`     | Удаление (только непроведённой)       |
| `GET`       | `/api/v1/accounts`              | Список счетов (получить `account_id`) |
| `GET`       | `/api/v1/accounts/{id}/balance` | Обороты и сальдо по счёту             |

### Список счетов (узнать account_id)

```bash
curl -u api@example.com:password \
  -H "Accept: application/json" \
  http://localhost:92/api/v1/accounts
```

### Пример: создание транзакции

Сначала получите реальные `id` счетов из `/api/v1/accounts`. В проводках указываются `amount` и `type` (`debit` / `credit`), суммы дебета и кредита должны совпадать.

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

### Пример: остаток по счёту

```bash
curl -u api@example.com:password \
  -H "Accept: application/json" \
  http://localhost:92/api/v1/accounts/1/balance
```

---

## Swagger / OpenAPI

| URL                                      | Описание                                 |
| ---------------------------------------- | ---------------------------------------- |
| http://localhost:92/api/documentation    | Swagger UI (интерактивная документация)  |
| `project/storage/api-docs/api-docs.json` | Сгенерированная спецификация OpenAPI 3.0 |

Документация собирается из PHP-атрибутов в контроллерах API и общих схем в `app/OpenApi/OpenApiSpec.php`.

### Перегенерация после изменений API

```bash
# локально
cd project && php artisan l5-swagger:generate

# или через Composer
composer swagger

# в Docker
docker exec -it itpelag-php-fpm php artisan l5-swagger:generate
```

В Swagger UI можно авторизоваться через **Authorize** (Basic Auth: `api@example.com` / `password`) и выполнять запросы к API прямо из браузера.

---

## Тестовые данные

После `php artisan db:seed` в базе:

- **3 счёта**: «Блинчики» (актив), «Кекс» (доход), «Милка» (расход)
- **2 транзакции** с проводками
- **API-пользователь** `api@example.com`

---

## Тесты

```bash
cd project
php artisan test
# или
composer test
```

Покрытие включает:

- unit-тесты `LedgerService` (баланс, валидация)
- feature-тесты REST API (CRUD, авторизация, остатки)
- проверку сидеров

---

## Стек технологий

- PHP 8.4+, Laravel 13
- MoonShine 4 + moonshine/import-export
- PostgreSQL 14
- darkaonline/l5-swagger (OpenAPI / Swagger UI)
- Docker (nginx, php-fpm, postgres)

---

## Структура репозитория

| Каталог                            | Назначение                              |
| ---------------------------------- | --------------------------------------- |
| [`project/`](project/)             | Исходный код Laravel-приложения         |
| [`docker/`](docker/)               | Docker Compose и конфигурация окружения |
| [`project/tests/`](project/tests/) | PHPUnit-тесты                           |

Подробности по Laravel-части — в [`project/README.md`](project/README.md).

---

## Лицензия

MIT (фреймворк Laravel — [MIT](https://opensource.org/licenses/MIT)).
