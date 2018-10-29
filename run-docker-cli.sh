#!/bin/bash

CURRENT_DIR=$(dirname $(readlink -f $0))

docker-compose -f ./docker/compose.yml ps -q php56 2> /dev/null > /dev/null

if [[ $? -eq 1 ]]; then
    docker-compose -f $CURRENT_DIR/docker/compose.yml up -d
fi

docker-compose -f $CURRENT_DIR/docker/compose.yml exec php56 bash