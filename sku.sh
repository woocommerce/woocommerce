#!/bin/bash

# Define the URL and authentication
URL="https://d.w.test/wp-json/wc/v3/products/batch"
CK="ck_7b87bfb00d141081ea0b2be4c443b939ac60677e"
CS="cs_0fbab889a09db5d83af5f2096ec553cf011c47c0"
CONTENT_TYPE="Content-Type: application/json"


INITIAL_SKU=3020

ROUNDS=2

CONCURRENT_REQUESTS=10

echo "Sending $ROUNDS rounds of $CONCURRENT_REQUESTS concurrent requests..."

count_errors() {
    local round=$1
    local error_count=0

    for i in $(seq 1 $CONCURRENT_REQUESTS); do
        if grep -q "error" "log_${round}_${i}.txt"; then
            error_count=$((error_count + 1))
        fi
    done

    echo $((CONCURRENT_REQUESTS - $error_count))
}

for j in $(seq 1 $ROUNDS); do
    SKU=$(($INITIAL_SKU + $j - 1)) 
    echo "Round $j with SKU $SKU..."

    for i in $(seq 1 $CONCURRENT_REQUESTS); do
        JSON_DATA=$(cat <<EOF
{
    "create": [
        {
            "name": "${j}-${i} New Test Product",
            "type": "simple",
            "sku": "${SKU}"
        }
    ]
}
EOF
)
        curl -X POST $URL \
            -u $CK:$CS \
            -H "$CONTENT_TYPE" \
            -d "$JSON_DATA" -k > "log_${j}_${i}.txt" 2>&1 &
    done

    wait
    echo "Completed round $j."

    error_count=$(count_errors $j)
    echo "Total products created in round $j with sku $SKU: $error_count" >> error_counts.txt
done

echo "All requests sent. Check log files for output."
