#!/usr/bin/env bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
STORYBOOK_WORDPRESS_DIR="$DIR/../storybook/wordpress";
STORY_BOOK_CSS_PATH="$DIR/../storybook/wordpress/css";
TMP_DIR="$DIR/../storybook/wordpress/tmp";
ARCHIVE_CSS_PATH="wordpress/wp-admin/css";
ARCHIVE_IMG_PATH="wordpress/wp-admin/images";

mkdir -p "$STORY_BOOK_CSS_PATH";
mkdir -p "$TMP_DIR";

function download_and_extract_css {
    curl -o "$STORYBOOK_WORDPRESS_DIR/wordpress-latest.zip" https://wordpress.org/nightly-builds/wordpress-latest.zip;
    unzip -qq "$STORYBOOK_WORDPRESS_DIR/wordpress-latest.zip" "$ARCHIVE_CSS_PATH/*" "$ARCHIVE_IMG_PATH/*" -d "$TMP_DIR";
    rsync -a "$TMP_DIR/$ARCHIVE_CSS_PATH" "$STORYBOOK_WORDPRESS_DIR";
    rsync -a "$TMP_DIR/$ARCHIVE_IMG_PATH" "$STORYBOOK_WORDPRESS_DIR";
    rm -r "$TMP_DIR";
}

if [ -z "$(find "$STORY_BOOK_CSS_PATH" -iname '*.css')" ] || [ "$1" == "-f" ]
then
    # The directory is not empty, import css
    download_and_extract_css;
else
    echo "Wordpress CSS already imported, pass -f to force an update";
fi
