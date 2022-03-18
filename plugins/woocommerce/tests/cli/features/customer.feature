Feature: Manage WooCommerce customers

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc customer --help`
	Then STDOUT should contain:
		"""
		wp wc customer <command>
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

Scenario: Get the value of an individual post field

 	When I run `wp wc customer get 1 --field=username --user=1`
	Then STDOUT should contain:
		"""
		admin
		"""

Scenario: CRUD a post

	When I run `wp wc customer create --user=admin --username="testuser" --password="hunter2" --email="woo@woo.local"  --porcelain`
	Then STDOUT should be a number
	And save STDOUT as {CUSTOMER_ID}

	When I run `wp wc customer update {CUSTOMER_ID} --user=admin --first_name="Test" --billing='{"city":"Portland","state":"OR"}' --porcelain`
	Then STDOUT should be a number

	When I run `wp wc customer get {CUSTOMER_ID} --user=admin`
	Then STDOUT should contain:
		"""
		Portland
		"""
	And STDOUT should contain:
		"""
		woo@woo.local
		"""

	When I run `wp wc customer delete {CUSTOMER_ID} --force=true --user=admin --porcelain`
	Then STDOUT should be a number
