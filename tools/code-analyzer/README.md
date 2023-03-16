# Code Analyzer

## Description

`code-analyzer` is a CLI tool designed to analyze change information about plugins in the WooCommerce monorepo.

## Commands

Currently there are 3 commands:

1. `lint`. Analyzer is used as a linter for PRs to check if hook/template/db changes were introduced. It produces output either directly on CI or via setting output variables in GH actions.

Here is an example `analyzer` command, run from this directory:

`pnpm run analyzer -- lint "release/6.8" "6.8.0" -b release/6.7`

In this command we compare the `release/6.7` and `release/6.8` branches to find differences, and we're looking for changes introduced since `6.8.0` (using the `@since` tag).

To find out more about the other arguments to the command you can run `pnpm run analyzer -- --help`

2. `major-minor`. This simple CLI tool gives you the latest `.0` major/minor released version of a plugin's mainfile based on Woo release conventions.

Here is an example `major-minor` command, run from this directory:

`pnpm run analyzer major-minor -- "release/6.8" "plugins/woocommerce/woocommerce.php"`

In this command we checkout the branch `release/6.8` and check the version of the woocommerce.php mainfile located at the path passed. Note that at the time of
writing the main file in this particular branch reports `6.8.1` so the output of this command is `6.8.0`.

This command is particularly useful combined with the analyzer, allowing you to determine the last major/minor.0 version of a branch or ref before passing that as the
version argument to `analyzer`.

3. `scan`. Scan is like `lint` but lets you scan for a specific change type. e.g. you can scan just for hook changes if you wish.

Here is an example of the `scan` command run to look for hook changes:

`pnpm analyzer scan hooks "release/6.8" "release/6.7" --since "6.8.0"`
\
In this command we compare the `release/6.7` and `release/6.8` branches to find hook changes, and we're looking for changes introduced since `6.8.0` (using the `@since` tag).
