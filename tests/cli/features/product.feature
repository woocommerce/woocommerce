Feature: Manage WooCommerce Products

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc product --help`
	Then STDOUT should contain:
		"""
		wp wc product <command>
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

Scenario: CRUD a product

	When I run `wp wc product create --user=admin --name='Test Simple Product' --type='simple' --regular_price='10.00' --sale_price='7.99' --porcelain`
	Then save STDOUT as {PRODUCT_ID}

	When I run `wp wc product update {PRODUCT_ID} --user=admin --description="Test product description" --sku='TEST SKU' --attributes='[{ "id": 0, "name": "Color", "position": 0, "visible": true, "variation": false, "options": ["Red", "Blue", "Green", "Yellow"]}]' --porcelain`
	Then STDOUT should be a number

	When I run `wp wc product get {PRODUCT_ID} --user=admin`
	Then STDOUT should contain:
		"""
		10.00
		"""
	And STDOUT should contain:
		"""
		7.99
		"""
	And STDOUT should contain:
		"""
		TEST SKU
		"""
	And STDOUT should contain:
		"""
		Test product description
		"""
	And STDOUT should contain:
		"""
		Color
		"""
	And STDOUT should contain:
		"""
		Yellow
		"""

	When I run `wp wc product get {PRODUCT_ID} --user=admin --field=price`
	Then STDOUT should contain:
		"""
		7.99
		"""

	When I run `wp wc product list --user=admin`
	Then STDOUT should contain:
		"""
		date_created
		"""
	And STDOUT should contain:
		"""
		Test Simple Product
		"""

	When I run `wp wc product delete {PRODUCT_ID} --force=true --user=admin`
 	Then STDOUT should contain:
		"""
		Success
		"""
