#!/usr/bin/env bash

echo "Uploading allure-results folder..."
aws s3 cp $ALLURE_RESULTS_DIR \
    "$S3_BUCKET/artifacts/$GITHUB_RUN_ID/$DESTINATION_DIR/allure-results" \
    --recursive \
    --only-show-errors
echo "Done"