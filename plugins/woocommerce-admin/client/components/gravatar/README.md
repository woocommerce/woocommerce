Gravatar
============

Use `Gravatar` to display a user's Gravatar.

## How to use:

```jsx
import { Gravatar } from 'components/gravatar';

render: function() {
  return (
    <Gravatar
		user="email@example.org"
		size={ 48 }
	/>
  );
}
```

## Props

* `user`: The address to hash for displaying a Gravatar. Can be an email address or WP-API user object.
* `size`: Default 60. The size of Gravatar to request.
* `alt`: Text to display as the image alt attribute.
* `title`: Text to use for the image's title
* `fallback`: Default `mp`. Gravatar default fallback mode. See https://en.gravatar.com/site/implement/images/.
* `className`: Additional CSS classes.