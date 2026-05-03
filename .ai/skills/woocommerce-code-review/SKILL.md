---
name: woocommerce-code-review
description: Review WooCommerce code changes for coding standards compliance. Use when reviewing code locally, performing automated PR reviews, or checking code quality.
---

# WooCommerce Code Review

Review code changes against WooCommerce coding standards and conventions.

## Critical Violations to Flag

### Backend PHP Code

Consult the `woocommerce-backend-dev` skill for detailed standards. Using these standards as guidance, flag these violations and other similar ones:

**Architecture & Structure:**

- ❌ **Standalone functions** - Must use class methods ([file-entities.md](../woocommerce-backend-dev/file-entities.md))
- ❌ **Using `new` for DI-managed classes** - Classes in `src/` must use `$container->get()` ([dependency-injection.md](../woocommerce-backend-dev/dependency-injection.md))
- ❌ **Classes outside `src/Internal/`** - Default location unless explicitly public ([file-entities.md](../woocommerce-backend-dev/file-entities.md))

**Naming & Conventions:**

- ❌ **camelCase naming** - Must use snake_case for methods/variables/hooks ([code-entities.md](../woocommerce-backend-dev/code-entities.md))
- ❌ **Yoda condition violations** - Must follow WordPress Coding Standards ([coding-conventions.md](../woocommerce-backend-dev/coding-conventions.md))

**Documentation:**

- ❌ **Missing `@since` annotations** - Required for public/protected methods and hooks ([code-entities.md](../woocommerce-backend-dev/code-entities.md))
- ❌ **Missing docblocks** - Required for all hooks and methods ([code-entities.md](../woocommerce-backend-dev/code-entities.md))
- ❌ **Verbose docblocks** - Keep concise, one line is ideal ([code-entities.md](../woocommerce-backend-dev/code-entities.md))

**Data Integrity:**

- ❌ **Missing validation** - Must verify state before deletion/modification ([data-integrity.md](../woocommerce-backend-dev/data-integrity.md))

**Testing:**

- ❌ **Using `$instance` in tests** - Must use `$sut` variable name ([unit-tests.md](../woocommerce-backend-dev/unit-tests.md))
- ❌ **Missing `@testdox`** - Required in test method docblocks ([unit-tests.md](../woocommerce-backend-dev/unit-tests.md))
- ❌ **Test file naming** - Must follow convention for `includes/` vs `src/` ([unit-tests.md](../woocommerce-backend-dev/unit-tests.md))

### UI Text & Copy

Consult the `woocommerce-copy-guidelines` skill. Flag:

- ❌ **Title Case in UI** - Must use sentence case ([sentence-case.md](../woocommerce-copy-guidelines/sentence-case.md))
    - Wrong: "Save Changes", "Order Details", "Payment Options"
    - Correct: "Save changes", "Order details", "Payment options"
    - Exceptions: Proper nouns (WooPayments), acronyms (API), brand names

## Review Approach

1. **Scan for critical violations** listed above
2. **Cite specific skill files** when flagging issues
3. **Provide correct examples** from the skill documentation
4. **Group related issues** for clarity
5. **Be constructive** - explain why the standard exists when relevant

## Output Format

For each violation found:

```text
❌ [Issue Type]: [Specific problem]
Location: [File path and line number]
Standard: [Link to relevant skill file]
Fix: [Brief explanation or example]
```

## Notes

- All detailed standards are in the `woocommerce-backend-dev`, `woocommerce-dev-cycle`, and `woocommerce-copy-guidelines` skills
- Consult those skills for complete context and examples
- When in doubt, refer to the specific skill documentation linked above
