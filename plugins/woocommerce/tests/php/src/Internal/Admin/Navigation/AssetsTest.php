<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Assets;
use Automattic\WooCommerce\Internal\Admin\Navigation\Menu_Reconciler;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.Commenting.FunctionComment.Missing -- Test sets WP globals for fixtures; setUp/tearDown self-document.

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Assets
 */
class AssetsTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Assets
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new Assets();
	}

	public function tearDown(): void {
		// Assets registers hooks on construction; remove only ours so they
		// don't leak into other suites.
		remove_action( 'admin_enqueue_scripts', array( $this->sut, 'enqueue' ) );
		remove_action( 'admin_head', array( $this->sut, 'print_critical_css' ) );
		remove_action( 'adminmenu', array( $this->sut, 'print_early_cascade_script' ) );
		remove_filter( 'admin_body_class', array( $this->sut, 'add_body_class' ) );
		$this->set_tree( null );
		unset( $_GET['page'], $GLOBALS['pagenow'] );
		parent::tearDown();
	}

	/**
	 * Set the reconciler's static tree, which Assets reads via get_tree().
	 *
	 * @param array|null $tree Tree, or null to clear.
	 */
	private function set_tree( ?array $tree ): void {
		$ref = new \ReflectionProperty( Menu_Reconciler::class, 'tree' );
		$ref->setAccessible( true );
		$ref->setValue( null, $tree );
	}

	/**
	 * A minimal two-node tree (root + a wc-admin child) used by the
	 * page-resolution assertions below.
	 *
	 * @return array
	 */
	private function sample_tree(): array {
		return array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'wc-admin'    => array(
				'parent'   => 'woocommerce',
				'title'    => 'Home',
				'position' => 10,
			),
		);
	}

	/**
	 * Before reconciliation has run (get_tree() === null), the body class is
	 * returned untouched.
	 */
	public function test_add_body_class_is_a_no_op_without_a_tree() {
		$this->set_tree( null );

		$this->assertSame( 'foo bar', $this->sut->add_body_class( 'foo bar' ) );
	}

	/**
	 * On a request that resolves to a tree slug, the active marker class is
	 * appended so CSS/JS can key off it.
	 */
	public function test_add_body_class_marks_woo_pages_active() {
		$this->set_tree( $this->sample_tree() );
		$_GET['page']       = 'wc-admin';
		$GLOBALS['pagenow'] = 'admin.php';

		$this->assertStringContainsString( 'wc-nav-v2-active', $this->sut->add_body_class( 'foo' ) );
	}

	/**
	 * On a request that does not resolve to any tree slug, the body class is
	 * left unchanged even though a tree exists.
	 */
	public function test_add_body_class_leaves_non_woo_pages_untouched() {
		$this->set_tree( $this->sample_tree() );
		$_GET['page']       = 'some-other-plugin';
		$GLOBALS['pagenow'] = 'admin.php';

		$this->assertSame( 'foo', $this->sut->add_body_class( 'foo' ) );
	}
}
