<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\DeprecatedExtendedTask;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use WC_Unit_Test_Case;

/**
 * DeprecatedExtendedTask test.
 *
 * @class DeprecatedExtendedTaskTest.
 */
class DeprecatedExtendedTaskTest extends WC_Unit_Test_Case {

	/**
	 * Tests that image metadata provided via task args is exposed.
	 */
	public function test_exposes_image_metadata_from_args() {
		$task = new DeprecatedExtendedTask(
			new TaskList( array( 'id' => 'extended' ) ),
			array(
				'id'        => 'third-party-task',
				'title'     => 'Third party task',
				'image_url' => 'https://example.com/image.png',
				'image_alt' => 'Third party illustration',
			)
		);

		$this->assertSame( 'https://example.com/image.png', $task->get_image_url() );
		$this->assertSame( 'Third party illustration', $task->get_image_alt() );

		$json = $task->get_json();
		$this->assertSame( 'https://example.com/image.png', $json['imageUrl'] );
		$this->assertSame( 'Third party illustration', $json['imageAlt'] );
	}

	/**
	 * Tests that image metadata defaults to empty strings when not provided.
	 */
	public function test_defaults_to_empty_image_metadata() {
		$task = new DeprecatedExtendedTask(
			new TaskList( array( 'id' => 'extended' ) ),
			array(
				'id'    => 'third-party-task',
				'title' => 'Third party task',
			)
		);

		$this->assertSame( '', $task->get_image_url() );
		$this->assertSame( '', $task->get_image_alt() );
	}
}
