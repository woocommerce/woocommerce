#!/usr/bin/env bash
set -x

# Import the encrypted SSH key
openssl aes-256-cbc -K $encrypted_aa1eba18da39_key -iv $encrypted_aa1eba18da39_iv -in tests/e2e-tests/scripts/deploy-key.enc -out tests/e2e-tests/scripts/deploy-key -d
chmod 600 tests/e2e-tests/scripts/deploy-key
mv tests/e2e-tests/scripts/deploy-key ~/.ssh/id_rsa
