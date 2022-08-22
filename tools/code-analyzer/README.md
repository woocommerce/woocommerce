# Code Analyzer

## Description

`code-analyzer` is a CLI tool designed to analyze change information about plugins in the WooCommerce monorepo.

## Commands

Currently there are just 2 commands:

1. `analyzer`. Analyzer serves 2 roles currently, as a linter for PRs to check if introduced hook/template/db changes have associated changelog entries and also to provide file output of changes between
WooCommerce versions for the purpose of automating release processes (such as generating release posts.)

Here is an example `analyzer` command:

`./bin/dev analyzer release/6.8 "6.8.0" -b=release/6.7`

In this command we compare the `release/6.7` and `release/6.8` branches to find differences, and we're looking for changes introduced since `6.8.0` (using the `@since` tag).

To find out more about the other arguments to the command you can run `./bin/dev analyzer --help`

2. `major_minor`. This simple CLI tool gives you the latest `.0` major/minor released version of a plugin's mainfile based on Woo release conventions. 

Here is an example `major_minor` command:

`./bin/dev major_minor release/6.8 "plugins/woocommerce/woocommerce.php"`

In this command we checkout the branch `release/6.8` and check the version of the woocommerce.php mainfile located at the path passed. Note that at the time of
writing the main file in this particular branch reports `6.8.1` so the output of this command is `6.8.0`.

This command is particularly useful combined with the analyzer, allowing you to determine the last major/minor.0 version of a branch or ref before passing that as the
version argument to `analyzer`.
