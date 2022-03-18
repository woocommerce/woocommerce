FROM alpine

WORKDIR /tmp

ARG PHP_VERSION

RUN apk --no-cache add \
	bash \
	ca-certificates \
	git \
	curl \
	mariadb-client \
	ncurses \
	nodejs \
	subversion \
	unzip \
	wget

RUN curl -f https://get.pnpm.io/v6.16.js | node - add --global pnpm
COPY install-php-version.sh /tmp/
RUN chmod u+x /tmp/install-php-version.sh && /tmp/install-php-version.sh

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

RUN curl -sL https://phar.phpunit.de/phpunit-7.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit

VOLUME ["/app"]
WORKDIR /app

COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
