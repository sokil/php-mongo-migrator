default: unsigned

GPG_KEY_ID="dmytro.sokil@gmail.com"

BOX_INSTALLED=$(box --version 2> /dev/null)

check_box_installed:
ifndef BOX_INSTALLED
	$(error "box not installed")
endif

pre-build:
	rm -rf ./build
	mkdir -p build
	composer install --no-dev --prefer-dist -o

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
