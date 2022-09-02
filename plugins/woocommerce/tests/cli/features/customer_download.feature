Feature: List WooCommerce customer downloads

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc customer_download --help`
	Then STDOUT should contain:
		"""
		wp wc customer_download <command>
		"""
	And STDOUT should contain:
		"""
		list
		"""

Scenario: List a specific customer's downloads

	When I run `wp wc customer_download list 1 --user=1`
	Then STDOUT should contain:
		"""
		download_url
		"""
