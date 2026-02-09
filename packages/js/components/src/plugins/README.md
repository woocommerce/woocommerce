# Plugins

Use `Plugins` to install and activate a list of plugins.

## Usage

```jsx
<Plugins
	onComplete={ this.complete }
	pluginSlugs={ [ 'jetpack', 'woocommerce-services' ] }
/>
```

### Props

| Name          | Type     | Default                                  | Description                                                               |
| ------------- | -------- | ---------------------------------------- | ------------------------------------------------------------------------- |
| `onComplete`  | Function |                                          | Called when the plugin installer is completed                             |
| `onError`     | Function |                                          | Called when the plugin installer completes with an error                  |
| `onSkip`      | Function | `noop`                                   | Called when the plugin installer is skipped                               |
| `skipText`    | String   |                                          | Text used for the skip installer button                                   |
| `autoInstall` | Boolean  | false                                    | If installation should happen automatically, or require user confirmation |
| `pluginSlugs` | Array    | `[ 'jetpack', 'woocommerce-services' ],` | An array of plugin slugs to install.                                      |
