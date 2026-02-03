# External Integrations

**Analysis Date:** 2026-02-02

## APIs & External Services

**Payment Gateways:**

-   PayPal Standard - Built-in payment gateway
    -   SDK/Client: Native implementation in `includes/gateways/paypal/`
    -   Auth: PayPal API credentials (stored in settings)

**WordPress Services:**

-   Jetpack - Automattic services integration
    -   SDK/Client: automattic/jetpack-\* packages
    -   Auth: Jetpack connection tokens

**Analytics & Telemetry:**

-   Automattic MC Stats - Usage tracking
    -   SDK/Client: automattic/jetpack-a8c-mc-stats
    -   Auth: Automatic via Jetpack

## Data Storage

**Databases:**

-   MySQL/MariaDB
    -   Connection: WordPress DB constants (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD)
    -   Client: WordPress $wpdb global

**File Storage:**

-   Local filesystem only

**Caching:**

-   None (relies on WordPress caching mechanisms)

## Authentication & Identity

**Auth Provider:**

-   WordPress native authentication
    -   Implementation: WordPress user system with capabilities

## Monitoring & Observability

**Error Tracking:**

-   None

**Logs:**

-   WordPress debug logging (WP_DEBUG_LOG)
-   WooCommerce logging system (internal)

## CI/CD & Deployment

**Hosting:**

-   Self-hosted WordPress installations

**CI Pipeline:**

-   GitHub Actions
    -   .github/workflows/ci.yml - Main CI workflow
    -   Various specialized workflows for releases and automation

## Environment Configuration

**Required env vars:**

-   WordPress database credentials
-   WordPress salts and keys
-   WP_DEBUG settings

**Secrets location:**

-   WordPress wp-config.php
-   WooCommerce settings in database

## Webhooks & Callbacks

**Incoming:**

-   PayPal IPN/PDT handlers at `includes/gateways/paypal/includes/`
-   PayPal webhook handler for transaction updates

**Outgoing:**

-   Action Scheduler for background processing
-   WordPress cron for scheduled tasks

---

_Integration audit: 2026-02-02_
