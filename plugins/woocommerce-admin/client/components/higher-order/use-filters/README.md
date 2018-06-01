useFilters
==========

`useFilters` is a fork of [gutenberg's `withFilters`](https://github.com/WordPress/gutenberg/tree/master/components/higher-order/with-filters). It is also a React [higher-order component](https://facebook.github.io/react/docs/higher-order-components.html).

Wrapping a component with `useFilters` provides a filtering capability controlled externally by the list of `hookName`s.

## Usage

```jsx
import { applyFilters } from '@wordpress/hooks';
import { useFilters } from 'components/higher-order/use-filters';

function MyCustomElement() {
	return <h3>{ applyFilters( 'woocommerce.componentTitle', 'Title Text' ) }</h3>;
}

export default useFilters( [ 'woocommerce.componentTitle' ] )( MyCustomElement );
```

`useFilters` expects an array argument which provides a list of hook names. It returns a function which can then be used in composing your component. The list of hook names are used in your component with `applyFilters`. Any filters added to the given hooks are run when added, and update your content (the title text, in this example).

### Adding filters

```js
function editText( string ) {
	return `Filtered: ${ string }`;
}
addFilter( 'woocommerce.componentTitle', 'editText', editText );
```

If we added this filter, our `MyCustomElement` component would display:

```html
<h3>Filtered: Title Text</h3>
```
