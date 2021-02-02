# WooCommerce Admin Extension Examples

Examples for extending WooCommerce Admin

## Directions

Install dependencies, if you haven't already.

```bash
npm install
```

Build the example extension by running the npm script and passing the example name.

```bash
npm run example -- --ext=<example>
```

Go to your WordPress installation's plugins page and activate the plugin. WooCommerce Analytics reports will now reflect the changes made by the example extension.

You can make changes to Javascript and PHP files in the example and see changes reflected upon refresh.

## Example Extensions

- `add-report` - Create a "Hello World" report page.
- `add-task` - Create a custom task for the onboarding task list.
- `dashboard-section` - Adding a custom "section" to the new dashboard area.
- `table-column` - An example of how to add column(s) to any report.
- `sql-modification` - An example of how to modify SQL statements.
