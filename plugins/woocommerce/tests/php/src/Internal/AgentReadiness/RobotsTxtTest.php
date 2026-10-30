<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\RobotsTxt;
use WC_Unit_Test_Case;

/**
 * Tests for reading robots.txt rules.
 */
class RobotsTxtTest extends WC_Unit_Test_Case {

	/**
	 * @testdox A crawler follows its own group, others the * group; agent names ignore case.
	 */
	public function test_groups(): void {
		$sut = new RobotsTxt( "User-agent: *\nDisallow: /\n\nUser-agent: oai-searchbot\nUser-agent: ChatGPT-User\nDisallow: /cart/\n" );

		$this->assertTrue( $sut->allows( 'OAI-SearchBot', '/shop/' ) );
		$this->assertTrue( $sut->allows( 'ChatGPT-User', '/shop/' ), 'Two agents can share a group' );
		$this->assertFalse( $sut->allows( 'OAI-SearchBot', '/cart/' ) );
		$this->assertFalse( $sut->allows( 'PerplexityBot', '/shop/' ) );

		$own_group = new RobotsTxt( "User-agent: *\nDisallow: /\n\nUser-agent: Claude-User\n" );
		$this->assertTrue( $own_group->allows( 'Claude-User', '/' ), 'A crawler\'s own group with no rules allows everything' );
	}

	/**
	 * @testdox The longest matching rule wins, Allow wins a tie, * and a final $ match as in RFC 9309.
	 */
	public function test_rule_precedence_and_patterns(): void {
		$sut = new RobotsTxt( "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /*.txt$\nAllow: /llms.txt$\nDisallow: /tag\nAllow: /tag\n# Disallow: /shop/\nDisallow:\n" );

		$this->assertFalse( $sut->allows( 'ClaudeBot', '/wp-admin/options.php' ) );
		$this->assertTrue( $sut->allows( 'ClaudeBot', '/wp-admin/admin-ajax.php' ) );
		$this->assertFalse( $sut->allows( 'ClaudeBot', '/notes.txt' ) );
		$this->assertTrue( $sut->allows( 'ClaudeBot', '/notes.txt?x=1' ), '$ ends the path' );
		$this->assertTrue( $sut->allows( 'ClaudeBot', '/llms.txt' ) );
		$this->assertTrue( $sut->allows( 'ClaudeBot', '/tag/a' ), 'Allow wins a tie' );
		$this->assertTrue( $sut->allows( 'ClaudeBot', '/shop/' ), 'Comments and an empty Disallow block nothing' );
		$this->assertFalse( ( new RobotsTxt( "User-agent: *\nDisallow: /a" . str_repeat( '*', 200 ) . "z\n" ) )->allows( 'ClaudeBot', '/a' . str_repeat( 'x', 200 ) . 'z' ) );
	}

	/**
	 * @testdox An empty file allows everything; Sitemap lines are listed wherever they appear.
	 */
	public function test_empty_and_sitemaps(): void {
		$this->assertTrue( ( new RobotsTxt( '' ) )->allows( 'OAI-SearchBot', '/' ) );
		$this->assertSame(
			array( 'https://example.com/sitemap_index.xml' ),
			( new RobotsTxt( "Sitemap: https://example.com/sitemap_index.xml\r\nUser-agent: *\r\nDisallow:\r\n" ) )->sitemaps()
		);
	}
}
