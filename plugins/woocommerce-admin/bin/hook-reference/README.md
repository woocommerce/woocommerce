# Hook Reference Generator

Compile a publishable JSON object of WooCommerce's JavaScript filters and slotFill entry points.

## Usage

Generate a new reference found at `bin/hook-reference/data.json` by running the following command.

```
pnpm run create-hook-reference
```

The data includes references to code in the Github repository by commit hash, so it is essential to commit the resulting data in a pull request to `main` so code references are publicly available.

## DocBlock Requirements

JavaScript documentation blocks require certain fields in order to be included in the reference.

### Filter

| Tag       | Description                                        |
| --------- | -------------------------------------------------- |
| `@filter` | Filter string used as `addFilter`'s first argument |

### SlotFill

| Tag         | Description               |
| ----------- | ------------------------- |
| `@slotFill` | The fill component's name |
