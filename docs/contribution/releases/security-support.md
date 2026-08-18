---
post_title: WooCommerce Security Patch Support Policy
sidebar_label: Security Support
sidebar_position: 10
---

# Security Patch Support Policy

When a security fix warrants it (CVSS score >=9), WooCommerce will provide security patches for the **last 21 major versions**. If the current stable WooCommerce version is 11.0, that means version **9.0 and newer**. Otherwise, we publish fixes for the latest version.

"Major version" follows WooCommerce's release numbering (`10.8`, `10.9`, `11.0`, ...), not semantic versioning. At the current release cadence, 21 major versions correspond to roughly two years of releases.

## What this means

- When the impact of a security issue warrants it (CVSS score >=9) - security fixes are backported to every supported major version affected by the vulnerability (within the established support window), and ship as [point releases](/docs/contribution/releases/point-releases).
- Versions older than the support window do not receive security patches. Stores on unsupported versions must update to a supported version to receive fixes.
- The window is a rolling count: each new major release moves the floor up by one version.

## Exceptions

For some classes of critical vulnerabilities, we may as a courtesy, backport outside the established policy.

## Reporting

Security vulnerabilities must be reported privately through Automattic's HackerOne program: [https://hackerone.com/automattic/](https://hackerone.com/automattic/). Never report them in public issues.

## Keeping this page current

The release run-book's publish steps include moving the supported-version floor forward when the stable release of a new major version ships.
