This is the release pull request for {version} of the WooCommerce Blocks plugin.

## Communication

<!--
  This section is for any notes related to communicating the release.
  Please include any extra details with each item as needed.
-->

This release introduces:

<!--
In this section document an overview/summary of what this release includes. You can refer to
the changelog for more information
-->

### Prepared Updates

The following documentation, blog posts, and changelog updates are prepared for the release:

<!--
In this section you are highlighting all the public facing documentation that is related to the
release. Feel free to remove anything that doesn't apply for this release.
-->

**Release announcement:** <!-- Link to release announcement post on developer.woocommerce.com (published after release) -->

**Developer Notes** - The following issues require developer notes in the release post:

<!--
Issues or pulls needing a developer note are labelled with `status: needs-dev-note`. Review
those and list here as checklist items. You can have different engineers write the notes
(usually the engineer that did the changes) if needed, but they should be summarized and included in the release post.
-->


**Relevant developer documentation:** <!-- Link(s) to any developer documentation related to the release -->

**Happiness Engineer:** <!-- Link to any special instructions or helpful notes for HE related to this release -->

* [ ] The release includes a changelog entry in the readme.txt?


## Quality

<!--
  This section is for any notes related to quality around the release.
  Please include any extra details with each item as needed. This can include notes about
  Why something isn't checked or expanding info on your response to an item.
-->

* [ ] Changes in this release are covered by Automated Tests.
     <!--
      This section is for confirming that the release changeset is covered by automated tests. If not,
      please leave some details on why not and any relevant information indicating confidence without
      those tests.
      -->
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
        * [ ] This is documented (see: <!-- Enter a link to the documentation here -->)
    * [ ] The release affects filters or action hooks.
        * [ ] This is documented (see: <!-- Enter a link to the documentation here -->)

* [ ] Link to **testing instructions** for this release: <!-- Enter a link to the testing instructions here -->

* [ ] The release has a negative performance impact on sites.
    * [ ] There are new assets (JavaScript or CSS bundles)
    * [ ] There is an increase to the size of JavaScrip or CSS bundles) <!-- please include rationale for this increase -->
    * [ ] Other negative performance impacts (if yes, include list below)

* [ ] The release has positive performance impact on sites. If checked, please document these improvements here.

## Additional Notes

<!--
  This section is for additional notes related to the release.
-->
