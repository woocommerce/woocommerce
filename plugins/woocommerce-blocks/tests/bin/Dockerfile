FROM wordpress:latest

CMD echo "Installing dependencies..."

RUN apt-get update && \
	apt-get -y install subversion unzip wget git-all

CMD echo "Installing tests..."

ENV WP_TESTS_DIR=/tmp/wordpress-tests-lib
ENV WP_CORE_DIR=/usr/src/wordpress

COPY install-wp-tests.sh /usr/local/bin/dockerInit
RUN chmod +x /usr/local/bin/dockerInit
RUN dockerInit wordpress wordpress wordpress db latest true

# This is the entrypoint script from the WordPress Docker package.
CMD ["docker-entrypoint.sh"]

# Keep container active
CMD ["apache2-foreground"]
