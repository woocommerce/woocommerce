<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\JsonLd;

/**
 * Tests for reading JSON-LD blocks. The fixtures are HTML strings, so this is not a WC_Unit_Test_Case.
 */
class JsonLdTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @testdox An SEO plugin's @graph is read, a schema: type counts as Product, and a block that doesn't parse is skipped.
	 */
	public function test_seo_plugin_graph_is_read(): void {
		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type' => 'WebPage',
					'name'  => 'Dummy Product',
				),
				array(
					'@type'  => array( 'schema:Product' ),
					'name'   => 'Dummy Product',
					'image'  => array( 'https://example.org/image.jpg' ),
					'offers' => array(
						'@type'  => 'AggregateOffer',
						'offers' => array(
							array(
								'@type'              => 'Offer',
								'priceSpecification' => array( 'price' => '10.00' ),
								'availability'       => 'https://schema.org/InStock',
							),
						),
					),
				),
			),
		);
		$html  = '<!doctype html><html><head>';
		$html .= '<script type="application/ld+json">{ not json</script>';
		$html .= '<script type="application/ld+json">' . wp_json_encode( $graph ) . '</script>';
		$html .= '</head><body></body></html>';

		$items = JsonLd::items( $html, 'Product', 'ProductGroup' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'Dummy Product', $items[0]['name'] );
		$this->assertSame( array( 'https://example.org/image.jpg' ), $items[0]['image'] );
		$this->assertSame( 'AggregateOffer', $items[0]['offers']['@type'] );
		$this->assertSame( '10.00', $items[0]['offers']['offers'][0]['priceSpecification']['price'] );
		$this->assertSame( 'https://schema.org/InStock', $items[0]['offers']['offers'][0]['availability'] );
	}
}
