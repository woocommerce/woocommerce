FROM alpine

WORKDIR /tmp

RUN apk --no-cache add \
	bash \
	ca-certificates \
	git \
	curl \
	mariadb-client \
	ncurses \
	nodejs \
	npm \
	subversion \
	unzip \
	wget \
	php7 \
	php7-bcmath \
	php7-ctype \
	php7-curl \
	php7-dom \
	php7-exif \
	php7-json \
	php7-mcrypt \
	php7-opcache \
	php7-openssl \
	php7-mbstring \
	php7-mysqli \
	php7-pcntl \
	php7-pdo \
	php7-pdo_pgsql \
	php7-pdo_sqlite \
	php7-pdo_mysql \
	php7-phar \
	php7-session \
	php7-soap \
	php7-tokenizer \
	php7-xdebug \
	php7-xml \
	php7-xmlreader \
	php7-zip \
	php7-zlib

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

RUN curl -sL https://phar.phpunit.de/phpunit-7.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit

VOLUME ["/app"]
WORKDIR /app

COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
