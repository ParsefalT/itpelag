# ITPelag — Laravel-приложение

Это рабочая директория Laravel-приложения учётной системы **ITPelag**.

Полное описание проекта, архитектура, Docker, API и Swagger — в корневом **[README.md](../README.md)**.

## Кратко

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan moonshine:user
php artisan l5-swagger:generate
```

| Сервис | URL |
|--------|-----|
| Приложение | http://localhost:92 |
| Админка MoonShine | http://localhost:92/admin |
| Swagger UI | http://localhost:92/api/documentation |

```bash
composer test          # PHPUnit
composer swagger       # перегенерация OpenAPI
```
