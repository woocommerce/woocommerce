<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\RobotsTxt;
use WC_Unit_Test_Case;

/**
 * Tests for reading the sitemaps a robots.txt lists.
 */
class RobotsTxtTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Sitemap lines are listed wherever they appear, in any case and line ending; comments and empty values are not.
	 */
	public function test_sitemaps(): void {
		$this->assertSame( array(), ( new RobotsTxt( '' ) )->sitemaps() );
		$this->assertSame(
			array( 'https://example.com/sitemap_index.xml', 'https://example.com/products.xml' ),
			( new RobotsTxt( "Sitemap: https://example.com/sitemap_index.xml\r\nUser-agent: *\r\nDisallow:\r\n# Sitemap: https://example.com/old.xml\nSITEMAP: https://example.com/products.xml\nSitemap:\n" ) )->sitemaps()
		);
	}
}
