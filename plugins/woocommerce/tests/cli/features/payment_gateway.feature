Feature: Manage WooCommerce Payment Gateways

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc payment_gateway --help`
	Then STDOUT should contain:
		"""
		wp wc payment_gateway <command>
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

Scenario: List Payment Gateways

	When I run `wp wc payment_gateway list --user=admin`
	Then STDOUT should contain:
		"""
		cod
		"""
	And STDOUT should contain:
		"""
		BACS
		"""
	And STDOUT should contain:
		"""
		id
		"""
	And STDOUT should contain:
		"""
		method_title
		"""

Scenario: Read & Update Payment Gateways

	When I run `wp wc payment_gateway update bacs --user=admin --title="Updated Direct Bank Transfer" --settings='{"instructions":"These are test instructions"}' --porcelain`
	Then STDOUT should contain:
		"""
		bacs
		"""

	When I run `wp wc payment_gateway get bacs --user=admin`
	Then STDOUT should contain:
		"""
		BACS
		"""
	And STDOUT should contain:
		"""
		Updated Direct Bank Transfer
		"""
	And STDOUT should contain:
		"""
		Updated Direct Bank Transfer
		"""
	And STDOUT should contain:
		"""
		These are test instructions
		"""
