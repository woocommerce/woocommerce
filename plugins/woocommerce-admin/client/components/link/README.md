Link
============

Use `Link` to create a link to another resource. It accepts a type to automatically create wp-admin links, wc-admin links, and external links.

## How to use:

```jsx
import Link from 'components/link';

render: function() {
  return (
	<Link
		href="edit.php?post_type=shop_coupon"
		type="wp-admin"
	>
		Coupons
	</Link>
  );
}
```

## Props

* `href` (required): The resource to link to.
* `type` (required): Type of link. For wp-admin and wc-admin, the correct prefix is appended. Accepts: wc-admin, wp-admin, external.