#!/bin/bash

CURRENT_DIR=$(dirname $(readlink -f $0))

docker-compose -f $CURRENT_DIR/docker/compose.yml up -d

docker-compose -f $CURRENT_DIR/docker/compose.yml exec php56 bash
