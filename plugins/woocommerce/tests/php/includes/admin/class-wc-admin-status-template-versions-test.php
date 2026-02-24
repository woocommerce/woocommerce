<?php

declare( strict_types=1 );

/**
 * Class WC_Admin_Status_Template_Versions_Test.
 *
 * Validates that block notice templates keep version parity with their legacy counterparts.
 */
class WC_Admin_Status_Template_Versions_Test extends WC_Unit_Test_Case {

	/**
	 * @dataProvider data_block_notice_templates
	 *
	 * @param string $template Template slug, without directory or extension.
	 */
	public function test_block_notice_template_versions_match_legacy_notices( $template ) {
		$core_template  = 'notices/' . $template . '.php';
		$block_template = 'block-notices/' . $template . '.php';

		$core_version  = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $core_template );
		$block_version = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $block_template );

		$this->assertNotSame(
			'',
			$core_version,
			sprintf( 'Expected @version header to be present for %s.', $core_template )
		);

		$this->assertSame(
			$core_version,
			$block_version,
			sprintf(
				'Expected %1$s to keep version parity with %2$s so block notices do not appear outdated.',
				$block_template,
				$core_template
			)
		);
	}

	/**
	 * Templates that must maintain version parity.
	 *
	 * @return array
	 */
	public function data_block_notice_templates() {
		return array(
			array( 'error' ),
			array( 'notice' ),
			array( 'success' ),
		);
	}
}
