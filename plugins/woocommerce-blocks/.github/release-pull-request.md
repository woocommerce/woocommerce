This is the release pull request for WooCommerce Blocks plugin `{{version}}`.

## Changelog

---

```
{{changelog}}
```

---

## Communication

>  This section is for any notes related to communicating the release. Please include any extra details with each item as needed.


This release introduces:


> In this section document a summary of what's in the release. Keep this brief, and link to the changelog for more detail.

### Prepared Updates

The following documentation, blog posts, and changelog updates are prepared for the release:


> In this section you are highlighting all the public facing documentation that is related to the release. Feel free to remove anything that doesn't apply for this release.


**Release announcement:** *Link to release announcement post on developer.woocommerce.com (published after release)*

{{#if devNoteItems}}
**Developer Notes** - The following issues require developer notes in the release post:

{{devNoteItems}}
{{/if}}


**Relevant developer documentation:**
_Link(s) to any developer documentation related to the release_

**Happiness Engineering or Happiness / Support:**
_Link to any special instructions or important support notes for this release._


## Quality

> This section is for any notes related to quality around the release Please include any extra details with each item as needed. This can include notes about why something isn't checked or expanding info on your response to an item.

* [ ] Changes in this release are covered by Automated Tests.

      > This section is for confirming that the release changeset is covered by automated tests. If not, please leave some details on why not and any relevant information indicating confidence without those tests.

     * [ ] Unit tests
     * [ ] E2E tests
     * [ ] for each supported WordPress and WooCommerce core versions.

* This release has been tested on the following platforms:
     * [ ] mobile
     * [ ] desktop

* [ ] This release affects public facing REST APIs.
    * [ ] It conforms to REST API versioning policy.

* [ ] This release impacts **other extensions** or **backward compatibility**.
    * [ ] The release changes the signature of public methods or functions
        * [ ] This is documented (see: *Enter a link to the documentation here*)
    * [ ] The release affects filters or action hooks.
        * [ ] This is documented (see: *Enter a link to the documentation here*)

* [ ] Link to **testing instructions** for this release: *Enter a link to the testing instructions here, ideally in /docs/testing/releases*

* [ ] The release has a negative performance impact on sites.
    * [ ] There are new assets (JavaScript or CSS bundles)
    * [ ] There is an increase to the size of JavaScrip or CSS bundles) *please include rationale for this increase*
    * [ ] Other negative performance impacts (if yes, include list below)

* [ ] The release has positive performance impact on sites. If checked, please document these improvements here.

## Additional Notes

> This section is for additional notes related to the release.

------
