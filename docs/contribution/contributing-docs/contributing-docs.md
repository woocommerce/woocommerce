---
post_title: Contributing technical documentation
sidebar_label: Contributing docs
---

# Contributing technical documentation

Thanks for helping improve WooCommerce's developer documentation. Our docs are powered by Docusaurus, and live inside the [`woocommerce/docs/`](https://github.com/woocommerce/woocommerce/tree/trunk/docs) folder of the monorepo.

This guide walks you through the structure, tooling, and process for contributing effectively.

## Getting started 

> This guide presumes that you're familiar with basic Git and GitHub functionality, that you're signed into a GitHub account, and that you have Git setup locally. If you're new to GitHub, we recommend reading their [quickstart](https://docs.github.com/en/get-started/quickstart/hello-world) and [working with forks](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/fork-a-repo) guides before getting started.

### Initial setup

1. Fork the [WooCommerce monorepo](https://github.com/woocommerce/woocommerce) on GitHub. If asked, you can safely check the `copy the trunk branch only` option.
2. [Clone the fork that you just created](https://docs.github.com/en/repositories/creating-and-managing-repositories/cloning-a-repository). This will allow you to edit it locally.

### Making changes

1. Prior to making any changes, ensure your `trunk` branch is up to date with the monorepo's `trunk` [by syncing it](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/syncing-a-fork).
2. For each modification you'd like to make, create a new branch off `trunk` in your fork that starts with `docs/`. For example, if you're adding a doc about improving extension performance, you could call your branch `docs/improve-extension-performance`.
3. Create or edit markdown files inside the appropriate folder under `docs/`.
4. If needed, update the folder's `_category_.json` (for sidebar label/position).
5. Run a build to verify changes, confirm that the sitemaps and llms-txt files are updated, and detect broken links (link checking only happens on build):

    ```bash
    npm run build
    ```

### Opening a pull request

1. Commit and push your changes to your fork.
2. [Open a pull request](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request) to woocommerce/woocommerce, targeting the trunk branch.
3. Use a descriptive title, and fill out the PR template. Include:
    * Rationale for new files or categories
    * A note about any sidebar or structure changes
4. The WooCommerce Developer Advocacy team will review and merge your changes.

## Docs folder anatomy

### Tooling and configuration

* **Supporting tooling and config** lives in:
    * [`woocommerce/docs/_docu-tools/`](https://github.com/woocommerce/woocommerce/blob/trunk/docs/_docu-tools/)
* **Top-level sidebar and navbar** are configured in:
    * [`sidebars.ts`](https://github.com/woocommerce/woocommerce/blob/trunk/docs/_docu-tools/sidebars.ts)
    * [`docusaurus.config.ts`](https://github.com/woocommerce/woocommerce/blob/trunk/docs/_docu-tools/docusaurus.config.ts)

### Documentation files

* **Docs location**: All documentation lives inside of [`woocommerce/docs/`](https://github.com/woocommerce/woocommerce/blob/trunk/docs/)
* **Each folder is sidebar or top nav category**, for example: `getting-started`, `code-snippets`, etc.
* **Sidebar configuration** for each category is managed using a `_category_.json` file inside each category folder:

    ```json
    {
        "position": 7,
        "label": "Code snippets"
    }
    ```

## Adding images

All documentation images are stored in:

```bash
docs/_docu-tools/static/img/doc_images/
```

To include an image in a markdown file, reference it like this:

```markdown
![Alt text](/img/doc_images/your-image-name.png)
```

## Creating New Categories

Before creating a new category, you should consider whether it is truly necessary to do so. Where possible, you should default to creating content in an existing category. If you do need to create a new category, follow these steps:

1. Inside the `/docs` folder, create a sub-folder with a descriptive name. For example, if you wanted to create a `Checkout design guidelines` section, you'd create a folder called `/docs/checkout-design-guidelines`.

2. Create a `_category_.json` file inside each category folder and give it a position it should have in the sidebar as well as a label:

    ```json
    {
        "position": 10,
        "label": "Checkout design guidelines"
    }
    ```

When creating new categories, please include rationale about why the category was created in your pull request's description.

## Writing guidelines and references

* Use short, URL-friendly file names (kebab-case, no spaces)
* Avoid pasting from rich text editors like Google Docs, which may introduce invalid characters
* Check our [docs style guide](style-guide) for detailed writing guidelines
* Reference the [Docusaurus documentation](https://docusaurus.io/docs) for additional guidance

