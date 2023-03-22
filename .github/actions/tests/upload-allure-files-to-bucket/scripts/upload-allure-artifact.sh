#!/usr/bin/env bash

s3_upload () {
    aws s3 cp "$1" "$2" \
        --recursive
}

upload_allure_results () {
    if [[ $INCLUDE_ALLURE_RESULTS != "true" ]]; then
        return
    fi

    SOURCE="$ALLURE_RESULTS_DIR"
    DESTINATION="$S3_BUCKET/artifacts/$GITHUB_RUN_ID/$ARTIFACT_NAME/allure-results"

    s3_upload "$SOURCE" "$DESTINATION"
}

upload_allure_report () {
    SOURCE="$ALLURE_REPORT_DIR"
    DESTINATION="$S3_BUCKET/artifacts/$GITHUB_RUN_ID/$ARTIFACT_NAME/allure-report"

    s3_upload "$SOURCE" "$DESTINATION"
}

upload_allure_results
upload_allure_report

EXIT_CODE=$(echo $?)
exit $EXIT_CODE