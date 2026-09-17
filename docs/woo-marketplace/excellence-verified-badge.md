---
sidebar_position: 7
sidebar_label: 'Excellence Verified badge'
---

# Earning the Excellence Verified badge on the Woo Marketplace

The Woo Marketplace is a trusted source for merchants searching for high-quality extensions to add to their stores. That trust is what brings merchants in ready to buy, and it's what makes the Marketplace worth selling on. The Excellence Verified badge builds on it. It marks the products that reach the highest tier, checked for security, compatibility, maintenance, and merchant ratings.

This guide explains what the badge offers, how a product earns it, where vendors can check their current status, and how to close the gap.

## What the badge offers

Products that earn the Excellence Verified badge stand out across the Marketplace. The badge:

- Appears on the product page and on product cards in listings.
- Raises visibility in Discover, including a dedicated area for badged products.
- Includes the product when merchants filter to badged products only.

More benefits are on the way as the program grows, including promotional placements for badged products across more areas.

Products that don't earn the badge aren't penalized in any way. They keep their existing visibility and placement. The badge adds recognition for the products that reach the top tier.

## How to earn a badge

The badge is built on four factors, and a product needs to do well on all of them:

- **Secure**: scanned for vulnerabilities and malicious code.
- **Compatible**: installs and runs cleanly on current WordPress and WooCommerce.
- **Maintained**: kept up to date with recent releases.
- **Merchant-approved**: rated 4.0+ by real merchants.

The first three are measured by the Quality Insights Toolkit (QIT). Here's what feeds each one:

- **Secure**: WPScan, code security analysis, secret-leak detection, and malware scans.
- **Compatible**: plugin metadata validation, the HPOS compatibility declaration, coding standards, and activation without fatal errors on current WordPress and WooCommerce versions.
- **Maintained**: up-to-date "tested up to" headers, dependency vulnerability checks, and a passing end-to-end test package.

For the full list of managed tests and what each one checks, see the [QIT documentation](https://qit.woo.com/docs/), specifically the Managed Tests and Test Packages sections.

The fourth factor, Merchant-approved, has two parts and a product needs both: a strong average customer rating, and enough reviews to reflect real use. This is the part no code change can fix. It rewards products that merchants trust over time.

The exact thresholds are calibrated at launch and will evolve as the program grows. The Vendor Dashboard shows each product's QIT checklist and current badge state, so it's the place to check technical progress: Vendor **Dashboard** > **Products**.

## How to improve a product's badge standing

Most products already meet several of the requirements, so there's no starting from scratch. Products that fall short are usually close, and a few targeted fixes get there.

**Start with validation**. It's the most common blocker and one of the easiest to fix. Validation checks plugin metadata: license headers and version requirements. These are small, well-defined changes that don't touch product logic.

**Declare HPOS compatibility honestly.** Validation also checks the HPOS compatibility declaration, and this one is not a metadata edit. If a product handles orders, declare compatibility only once the order code supports HPOS. The declaration tells merchants what the product does, so setting it to true without the support behind it hides the warning WooCommerce would otherwise show them. Test order flows with HPOS enabled first — QIT accepts `--optional_features=hpos` — then declare. The [HPOS recipe book](/docs/features/orders/high-performance-order-storage/recipe-book/) covers what to check.

**Add a test package.** The check confirms that a product's own end-to-end suite runs and passes. The value goes beyond the badge: a real suite catches regressions before they reach merchants and makes it safer to ship updates as WordPress and WooCommerce evolve. A suite that genuinely exercises the product is worth far more than a placeholder.

**Keep compatibility headers current**. The recent-work check looks for recent WordPress and WooCommerce versions in the "tested up to" headers. Update these with each release.

**Clear any security findings.** If WPScan, code security, or any malware scan flags something, treat it as a priority. These protect merchants directly.

On the satisfaction side, there's no shortcut. Strong support, clear documentation, and a product that does what it promises are what build a strong rating over time.

## Keeping the badge

The badge isn't a one-time award. A product holds it while it keeps meeting all four factors.

Status is re-checked automatically as new versions ship and as ratings change. If a product drops below the bar on any factor, the badge comes off until the product qualifies again. The Quality tab always shows what needs attention, so the path back is clear.

## Timeline and what to expect

The badge rolls out in stages, leaving time to prepare.

Vendors will first get visibility into their status through the dashboard. An adjustment window of at least two weeks will follow before the thresholds are locked, allowing time for metadata and test-package fixes. Pass rates will then be measured, and the final thresholds set before the badge goes live.

The checks are automated and run continuously, so launch day isn't the only chance to qualify. A product that misses the badge at launch can earn it at any point after: every new version is a fresh opportunity.

Exact dates and the steps for each stage will follow in vendor comms ahead of launch.

## Frequently asked questions

<details>
<summary>How many products will have the badge at launch?</summary>

Only a limited share of products will qualify at launch. Many are only a few targeted fixes away, so that number is expected to grow as vendors make improvements.

</details>

<details>
<summary>Does the badge cover themes and business services?</summary>

Not in the first phase. The Excellence Verified badge starts with extensions, and we'll evaluate expanding it to other product types as the program grows.

</details>

<details>
<summary>Is the badge a certification?</summary>

No. It's a quality signal based on measurable tests and real customer ratings, not a formal certification or endorsement.

</details>

<details>
<summary>What's the most effective first step?</summary>

Passing validation and adding a working test package. Those two account for most near-misses, and neither requires changing a product's core code.

</details>

<details>
<summary>Will the criteria change?</summary>

Yes. The program will continually refine its requirements over time based on vendor, merchant, and agency feedback, so the products with the highest quality are rewarded with the badge. Any changes will be communicated before they take effect.

</details>
