#!/usr/bin/env bash
set -eu

declare -p WORDPRESS_HOST
wait-for-it ${WORDPRESS_HOST} -t 120

declare -p RUN_WP_CLI_ON_DOCKER

## if file exists then exit early because initialization already happened.
if [ -f /var/www/html/.initialized ];
then
   echo "The environment has already been initialized."
   [[ "${RUN_WP_CLI_ON_DOCKER}" == "true" ]] && sleep infinity
   exit 0
fi

chown xfs:xfs /var/www/html/wp-content
chown xfs:xfs /var/www/html/wp-content/plugins

## switch user
if [ $UID -eq 0 ]; then
  user=xfs
  dir=/var/www/html
  cd "$dir"
  exec su -s /bin/bash "$user" "$0" -- "$@"
  # nothing will be executed beyond that line,
  # because exec replaces running process with the new one
fi

declare -p WORDPRESS_PORT
[[ "${WORDPRESS_PORT}" == 80 ]] && \
URL="http://localhost" || \
URL="http://localhost:${WORDPRESS_PORT}"

if $(wp core is-installed);
then
    echo "WordPress is already installed..."
else
    declare -p WORDPRESS_TITLE >/dev/null
    declare -p WORDPRESS_LOGIN >/dev/null
    declare -p WORDPRESS_PASSWORD >/dev/null
    declare -p WORDPRESS_EMAIL >/dev/null
    echo "Installing WordPress..."
    wp core install \
        --url=${URL} \
        --title="$WORDPRESS_TITLE" \
        --admin_user=${WORDPRESS_LOGIN} \
        --admin_password=${WORDPRESS_PASSWORD} \
        --admin_email=${WORDPRESS_EMAIL} \
        --skip-email
fi

## Check for an initialization script.
declare -r INIT_SCRIPT=$(command -v initialize.sh)

if [[ -x ${INIT_SCRIPT} ]]; then
    . "$INIT_SCRIPT"
fi

declare -r CURRENT_DOMAIN=$(wp option get siteurl)

if ! [[ ${CURRENT_DOMAIN} == ${URL} ]]; then
    echo "Replacing ${CURRENT_DOMAIN} with ${URL} in database..."
    wp search-replace ${CURRENT_DOMAIN} ${URL}
fi

if $(wp post list --post_type=page --name=ready);
then
    echo "Ready page already exists..."
else
    wp post create \
        --url=${URL} \
        --post_type=page \
        --post_status=publish \
        --post_title='Ready' \
        --post_content='E2E-tests.'
fi

echo "Visit $(wp option get siteurl)"
touch /var/www/html/.initialized

[[ "${RUN_WP_CLI_ON_DOCKER}" == "true" ]] && sleep infinity
