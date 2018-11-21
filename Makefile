default: build

BOX_INSTALLED=$(box --version 2> /dev/null)

check_box_installed:
ifndef BOX_INSTALLED
	$(error "box not installed")
endif

build: check_box_installed
	mkdir -p build
	composer update --no-dev -o
	box build -v
	gpg -u dmytro.sokil@gmail.com --detach-sign --output build/mongo-migrator.phar.asc build/mongo-migrator.phar

dev:
	composer update -o

clean:
	rm -rf ./vendor
	rm -rf ./build
