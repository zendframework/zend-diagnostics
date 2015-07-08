install:
ifdef TRAVIS_PHP_VERSION
	composer self-update
	composer install --prefer-source
else
	if [ ! -f ./composer.phar ]; then curl -sS https://getcomposer.org/installer | php; fi
	./composer.phar install --prefer-source
endif

test:
	./vendor/bin/phpunit -c ./tests/ --coverage-text
	./vendor/bin/php-cs-fixer fix -v --dry-run --level=psr2 .
