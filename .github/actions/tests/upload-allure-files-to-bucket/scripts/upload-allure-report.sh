#!/usr/bin/env bash

echo "Uploading allure-report folder..."
aws s3 cp $ALLURE_REPORT_DIR \
    "$S3_BUCKET/artifacts/$GITHUB_RUN_ID/$DESTINATION_DIR/allure-report" \
    --recursive \
    --only-show-errors
echo "Done"