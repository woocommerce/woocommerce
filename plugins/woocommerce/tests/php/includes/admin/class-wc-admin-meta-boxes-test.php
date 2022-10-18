<?php

/**
 * Tests for meta box-related functionality in the product editor.
 */
class WC_Admin_Meta_Boxes_Test extends WC_Unit_Test_Case {
	/**
	 * @var WC_Admin_Meta_Boxes
	 */
	private $sut;

	/**
	 * Create subject-under-test.
	 */
	public function set_up() {
		$this->sut = new WC_Admin_Meta_Boxes();
		parent::set_up();
	}

	/**
	 * @testdox Test that meta box errors can be stored and retrieved as expected.
	 */
	public function test_persistence_of_meta_box_errors() {
		WC_Admin_Meta_Boxes::add_error( 'Oh no!' );
		WC_Admin_Meta_Boxes::add_error( 'Crikey!' );

		$error_output = $this->get_meta_box_error_output();
		$this->assertEmpty( $error_output, 'If the errors have not first been saved to the database, they cannot be retrieved for display.' );

		$this->simulate_shutdown();
		$error_output = $this->get_meta_box_error_output();
		$this->assertStringContainsString( 'Oh no!', $error_output, 'The error output contains the expected error string (test #1).' );
		$this->assertStringContainsString( 'Crikey!', $error_output, 'The error output contains the expected error string (test #2).' );

		$error_output = $this->get_meta_box_error_output();
		$this->assertEmpty( $error_output, 'The error store is cleared after errors have been output.' );
	}

	/**
	 * @testdox Test that the stored meta box errors are not accidentally cleared by concurrent requests before they are rendered.
	 */
	public function test_meta_box_errors_are_not_accidentally_cleared_during_shutdown() {
		WC_Admin_Meta_Boxes::add_error( 'Yikes!' );

		$this->simulate_shutdown();
		$this->simulate_shutdown();

		$error_output = $this->get_meta_box_error_output();
		$this->assertStringContainsString( 'Yikes!', $error_output, 'The stored error persisted across requests.' );
	}

	/**
	 * Calls the WC_Admin_Meta_Boxes::output_errors() method, capturing and returning the output.
	 *
	 * @return string
	 */
	private function get_meta_box_error_output(): string {
		ob_start();
		$this->sut->output_errors();
		return ob_get_clean();
	}

	/**
	 * Simulates what normally happens when `shutdown` occurs, in relation to the WC_Admin_Meta_Boxes class.
	 * We avoid actually calling `do_action( 'shutdown' )` because we do not have perfect isolation between tests, and
	 * wish to avoid unwanted side-effects unrelated to this set of tests.
	 */
	private function simulate_shutdown() {
		// Previously (prior to 6.5.0), $this->sut->save_errors() would have been called during shutdown.
		$this->sut->append_to_error_store();
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
	}
}
