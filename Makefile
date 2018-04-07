PHP_CS_FIXER_FUTURE_MODE=1
PHPSTAN=./phpstan.phar
PHP-CS-FIXER=./php-cs-fixer-v2.phar
INFECTION=./infection.phar
INFECTION_FLAGS=--threads=4

.PHONY: all analyze test
all: analyze test
#Run all checks, default when running 'make'

vendor: composer.json composer.lock
	composer install

./php-cs-fixer-v2.phar:
	wget http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar
	chmod a+x ./php-cs-fixer-v2.phar

./phpstan.phar:
	wget https://github.com/phpstan/phpstan/releases/download/0.9.1/phpstan.phar
	chmod a+x ./phpstan.phar

./infection.phar:
	wget https://github.com/infection/infection/releases/download/0.8.1/infection.phar
	wget https://github.com/infection/infection/releases/download/0.8.1/infection.phar.pubkey
	chmod a+x ./infection.phar


.PHONY: test-unit test-infection
test: test-unit test-infection

test-unit: vendor
	vendor/bin/phpunit

test-infection: $(INFECTION)
	$(INFECTION) $(INFECTION_FLAGS)

analyze: validate phpstan cs-check

phpstan: vendor $(PHPSTAN)
	$(PHPSTAN) analyse src tests --level=max --no-interaction -c phpstan.neon

cs-fix: vendor $(PHP-CS-FIXER)
	$(PHP-CS-FIXER) fix -v --diff

cs-check: vendor $(PHP-CS-FIXER)
	$(PHP-CS-FIXER) fix -v --diff --dry-run

validate:
	composer validate --strict
