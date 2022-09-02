Feature: Running system status tools

Background:

	Given a WP install

Scenario: Help for all available commands

	When I run `wp wc tool --help`
	Then STDOUT should contain:
		"""
		wp wc tool <command>
		"""
	And STDOUT should contain:
		"""
		list
		"""
	And STDOUT should contain:
		"""
		run
		"""

	When I run `wp wc tool list --help`
	Then STDOUT should contain:
		"""
		wp wc tool list [--fields=<fields>] [--field=<field>]
		"""

	When I run `wp wc tool run --help`
	Then STDOUT should contain:
		"""
		wp wc tool run <id>
		"""

Scenario: List all available tools

	When I run `wp wc tool list --user=1`
	Then STDOUT should be a table containing rows:
	| id                | name          | action           | description                                             |
	| clear_transients  | WC transients | Clear transients | This tool will clear the product/shop transients cache. |

	When I run `wp wc tool list --format=ids --user=1`
	Then STDOUT should contain:
		"""
		install_pages delete_taxes reset_tracking
		"""

	When I run `wp wc tool list --format=body --user=1`
	Then STDOUT should be JSON containing:
		"""
		[{"id":"clear_transients"}]
		"""

Scenario: Run a specific tool

	When I run `wp wc tool run clear_transients --user=1`
	Then STDOUT should contain:
		"""
		Success: Updated system_status_tool clear_transients.
		"""
