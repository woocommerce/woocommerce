#!/usr/bin/env bash
PHP_FILES_CHANGED=""

for FILE in $(echo $CHANGED_FILES | tr ',' '\n')
do
	if [[ $FILE =~ ".php" && -e $FILE ]]; then
		PHP_FILES_CHANGED+="$FILE "
	fi	
done

if [ "$PHP_FILES_CHANGED" != "" ]; then
	composer install
	echo "Running Code Sniffer."
	./vendor/bin/phpcs --encoding=utf-8 -n -p $PHP_FILES_CHANGED
else
	echo "No changed files detected, sniffer not run."
fi
