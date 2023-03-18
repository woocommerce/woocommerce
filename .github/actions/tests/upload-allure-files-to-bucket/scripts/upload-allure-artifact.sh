#!/usr/bin/env bash

upload_allure_results () {
    if [[ $INCLUDE_ALLURE_RESULTS != "true" ]]; then
        return
    fi

    SOURCE="$ALLURE_RESULTS_DIR"
    DESTINATION="$S3_BUCKET/artifacts/$GITHUB_RUN_ID/$DESTINATION_DIR/allure-results"

    aws s3 cp "$SOURCE" "$DESTINATION" \
        --recursive \
        --only-show-errors
}

upload_allure_report () {
    SOURCE="$ALLURE_REPORT_DIR"
    DESTINATION="$S3_BUCKET/artifacts/$GITHUB_RUN_ID/$DESTINATION_DIR/allure-report"

    aws s3 cp "$SOURCE" "$DESTINATION" \
        --recursive \
        --only-show-errors
}

upload_allure_results
upload_allure_report

EXIT_CODE=$(echo $?)
exit $EXIT_CODE