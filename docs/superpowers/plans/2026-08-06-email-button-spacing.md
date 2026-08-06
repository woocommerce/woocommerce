# Email Button Spacing Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the baseline space below single-row block email buttons without changing button alignment or overflowing-row behavior.

**Architecture:** Keep the single-row button table in the existing inline formatting context so its parent `text-align` continues to control horizontal alignment. Add the same explicit top alignment already used by the renderer's wrapping path, and lock the generated markup with an integration test.

**Tech Stack:** PHP 7.4+, WordPress block rendering, PHPUnit integration tests, WordPress Coding Standards, PHPStan, Jetpack Changelogger.

## Global constraints

- Preserve auto-width groups; left, center, and right alignment; explicit widths and gaps; RTL behavior; overflow wrapping; and Outlook conditional markup.
- Do not change PHP signatures, hooks, filters, stored data, width computation, or the wrapping layout.
- Follow WordPress Coding Standards, including snake_case test method names and `@testdox` descriptions for new tests.
- Use red-green TDD and verify the new assertion fails for the missing vertical alignment before changing production code.

---

### Task 1: Top-align the single-row button wrapper

**Files:**

- Modify: `packages/php/email-editor/tests/integration/Engine/Renderer/ContentRenderer/Layout/Flex_Layout_Renderer_Test.php`
- Modify: `packages/php/email-editor/src/Engine/Renderer/ContentRenderer/Layout/class-flex-layout-renderer.php:63`

**Interfaces:**

- Consumes: `Flex_Layout_Renderer::render_inner_blocks_in_layout( array $parsed_block, Rendering_Context $rendering_context ): string`.
- Produces: Single-row wrapper markup whose table style is `display:inline-block;vertical-align:top`.

- [ ] **Step 1: Write the failing integration test**

Add this method after `testItRendersInnerBlocks()`:

```php
/**
 * Test the single-row wrapper's vertical alignment.
 *
 * @testdox Should top-align the single-row wrapper to avoid baseline spacing below its content.
 */
public function test_it_top_aligns_the_single_row_wrapper(): void {
	$parsed_block = array(
		'innerBlocks' => array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
			),
		),
		'email_attrs' => array(),
	);

	$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );

	$this->assertStringContainsString(
		'style="display:inline-block;vertical-align:top"',
		$output,
		'The inline wrapper table should opt out of baseline alignment.'
	);
}
```

- [ ] **Step 2: Run the new test and verify RED**

Run:

```bash
pnpm --filter=@woocommerce/email-editor-config test:integration -- --filter test_it_top_aligns_the_single_row_wrapper
```

Expected: FAIL because the generated table contains `style="display:inline-block"` without `vertical-align:top`.

- [ ] **Step 3: Add the minimal production style**

Change the single-row wrapper opening markup to:

```php
<div style="%1$s"><table class="layout-flex-wrapper" style="display:inline-block;vertical-align:top"><tbody><tr>',
```

Do not change `render_wrapping_layout()`.

- [ ] **Step 4: Run the targeted test and verify GREEN**

Run:

```bash
pnpm --filter=@woocommerce/email-editor-config test:integration -- --filter test_it_top_aligns_the_single_row_wrapper
```

Expected: PASS.

- [ ] **Step 5: Run the complete flex renderer integration test class**

Run:

```bash
pnpm --filter=@woocommerce/email-editor-config test:integration -- --filter Flex_Layout_Renderer_Test
```

Expected: PASS, including the existing wrapping, width, alignment, and RTL tests.

### Task 2: Add the package changelog entry

**Files:**

- Create: `packages/php/email-editor/changelog/fix-button-wrapper-spacing`

**Interfaces:**

- Consumes: Jetpack Changelogger's `Significance` and `Type` fields.
- Produces: A patch-level `fix` entry for the PHP email editor package.

- [ ] **Step 1: Create the changelog entry**

Add exactly:

```text
Significance: patch
Type: fix

Prevent inline button wrapper tables from leaving extra baseline space below buttons in rendered block emails.
```

- [ ] **Step 2: Validate the changelog entry**

Run from `packages/php/email-editor`:

```bash
composer exec -- changelogger validate
```

Expected: the new changelog file is valid.

### Task 3: Verify code quality and the complete change

**Files:**

- Verify: `packages/php/email-editor/src/Engine/Renderer/ContentRenderer/Layout/class-flex-layout-renderer.php`
- Verify: `packages/php/email-editor/tests/integration/Engine/Renderer/ContentRenderer/Layout/Flex_Layout_Renderer_Test.php`
- Verify: `packages/php/email-editor/changelog/fix-button-wrapper-spacing`

**Interfaces:**

- Consumes: The finished source, test, and changelog changes from Tasks 1 and 2.
- Produces: A clean, verified branch diff ready for review.

- [ ] **Step 1: Run PHPCS for the PHP package**

Run:

```bash
pnpm --filter=@woocommerce/email-editor-config lint:lang:php
```

Expected: PASS with no errors or warnings.

- [ ] **Step 2: Run PHPStan for supported PHP versions**

Run:

```bash
pnpm --filter=@woocommerce/email-editor-config phpstan:php8
pnpm --filter=@woocommerce/email-editor-config phpstan:php7
```

Expected: both commands pass without adding baseline entries.

- [ ] **Step 3: Re-run the focused integration test class**

Run:

```bash
pnpm --filter=@woocommerce/email-editor-config test:integration -- --filter Flex_Layout_Renderer_Test
```

Expected: PASS with no warnings.

- [ ] **Step 4: Inspect the branch diff**

Run:

```bash
git diff --check origin/trunk...
git diff --stat origin/trunk...
git status --short
```

Expected: no whitespace errors; only the approved design and plan documents, renderer, integration test, and changelog entry are changed.
