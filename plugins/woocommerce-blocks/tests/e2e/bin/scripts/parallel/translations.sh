#!/usr/bin/env bash

wp language core install nl_NL
wp language core activate nl_NL
wp language plugin install woocommerce nl_NL
wp language plugin update woocommerce
wp language core activate en_US
