default: unsigned

# Define GPG key for GPG signing (required by phive)
GPG_KEY_ID="dmytro.sokil@gmail.com"

# Check if phar compiler installed
ifeq ($(strip $(shell box --version 2> /dev/null)),)
	$(error "box not installed")
endif

# Defing MongoDB driver
ifndef ($(MONGO_DRIVER))
	ifneq ($(shell php -m | grep mongodb),)
		MONGO_DRIVER=new
	else
		ifneq ($(shell php -m | grep mongo),)
			MONGO_DRIVER=legacy
		else
			MONGO_DRIVER=none
		endif
	endif
endif


pre-build:
    ifeq ($(MONGO_DRIVER),new)
		$(info New MongoDB driver found)
    else ifeq ($(MONGO_DRIVER),legacy)
		$(info Legacy MongoDB driver found)
    else
		$(error MongoDB driver "$(MONGO_DRIVER)" not found)
    endif
	rm -rf ./build
	mkdir -p build
	rm -f composer.lock
	composer install --no-dev --prefer-dist -o
    ifeq ($(MONGO_DRIVER),new)
		composer require --update-no-dev --prefer-dist -o alcaeus/mongo-php-adapter
    endif

unsigned: pre-build
	cat box.json | sed -E 's/\"key\": \".+\",//g' | sed -E 's/\"algorithm\": \".+\",//g' > box.unsigned.json
	box build -v -c box.unsigned.json
	rm -f box.unsigned.json

gpg-signed: unsigned
	gpg --import keys/private.asc
	gpg -u $(GPG_KEY_ID) --detach-sign --output build/mongo-migrator.phar.asc build/mongo-migrator.phar

openssl-signed: pre-build
	box build -v

dev:
	composer update --prefer-dist -o

clean:
	rm -f box.unsigned.json
	rm -f composer.lock
	rm -rf ./vendor
	rm -rf ./build
