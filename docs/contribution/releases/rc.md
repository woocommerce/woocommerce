---
post_title: WooCommerce Release Candidates
sidebar_label: Release Candidates
sidebar_position: 5
---

# WooCommerce Release Candidates

Release candidates are pre-release WooCommerce versions made available for testing by plugin authors and users.
They are versioned incrementally, starting with `-rc.1`, then `-rc.2`, and so on (released if any regressions have been
discovered or any crucial features need to make it into the final release).

The date of the very first release candidate is announced along with the final release date as part of the published release 
checklist and [release schedule](https://developer.woocommerce.com/release-calendar/).

> Note on timeline: The expected timeframe between the RC1 and a final release is three weeks.

> Note on RC1: RC1 can be released without additional consideration, as it aligns with the code freeze timing.

> Note on RC2: RC2 can be released two weeks after RC1 (and one week before the final release).

On the technical side of the release process, we rely on release branches (named e.g., `release/9.9`) for code freeze and
stabilization. In those branches, we tag release candidates, fix regressions (via CFEs), and tag final releases in isolation 
from ongoing development for greater release stability. 
