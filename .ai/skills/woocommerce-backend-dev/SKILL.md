---
name: woocommerce-backend-dev
description: Add or modify WooCommerce backend PHP code following project conventions. Use when creating new classes, methods, hooks, or modifying existing backend code. **MUST be invoked before writing any PHP unit tests.**
---

# WooCommerce Backend Development

This skill provides guidance for developing WooCommerce backend PHP code according to project standards and conventions.

## When to Use This Skill

**ALWAYS invoke this skill before:**

- Writing new PHP unit tests (`*Test.php` files)
- Creating new PHP classes
- Modifying existing backend PHP code
- Adding hooks or filters

## Instructions

Follow WooCommerce project conventions when adding or modifying backend PHP code:

1. **Creating new code structures**: See [file-entities.md](file-entities.md) for conventions on creating classes and organizing files (but for new unit test files see [unit-tests.md](unit-tests.md)).
2. **Naming conventions**: See [code-entities.md](code-entities.md) for naming methods, variables, and parameters
3. **Coding style**: See [coding-conventions.md](coding-conventions.md) for general coding standards and best practices
4. **Type annotations**: See [type-annotations.md](type-annotations.md) for PHPStan-aware PHPDoc annotations
5. **Working with hooks**: See [hooks.md](hooks.md) for hook callback conventions and documentation
6. **Dependency injection**: See [dependency-injection.md](dependency-injection.md) for DI container usage
7. **Data integrity**: See [data-integrity.md](data-integrity.md) for ensuring data integrity when performing CRUD operations
8. **Writing tests**: See [unit-tests.md](unit-tests.md) for unit testing conventions

## Key Principles

- Always follow WordPress Coding Standards
- Use class methods instead of standalone functions
- Place new internal classes in `src/Internal/` by default
- Use PSR-4 autoloading with `Automattic\WooCommerce` namespace
- Write comprehensive unit tests for new functionality
- Run linting and tests before committing changes

## Version Information

To determine the next WooCommerce version number for `@since` annotations:

- Read the `$version` property in `includes/class-woocommerce.php` **on the trunk branch**
- Remove the `-dev` suffix if present
- Example: If trunk shows `10.4.0-dev`, use `@since 10.4.0`
- Note: When reviewing PRs against trunk, the version in trunk is correct even if it seems "future" relative to released versions
