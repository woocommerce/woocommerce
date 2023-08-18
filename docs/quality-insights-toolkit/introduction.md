# Introduction

The Quality Insights Toolkit (QIT) is an initiative by WooCommerce designed to provide extension developers in the [Woo Marketplace](https://woocommerce.com/products/) with managed automated tests.

To ensure that all extensions in the Woo Marketplace meet our quality standards, we run a series of automated tests. As part of our commitment to supporting developers, we also provide ways for developers to easily integrate these tests into their development workflows.

## Quick Start Guide

1. `composer install woocommerce/qit-cli`
2. `./vendor/bin/qit` to authenticate with your WooCommerce.com developer account.

## Benefits for You as a Developer

- You can enjoy out-of-the-box tests for all the extensions you sell on the Marketplace
- Your extensions will undergo continuous testing, including against new releases of PHP, WooCommerce, and WordPress
- You can have increased peace of mind knowing that you'll be alerted if the tests catch anything with a future release or an edge case
- You can take advantage of integration with GitHub Workflows
- You can easily integrate the testing tool with your development workflow using the terminal tool
- You can run some tests with other extensions active at the same time, including those that may be known to cause issues when activated alongside yours.

## Benefits for Users of the WooCommerce Ecosystem

- Users can enjoy increased reliability when updating WooCommerce extensions on the Marketplace
- Users can have confidence to update PHP, WordPress, or WooCommerce versions without fear of breaking their extensions
- There will be less potential security issues for users of the WooCommerce Ecosystem
- Users can have more assurance that all extensions will continue to work as expected when activated alongside each other, ensuring a seamless experience.

## Who is this toolkit for?

This toolkit is for extension developers who are selling their extensions on the WooCommerce Marketplace.

## What types of tests are available?

- End-to-end
- API
- Activation
- Security
- PHPStan

## How can I use the toolkit?

Tests are executed automatically by us whenever you publish a new version of your extension on the WooCommerce Marketplace. You can also run tests manually using the following tools:

- [Dashboard](qit-dashboard/getting-started.md): A UI-based test runner and test results viewer, available in your WooCommerce dashboard.
- [CLI](qit-cli/getting-started.md): A CLI tool that allows you to run and view tests, including against development builds.
- [GitHub Workflows](github-workflows.md): GitHub workflow files that allow running tests regularly with QIT as part of a GitHub development workflow.

## Why does this toolkit exist?

The primary goal of these tools is to assist extension developers to easily integrate a variety of tests into their development workflows, and promote and encourage quality around WooCommerce extensions available in the marketplace.
