Feature: Manage WooCommerce Coupons

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc shop_coupon --help`
	Then STDOUT should contain:
		"""
		wp wc shop_coupon <command>
		"""
	And STDOUT should contain:
		"""
		create
		"""
	And STDOUT should contain:
		"""
		delete
		"""
	And STDOUT should contain:
		"""
		get
		"""
	And STDOUT should contain:
		"""
		list
		"""
	And STDOUT should contain:
		"""
		update
		"""

Scenario: CRUD a coupon

	When I run `wp wc shop_coupon create --user=admin --code='coupon code' --discount_type='fixed_product' --amount='5.00'  --porcelain`
	Then save STDOUT as {COUPON_ID}

	When I run `wp wc shop_coupon update {COUPON_ID} --user=admin --discount_type="percent" --amount='2' --porcelain`
	Then STDOUT should be a number

	When I run `wp wc shop_coupon get {COUPON_ID} --user=admin`
	Then STDOUT should contain:
		"""
		coupon code
		"""
	And STDOUT should contain:
		"""
		percent
		"""

	When I run `wp wc shop_coupon get {COUPON_ID} --user=admin --field=amount`
	Then STDOUT should contain:
		"""
		2.00
		"""

	When I run `wp wc shop_coupon list --user=admin`
	Then STDOUT should contain:
		"""
		id
		"""
	And STDOUT should contain:
		"""
		code
		"""
	And STDOUT should contain:
		"""
		date_created
		"""
	And STDOUT should contain:
		"""
		coupon code
		"""

	When I run `wp wc shop_coupon delete {COUPON_ID} --force=true --user=admin`
 	Then STDOUT should contain:
		"""
		Success
		"""
