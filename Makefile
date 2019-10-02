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

ifeq ($(MONGO_DRIVER),new)
    PHAR_FILE=mongo-migrator.phar
    PHAR_KEY_FILE=mongo-migrator.phar.asc
else ifeq ($(MONGO_DRIVER),legacy)
    PHAR_FILE=mongo-migrator.legacy.phar
    PHAR_KEY_FILE=mongo-migrator.legacy.phar.asc
endif

pre-build: clean
    ifeq ($(MONGO_DRIVER),new)
		$(info New MongoDB driver found)
    else ifeq ($(MONGO_DRIVER),legacy)
		$(info Legacy MongoDB driver found)
    else
		$(error MongoDB driver "$(MONGO_DRIVER)" not found)
    endif
	mkdir -p build
	mkdir -p dist
	composer install --no-dev --prefer-dist -o
    ifeq ($(MONGO_DRIVER),new)
		composer require --update-no-dev --prefer-dist -o alcaeus/mongo-php-adapter
    endif

gpg-signed: pre-build
	cat box.json | sed -E 's/\"key\": \".+\",//g' | sed -E 's/\"algorithm\": \".+\",//g' | sed -E 's/\"output\": \".+\",/\"output\": \"build\/$(PHAR_FILE)\",/g' > box.unsigned.json
	box build -v -c box.unsigned.json
	rm -f box.unsigned.json
	gpg --import keys/private.asc
	gpg -u $(GPG_KEY_ID) --detach-sign --output build/$(PHAR_KEY_FILE) build/$(PHAR_FILE)
	mv build/*.phar dist
	mv build/*.phar.asc dist

openssl-signed: pre-build
	box build -v
	mv build/*.phar dist/

dev:
	composer update --prefer-dist -o

clean:
	rm -f box.unsigned.json
	rm -f composer.lock
	rm -rf ./vendor
	rm -rf ./build
