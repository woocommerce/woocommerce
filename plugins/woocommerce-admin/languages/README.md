# Languages

## Contributing a Translation
If you'd like to contribute a translation, please follow the Localizing section in [CONTRIBUTING.md](https://github.com/woocommerce/woocommerce-admin/blob/main/CONTRIBUTING.md).

## Generating POT

The generated POT template file is not included in this repository. To create this file locally, follow instructions from [README.md](https://github.com/woocommerce/woocommerce-admin/blob/main/README.md) to install the project, then run the following command:

```
npm run i18n lang=xx_YY
```

After the build completes, you'll find a `woocommerce-admin-xx_YY.po` (eg. `woocommerce-admin-fr_FR.po`) strings file in this directory. 

## Generating JSON

To generate JSON from your translations, save your translation file in this directory then run the following command:

```
npm run i18n:json
```