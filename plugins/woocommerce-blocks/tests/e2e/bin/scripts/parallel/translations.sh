#!/usr/bin/env bash

wp language core install nl_NL
wp language core activate nl_NL
wp language plugin install woo-gutenberg-products-block nl_NL
wp language plugin update woo-gutenberg-products-block
wp language core activate en_US
