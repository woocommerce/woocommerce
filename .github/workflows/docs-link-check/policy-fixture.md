# Docs link-check policy fixture

This file is input for `.github/workflows/scripts/docs-link-check-policy-test.sh`. It lists URLs that the pull-request link check must request and URLs it must never request. The expected result is `policy-expected.txt`.

## Approved hosts (must be requested)

- [exact host](https://github.com/woocommerce/woocommerce)
- [plain http](http://wordpress.org/plugins/woocommerce/)
- [uppercase host is normalized](https://GITHUB.COM/Woo/Repo#frag)
- [approved docs host](https://developer.woocommerce.com/docs/)
- [query string kept](https://github.com/x?y=1)
- autolink: <https://github.com/auto/link>
- [raw file host](https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/README.md)
- [blob link with a fragment](https://github.com/woocommerce/woocommerce/blob/trunk/README.md#readme) is requested from raw.githubusercontent.com at check time (see `pr.toml`); `--dump` shows the original URL

## Lookalikes and out-of-policy hosts (must not be requested)

- [user info before an approved host](https://github.com@evil.example/x)
- [credentials](https://user:pw@github.com/x)
- [non-default port](https://github.com:8443/x)
- [suffix lookalike](https://github.com.evil.example/x)
- [approved host in the path](https://evil.example/github.com/)
- [www subdomain is not approved](https://www.woocommerce.com/)
- [other subdomain is not approved](https://foo.wordpress.org/)
- [trailing dot host](https://github.com./x)
- [homoglyph host](https://gıthub.com/x)
- [npm is not approved](https://www.npmjs.com/package/x)
- [example domain](https://example.com/)

## Private, loopback, and link-local (must not be requested)

- [loopback](http://127.0.0.1/)
- [localhost with port](http://localhost:8080/)
- [link-local metadata](http://169.254.169.254/latest/meta-data/)
- [private range](http://10.0.0.1/)
- [ipv6 loopback](http://[::1]/)
- [hex loopback](http://0x7f000001/)
- [decimal loopback](http://2130706433/)
- [placeholder .local host](http://store.local/)
- [placeholder .test host](http://local.wordpress.test/)

## Other schemes and non-network links (must not be requested)

- [mail](mailto:docs@example.com)
- [relative](../README.md)
- [root-relative](/docs/apis/rest-api/)
- [fragment](#approved-hosts-must-be-requested)
- [ftp](ftp://github.com/x)

```md
[inside a code fence](https://github.com/should/not/appear)
```
