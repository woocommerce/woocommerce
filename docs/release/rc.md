Release candidates are pre-release version of WooCommerce that is made available for testing by plugin authors and users.
They are versioned incrementally, starting with `-rc.1`, then `-rc.2`, and so on (released if any regressions are found).

The date of very first release candidate is announced along with final release date as part of published release checklist 
and release schedule https://developer.woocommerce.com/release-calendar/.

> Note 1: The very first release candidate can be released after all [CFEs](https://github.com/woocommerce/woocommerce/issues?q=is%3Aissue%20state%3Aopen%20label%3A%22code%20freeze%20exception%22) 
> for the target release version are addressed and merged.

The expected timeframe between a release candidate and final release is one week dedicated to identifying and addressing 
any regressions. If during this period no regression has been found, release candidate can be released as final.

On the technical side of the release process, we rely on release branches (named as `release/*.*`) for code freeze and 
stabilization. In those branches we tag release candidates, fix regressions (via CFEs) and tag final releases in isolation from ongoing 
trunk changes for greater releases stability. 







