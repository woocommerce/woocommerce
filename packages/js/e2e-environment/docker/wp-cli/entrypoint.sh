#!/usr/bin/env bash
set -eu

# If WordPress is installed and the page "ready" exists, we bail the initialization.
if [ $(wp --allow-root core is-installed) ] && [ $(wp --allow-root post exists $(wp --allow-root post list --format=ids --post_name=ready)) ];
then
   echo "The environment has already been initialized."
   exit 0
else
	echo "Initializing the environment..."
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

wp post create \
	--url=${URL} \
	--post_type=page \
	--post_status=publish \
	--post_title='Ready' \
	--post_content='E2E-tests.'

echo "Visit $(wp option get siteurl)"
