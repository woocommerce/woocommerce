# How to release Javascript packages

When a packages contains sufficient changes to justify a release to [NPM](https://www.npmjs.com/), follow these instructions to create a new release from the monorepo.

## Prepare packages for release

In order to prepare a package for release, version numbers require a bump to signify a new version as well as compilation of the changelog. The monorepo [Actions Tab contains a Prepare Package Release workflow](https://github.com/woocommerce/woocommerce/actions/workflows/prepare-package-release.yml).

![image](https://user-images.githubusercontent.com/1922453/179434311-1adc8df5-883b-4e98-8a3e-6d4cca778354.png)
