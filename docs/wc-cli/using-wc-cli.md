---
post_title: How to Use WooCommerce CLI
menu_title: Using WooCommerce CLI
tags: how-to
---

## Introduction

This guide aims to assist beginners in using WooCommerce CLI (WC-CLI) for managing WooCommerce stores via the command line.

## Getting Started

- Ensure WP-CLI is installed and WooCommerce is at least version 3.0.0.
- To check WC-CLI availability:

```bash
wp wc --info
```

### General Command Structure

The general syntax for WC-CLI commands is:

```bash
wp wc [command] [options]
```

For detailed help on any specific command, use:

```bash
wp wc [command] --help
```

## Basic Tasks

### 1. Listing Products

To list all products in your WooCommerce store:

```bash
wp wc product list
```

### 2. Creating a New Product

To create a new product:

```bash
wp wc product create --name="New Product" --type="simple" --regular_price="19.99"
```

### 3. Updating a Product

To update an existing product (e.g., product ID 123):

```bash
wp wc product update 123 --regular_price="24.99"
```

### 4. Deleting a Product

To delete a product (e.g., product ID 123):

```bash
wp wc product delete 123 --force
```

For a complete list of WC-CLI commands, check out our [WC-CLI commands](./wc-cli-commands.md) documentation
