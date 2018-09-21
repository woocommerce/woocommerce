ActivityOutboundLink
============

`ActivityOutboundLink` creates a styled link for use in the Activity Panel.

## How to use:

```jsx
import ActivityOutboundLink from 'layout/activity-panel/activity-outbound-link';

render: function() {
  return (
	<ActivityOutboundLink
		href="edit.php?post_type=shop_coupon"
		type="wp-admin"
	>
		Coupons
	</ActivityOutboundLink>
  );
}
```

## Props

* `href` (required): The resource to link to.
* `type` (required): Type of link. For wp-admin and wc-admin, the correct prefix is appended. Accepts: wc-admin, wp-admin, external.