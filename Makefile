test:
	echo "it's just test command"


# command about docker
up:
	cd docker/ && docker compose up -d
	echo http://localhost:92

down:
	cd docker/ && docker compose down

ps:
	cd docker/ && docker compose ps

bash:
	cd docker/ && docker container exec -it itpelag-php-fpm bash

db:
	cd docker/ && docker container exec -it itpelag-pgsql bash

generateKey:
	cd docker/ && docker container exec -it itpelag-php-fpm bash "php artisan key:generate"

migrate:
	cd docker/ && docker container exec -it itpelag-php-fpm bash "php artisan make migrate"

createUser:
	cd docker/ && docker container exec -it itpelag-php-fpm bash "php artisan moonshine:user"

cs-fix:
	cd docker/ && docker container exec -it itpelag-php-fpm php /application/vendor/bin/php-cs-fixer fix --config=/application/.php-cs-fixer.php

phpstan:
	cd docker/ && docker container exec -it itpelag-php-fpm php /application/vendor/bin/phpstan analyse --configuration=/application/phpstan.neon --memory-limit=512M
