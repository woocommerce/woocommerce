# The images `gh local-ci run --docker` cuts its containers from: the default
# one (PHP 8.3, like the ubuntu-24.04 runner) and one per entry in
# docker.images. PHP_IMAGE is the official PHP CLI image, pinned by digest in
# .github/local-ci.json; the Node and Composer images are pinned by digest
# here. The tool rebuilds an image only when this directory or its args change.
#
# Every image needs Composer as well as Node, because pnpm install runs
# composer install in several workspace postinstall scripts. Node and pnpm are
# the same in every image: they share the worktree's node_modules, and native
# modules are built for one Node ABI. Keep them in step with .nvmrc and the
# packageManager field in package.json.
#
# Every job gets a fresh container and only named volumes survive between
# them, so pnpm must be in the image itself.
ARG PHP_IMAGE
FROM node:24.22.3-bookworm@sha256:9d266751ca3d7fdbe651c878ffd5ad0a42fed49a18c283509267c97f025b7496 AS node
FROM composer:2.10.3@sha256:2cf275428757e5435a667bfc0d038b14520ca02be2b1b70d44a05c8c19e2de48 AS composer

FROM ${PHP_IMAGE}

# What composer needs to fetch and unpack packages, what node-gyp needs to
# build native modules, and the extensions setup-php enables by default that
# the official image lacks. PHP 7.4 images are Debian bullseye, which is past
# end of life and served only from archive.debian.org.
RUN . /etc/os-release \
	&& if [ "$VERSION_CODENAME" = bullseye ]; then sed -i 's#http://deb.debian.org#http://archive.debian.org#' /etc/apt/sources.list; fi \
	&& apt-get update \
	&& apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev python3 make g++ \
	&& docker-php-ext-install intl zip bcmath \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
	&& ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
	&& npm install --global pnpm@10.33.0 && npm cache clean --force

# No USER: gh local-ci picks the user. On Linux it runs jobs as the host's
# uid:gid and hands it the volumes first; elsewhere the daemon maps file
# ownership, so the image default is enough.
#
# PHPStan analyses with --memory-limit=2G; the CLI default is 128M.
RUN echo "memory_limit = -1" > "$PHP_INI_DIR/conf.d/local-ci.ini"
