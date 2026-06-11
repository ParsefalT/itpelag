test:
	echo "it's just test command"


# command about docker
build:
	cd docker/ && docker compose build && docker compuse up -d

start:
	cd docker/ && docker compose up -d

restart:
	cd docker/ && docker compose restart

stop:
	cd docker/ && docker compose down

bash:
	cd docker/ && docker container exec -it itpelag-php-fpm bash

ps:
	cd docker/ && docker compose ps

db:
	cd docker/ && docker container exec -it itpelag-pgsql bash
