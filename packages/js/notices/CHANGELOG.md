<!-- Learn how to maintain this file at https://github.com/WordPress/gutenberg/tree/trunk/packages#maintaining-changelogs. -->

# Unreleased

-   Update dependency `@wordpress/a11y` to ^3.5.0

# 4.0.1

-   Update all js packages with minor/patch version changes. #8392

# 4.0.0

## Breaking changes

-   Update dependencies to support react 17. #8305
-   Drop support for IE11. #8305

## 3.1.0

-   Fix commonjs module build, allow package to be built in isolation. #7286

## 3.0.0 (2021-06-03)

## Breaking changes

-   Move Lodash to a peer dependency.

## 2.0.0 (2020-02-10)

### Breaking Change

-   A notices message is no longer spoken as a result of notice creation, but rather by its display in the interface by its corresponding [`Notice` component](https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/notice).

## 1.5.0 (2019-06-12)

### New Features

-   Support a new `snackbar` notice type in the `createNotice` action.

## 1.1.2 (2019-01-03)

## 1.1.1 (2018-12-12)

## 1.1.0 (2018-11-20)

### New Feature

-   New option `speak` enables control as to whether the notice content is announced to screen readers (defaults to `true`)

### Bug Fixes

-   While `createNotice` only explicitly supported content of type `string`, it was not previously enforced. This has been corrected.

## 1.0.5 (2018-11-15)

## 1.0.4 (2018-11-09)

## 1.0.3 (2018-11-09)

## 1.0.2 (2018-11-03)

## 1.0.1 (2018-10-30)

## 1.0.0 (2018-10-29)

-   Initial release.
