#!/usr/bin/env bash

wp option set --format=json woocommerce_cod_settings '{
    "enabled":"yes",
    "title":"Cash on delivery",
    "description":"Cash on delivery description",
    "instructions":"Cash on delivery instructions"
}'

wp option set --format=json woocommerce_bacs_settings '{
    "enabled":"yes",
    "title":"Direct bank transfer",
    "description":"Direct bank transfer description",
    "instructions":"Direct bank transfer instructions"
}'

wp option set --format=json woocommerce_cheque_settings '{
    "enabled":"yes",
    "title":"Check payments",
    "description":"Check payments description",
    "instructions":"Check payments instructions"
}'
