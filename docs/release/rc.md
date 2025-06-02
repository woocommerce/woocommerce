Release candidates are pre-release WooCommerce versions made available for testing by plugin authors and users.
They are versioned incrementally, starting with `-rc.1`, then `-rc.2`, and so on (released if any regressions have been
discovered or any crucial features need to make it into the final release).

The date of the very first release candidate is announced along with the final release date as part of the published release 
checklist and [release schedule](https://developer.woocommerce.com/release-calendar/).

> Note on RC1: RC1 can be released without additional consideration, as it aligns with the code freeze timing.

> Note on RC2: RC2 can be released after all [CFEs](https://github.com/woocommerce/woocommerce/issues?q=is%3Aissue%20state%3Aopen%20label%3A%22code%20freeze%20exception%22) 
> for the target release version are addressed and merged, and any of those need testing in public.

> Note on RC3+: In general, RC3+ is not tagged until post-RC2 CFEs need testing in public, and fit into the timeframe between RC1 and final releases.

The expected timeframe between a release candidate and a final release is two weeks (to identify and address any regressions).
If, during this period, no regression has been found, we release the release candidate as final.

On the technical side of the release process, we rely on release branches (named e.g., `release/9.9`) for code freeze and
stabilization. In those branches, we tag release candidates, fix regressions (via CFEs), and tag final releases in isolation 
from ongoing development for greater release stability. 
