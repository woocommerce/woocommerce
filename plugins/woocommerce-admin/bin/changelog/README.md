# Changelog Script

This folder contains the logic for a changelog script that can be used for generating changelog entries from either pull requests added to a Github milestone, or pull requests that are part of a Zenhub release.

## Usage:

By default, changelog entries will use the title of pull requests. However, you can also customize the changelog entry by adding to the description of the pull custom text in the following format.

```md
### Changelog

> Fix bug in Safari and other Webkit browsers.
```

You can implement the script in your `package.json` in the simplest form by adding the following to the `"scripts"` property (assuming it is installed in `./bin`):

```json
{
  "scripts": {
    "changelog": "node ./bin/changelog",
  }
}
```

## Configuration

The following configuration options can be set for the changelog script. **Note:** you can use all of these options but environment variables overwrite `package.json` config and command line arguments overwrite environment variables.

`package.json` configuration should be added on a top level `changelog` property.

The 'variable' in the following table can be used in `package.json` or as a cli arg.

| variable | description |
| ---- | ---- |
| labelPrefix | Any labels prefixed with this string will be used to derive the "type" of change (defaults to `type:`). |
| skipLabel |  Any pull having this label will be skipped for the changelog (defaults to `no-changelog`). |
| defaultPrefix |  When there is no label with the `labelPrefix` on a pull, this is the default type that will be used for the changelog entry (defaults to `dev`). |
| changelogSrcType |  Either "MILESTONE" (default) or "ZENHUB_RELEASE". This determines what will serve as the source for the changelog entries.
| devNoteLabel |  If a pull has this label then `[DN]` will be appended to the end of the changelog. It's a good way to indicate what entries have (or will have) dev notes.
| repo | This is the namespace for the github repository used as the source for pulls used in the changelog entries. Example: `'woocommerce/woocommerce-gutenberg-products-block'`
| ghApiToken |  You can pass your github api token to the script. NOTE: Strongly recommend you use environment variable for this. |
| zhApiKey | You can pass your zenhub api key to the script using this config. NOTE: Strongly recommend you use environment variable for this. |

The two environment variables you can use are:

| Environment Variable | Description |
| -------------------- | ----------  |
| GH_API_TOKEN | Github API token for authorizing on the github API. |
| ZH_API_TOKEN   | Zenhub api token used for authorizing against the zenhub API. |

### Examples:

**package.json**:

```json
{
  "changelog": {
    "labelPrefix": "type:",
    "skipLabel": "skip-changelog",
    "defaultPrefix": "dev",
    "repo": "woocommerce/woocommerce-gutenberg-products-block"
  },
}
```

**Environment Variable**
```bash
GH_API_TOKEN="1343ASDFQWER13241REASD" node ./bin/changelog
```

**Command Line**
```bash
node ./bin/changelog --labelPrefix="type:" --skipLabel="skip-changelog" --defaultPrefix="dev" --repo="woocommerce/woocommerce-gutenberg-products-block" --ghApiToken="1343ASDFQWER13241REASD"
```
