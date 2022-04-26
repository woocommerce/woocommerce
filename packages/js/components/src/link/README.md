Link
===

Use `Link` to create a link to another resource. It accepts a type to automatically
create wp-admin links, wc-admin links, and external links.

## Usage

```jsx
<Link
	href="edit.php?post_type=shop_coupon"
	type="wp-admin"
>
	Coupons
</Link>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`href` | String | `null` | (required) The resource to link to
`type` | One of: 'wp-admin', 'wc-admin', 'external' | `'wc-admin'` | Type of link. For wp-admin and wc-admin, the correct prefix is appended
