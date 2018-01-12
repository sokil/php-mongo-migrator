default: build

BOX_INSTALLED=$(box --version 2> /dev/null)

check_box_installed:
ifndef BOX_INSTALLED
	$(error "box not installed")
endif

build: check_box_installed
	composer.phar update --no-dev -o
	box build -v

dev:
	composer.phar update -o

clean:
	rm -rf ./vendor
	rm -rf ./build/mongo-migrator.phar
