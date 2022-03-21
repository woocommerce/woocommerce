#!/usr/bin/env bash

changelog_entry=$(sed -n '/^Significance/,/^==/p' changelogs/* | grep -w "#$PR_NUMBER")

if [ -z "$changelog_entry" ]
then
  echo "Error: No changelog entry was provided for #$PR_NUMBER"
  echo "label_action=add" >> $GITHUB_ENV
  echo "label=needs changelog entry" >> $GITHUB_ENV 
  exit 1
fi

echo "label_action=remove" >> $GITHUB_ENV
echo "label=needs changelog entry" >> $GITHUB_ENV 
