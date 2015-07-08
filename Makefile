install:
ifdef TRAVIS_PHP_VERSION
	composer self-update
	composer install --prefer-source
else
	test -s ./composer.phar || { curl -sS https://getcomposer.org/installer | php }
	./composer.phar install --prefer-source
endif

test:
	phpunit -c ./tests/ --coverage-text
	output=$(php php-cs-fixer.phar fix -v --dry-run --level=psr2 .); if [[ $output ]]; then while read -r line; do echo -e "\e[00;31m$line\e[00m"; done <<< "$output"; false; fi;
