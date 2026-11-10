<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Compares whole rendered emails with stored snapshots, so a change in one block or in the CSS
 * inlining that alters other parts of the email shows up in review.
 *
 * After an intended change, run `pnpm test:update-email-snapshots` in the package and review
 * the diff of the `.html` files under `snapshots/`.
 */
class Renderer_Snapshot_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Block types that got an email render callback in setUp.
	 *
	 * @var \WP_Block_Type[]
	 */
	private array $wired_block_types = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		// The site theme differs between WordPress versions, so it is kept out of the snapshots.
		add_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->wire_core_block_renderers();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->wired_block_types as $block_type ) {
			unset( $block_type->render_email_callback );
		}
		remove_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );
		parent::tearDown();
	}

	/**
	 * Test the rendered email matches its snapshot.
	 *
	 * @dataProvider fixture_provider
	 * @param string $name Fixture and snapshot name.
	 */
	public function testRenderedEmailMatchesSnapshot( string $name ): void {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => (string) file_get_contents( __DIR__ . "/fixtures/{$name}.html" ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.
			)
		);
		$this->assertIsInt( $post_id );
		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );

		$rendered = $this->di_container->get( Renderer::class )->render( $post, 'Subject', 'Preheader', 'en_US' );
		$actual   = $this->normalize( $rendered['html'] );

		$snapshot = __DIR__ . "/snapshots/{$name}.html";
		if ( getenv( 'UPDATE_EMAIL_SNAPSHOTS' ) ) {
			file_put_contents( $snapshot, $actual ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- writes a test snapshot.
		}

		$this->assertFileExists( $snapshot, 'Snapshot missing. Run `pnpm test:update-email-snapshots` to create it.' );
		$this->assertSame( file_get_contents( $snapshot ), $actual, 'The rendered email changed. If intended, run `pnpm test:update-email-snapshots` and commit the snapshot.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local snapshot file.
	}

	/**
	 * Fixtures with the blocks each snapshot covers.
	 *
	 * @return array<string, array{string}>
	 */
	public function fixture_provider(): array {
		return array(
			'core blocks'   => array( 'core-blocks' ),
			'styled blocks' => array( 'styled-blocks' ),
		);
	}

	/**
	 * Gives core blocks their email renderers, as Bootstrap does in production. Blocks are
	 * registered before the test starts, so the block settings filter can't do it here.
	 */
	private function wire_core_block_renderers(): void {
		$initializer = $this->di_container->get( Initializer::class );
		$initializer->initialize();
		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_type ) {
			$settings = $initializer->update_block_settings( array( 'name' => $block_type->name ) );
			if ( isset( $settings['render_email_callback'] ) ) {
				$block_type->set_props( array( 'render_email_callback' => $settings['render_email_callback'] ) );
				$this->wired_block_types[]         = $block_type;
			}
		}
	}

	/**
	 * Removes what differs between test environments and puts each tag on its own line,
	 * so a snapshot diff shows which part of the email changed.
	 *
	 * @param string $html Rendered email HTML.
	 */
	private function normalize( string $html ): string {
		$html = str_replace( untrailingslashit( home_url() ), '{{site_url}}', $html );
		// WordPress names this class with a counter or, in older versions, a hash.
		$html = (string) preg_replace( '/\bwp-elements-[0-9a-f]+\b/', 'wp-elements-{{id}}', $html );
		return trim( str_replace( '><', ">\n<", $html ) ) . "\n";
	}
}
