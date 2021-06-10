#!/bin/bash

FILENAME="woocommerce-trunk-nightly.zip"

cd ${GITHUB_WORKSPACE}/tests/e2e/env/deps

# First, make sure we are working with a valid zip file
if file $FILENAME | grep "Zip archive"; then
  echo "Valid zip found, continuing."
else
  echo "Invalid zip provided."
  exit 1
fi;

echo "Check whether or not the zip file contains another zip. If so, extract it."
unzip -l $FILENAME | grep -q woocommerce.zip;
if [ "$?" == "0" ]
then
    echo "Extracting nested zip"
    unzip -q -j $FILENAME && rm $FILENAME
else
	echo "Renaming $FILENAME to woocommerce.zip"
	mv $FILENAME woocommerce.zip
fi;
