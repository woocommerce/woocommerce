#!/bin/bash
# make-pot over one build folder. Usage: pot.sh <label> <dir>
cd /home/user/store
( time wp i18n make-pot "$2" "/home/user/wc-bench/pot/$1.pot" --domain=woocommerce --skip-php --skip-audit --debug ) > "/home/user/wc-bench/pot/$1.log" 2>&1
echo "$1 msgids=$(grep -c '^msgid ' /home/user/wc-bench/pot/$1.pot) translatorComments=$(grep -c '^#\. translators' /home/user/wc-bench/pot/$1.pot) couldNotParse=$(grep -ci 'could not parse' /home/user/wc-bench/pot/$1.log) $(grep real /home/user/wc-bench/pot/$1.log)"
