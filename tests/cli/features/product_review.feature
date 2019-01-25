Feature: Manage WooCommerce Product Reviews

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc product_review --help`
	Then STDOUT should contain:
		"""
		wp wc product_review <command>
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

Scenario: CRUD a product review

	When I run `wp wc product create --user=admin --name='Test Simple Product'  --porcelain`
	Then save STDOUT as {PRODUCT_ID}

	When I run `wp wc product_review create {PRODUCT_ID} --user=admin --review='Testing' --name='Mr. Test' --email='woo@woo.local' --porcelain`
	Then STDOUT should be a number
	And save STDOUT as {PRODUCT_REVIEW_ID}

	When I run `wp wc product_review update {PRODUCT_ID} {PRODUCT_REVIEW_ID} --user=admin --review="Updated" --porcelain`
	Then STDOUT should be a number

	When I run `wp wc product_review get {PRODUCT_ID} {PRODUCT_REVIEW_ID} --user=admin`
	Then STDOUT should contain:
		"""
		Updated
		"""
	And STDOUT should contain:
		"""
		woo@woo.local
		"""
	And STDOUT should contain:
		"""
		Mr. Test
		"""

	When I run `wp wc product_review list {PRODUCT_ID} --user=admin`
	Then STDOUT should contain:
		"""
		date_created
		"""
	And STDOUT should contain:
		"""
		Updated
		"""

	When I run `wp wc product_review delete {PRODUCT_ID} {PRODUCT_REVIEW_ID} --force=true --user=admin`
 	Then STDOUT should contain:
		"""
		Success
		"""
