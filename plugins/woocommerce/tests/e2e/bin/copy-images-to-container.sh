#!/bin/bash
set -e

echo "[setup] Starting test data setup..."

# This script requires QIT environment variables to be set
if [ -z "$QIT_PHP_CONTAINER" ] || [ -z "$QIT_SITE_URL" ]; then
    echo "[setup] Error: Required QIT environment variables are not set. This script must be run in a QIT environment."
    exit 1
fi

# Determine the script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(dirname "$SCRIPT_DIR")"

echo "[setup] Container: $QIT_PHP_CONTAINER"
echo "[setup] Site URL: $QIT_SITE_URL"

# Step 1: Copy images to container
echo "[setup] Copying images to container..."
if [ ! -d "$BASE_DIR/test-data/images" ]; then
    echo "[setup] Error: test-data/images directory not found"
    exit 1
fi

docker exec "$QIT_PHP_CONTAINER" mkdir -p /tmp/test-images
docker cp "$BASE_DIR/test-data/images/." "$QIT_PHP_CONTAINER:/tmp/test-images/"
echo "[setup] Images copied successfully"

# Step 2: Upload a single image to WordPress Media Library
echo "[setup] Uploading single image to WordPress Media Library..."

# Upload just one image and use it for all products (faster than uploading 30+ images)
SINGLE_IMAGE="image-01.png"
ATTACHMENT_ID=$(docker exec "$QIT_PHP_CONTAINER" wp media import "/tmp/test-images/$SINGLE_IMAGE" --allow-root --porcelain 2>&1)

# Check if the import was successful (should be a number)
if [[ ! $ATTACHMENT_ID =~ ^[0-9]+$ ]]; then
    echo "[setup] Error: Failed to upload image: $ATTACHMENT_ID"
    exit 1
fi

# Get the URL of the uploaded image
IMAGE_URL=$(docker exec "$QIT_PHP_CONTAINER" wp post get "$ATTACHMENT_ID" --field=guid --allow-root)
echo "[setup] Uploaded image URL: $IMAGE_URL"

# Step 3: Prepare CSV files with Media Library URLs
echo "[setup] Preparing CSV files with Media Library URLs..."

# Process both CSV templates
for template in sample_products sample_products_override; do
    if [ -f "$BASE_DIR/test-data/${template}.template.csv" ]; then
        echo "[setup] Processing ${template}.csv..."
        cp "$BASE_DIR/test-data/${template}.template.csv" "$BASE_DIR/test-data/${template}.csv"
        # Replace ALL image URLs with the single uploaded image URL
        php -r "
            \$content = file_get_contents('$BASE_DIR/test-data/${template}.csv');
            \$pattern = '|{{SITE_URL}}/test-data/images/[^,\"]*|';
            \$replacement = '${IMAGE_URL}';
            \$content = preg_replace(\$pattern, \$replacement, \$content);
            file_put_contents('$BASE_DIR/test-data/${template}.csv', \$content);
        "
    else
        echo "[setup] Warning: ${template}.template.csv not found, skipping"
    fi
done

# Clean up
docker exec "$QIT_PHP_CONTAINER" rm -rf /tmp/test-images

echo "[setup] Test data setup complete!"