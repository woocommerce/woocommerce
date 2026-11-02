<?php
/**
 *  Tests for the WC_Admin_Dashboard_Setup class.
 *
 * @package WooCommerce\Tests\Admin
 */

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;

/**
 * Class WC_Admin_Dashboard_Setup_Test
 */
class WC_Admin_Dashboard_Setup_Test extends WC_Unit_Test_Case {

	/**
	 * Whether the default country option existed before the test.
	 *
	 * @var bool
	 */
	private $default_country_option_existed = false;

	/**
	 * Default country option value before the test.
	 *
	 * @var mixed
	 */
	private $default_country_option_value;

	/**
	 * Set up
	 */
	public function setUp(): void {
		$missing_option                       = new stdClass();
		$this->default_country_option_value   = get_option( 'woocommerce_default_country', $missing_option );
		$this->default_country_option_existed = $missing_option !== $this->default_country_option_value;

		parent::setUp();

		// Set default country to non-US so that 'payments' task gets added but 'woocommerce-payments' doesn't,
		// by default it won't be considered completed but we can manually change that as needed.
		update_option( 'woocommerce_default_country', 'JP' );
		$password    = wp_generate_password( 8, false, false );
		$this->admin = wp_insert_user(
			array(
				'user_login' => "test_admin$password",
				'user_pass'  => $password,
				'user_email' => "admin$password@example.com",
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin );
	}

	/**
	 * Tear down
	 */
	public function tearDown(): void {
		try {
			remove_all_filters( 'woocommerce_available_payment_gateways' );
		} finally {
			try {
				parent::tearDown();
			} finally {
				$this->invalidate_dashboard_option_caches();

				if ( $this->default_country_option_existed ) {
					update_option( 'woocommerce_default_country', $this->default_country_option_value );
				} else {
					delete_option( 'woocommerce_default_country' );
				}
			}
		}
	}

	/**
	 * Invalidate caches for options modified by dashboard tests.
	 */
	private function invalidate_dashboard_option_caches(): void {
		$option_names = array(
			'woocommerce_default_country',
			'woocommerce_default_homepage_layout',
			'woocommerce_onboarding_profile',
			'woocommerce_task_list_hidden',
			'woocommerce_task_list_hidden_lists',
		);

		foreach ( $option_names as $option_name ) {
			wp_cache_delete( $option_name, 'options' );
		}
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Includes widget class and return the class.
	 *
	 * @return WC_Admin_Dashboard_Setup
	 */
	public function get_widget() {
		return include __DIR__ . '/../../../../includes/admin/class-wc-admin-dashboard-setup.php';
	}

	/**
	 * Return widget output (HTML).
	 *
	 * @return string Render widget HTML
	 */
	public function get_widget_output() {
		update_option( 'woocommerce_task_list_hidden', 'no' );

		ob_start();
		$this->get_widget()->render();
		return ob_get_clean();
	}


	/**
	 * Given the task list is not hidden and is not complete, make sure the widget is rendered.
	 */
	public function test_widget_render() {
		// Force the "payments" task to be considered incomplete.
		add_filter(
			'woocommerce_available_payment_gateways',
			function () {
				return array();
			}
		);
		global $wp_meta_boxes;
		$task_list = $this->get_widget()->get_task_list();
		$task_list->unhide();

		$this->get_widget();
		$this->assertArrayHasKey( 'wc_admin_dashboard_setup', $wp_meta_boxes['dashboard']['normal']['high'] );
	}

	/**
	 * Tests widget does not display when task list is complete.
	 */
	public function test_widget_does_not_display_when_task_list_complete() {
		// phpcs:disable Squiz.Commenting
		$task_list = new class() {
			public function is_complete() {
				return true;
			}
			public function is_hidden() {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting
		$widget = $this->get_widget();
		$widget->set_task_list( $task_list );

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests widget does not display when task list is hidden.
	 */
	public function test_widget_does_not_display_when_task_list_hidden() {
		$widget = $this->get_widget();
		$widget->get_task_list()->hide();

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests widget does not display when user cannot manage woocommerce.
	 */
	public function test_widget_does_not_display_when_missing_capabilities() {
		$password = wp_generate_password( 8, false, false );
		$author   = wp_insert_user(
			array(
				'user_login' => "test_author$password",
				'user_pass'  => $password,
				'user_email' => "author$password@example.com",
				'role'       => 'author',
			)
		);
		wp_set_current_user( $author );

		$widget = $this->get_widget();

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests widget does not display when task list is unavailable.
	 */
	public function test_widget_does_not_display_when_no_task_list() {
		$widget = $this->get_widget();
		$widget->set_task_list( null );

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests the widget output when 1 task has been completed.
	 */
	public function test_initial_widget_output() {
		// Force the "payments" task to be considered incomplete.
		add_filter(
			'woocommerce_available_payment_gateways',
			function () {
				return array();
			}
		);

		$html = $this->get_widget_output();

		$required_strings = array(
			'Step \d+ of \d+',
			'dashboard-widget-finish-setup__title',
			'dashboard-widget-finish-setup__image',
		);

		foreach ( $required_strings as $required_string ) {
			$this->assertMatchesRegularExpression( "/{$required_string}/", $html );
		}
	}

	/**
	 * Tests the widget output when the next task is in progress.
	 *
	 * @testdox Widget output includes the in-progress label for the next task.
	 */
	public function test_widget_output_includes_in_progress_label() {
		// phpcs:disable Squiz.Commenting
		$task_list = new class() {
			public function is_complete() {
				return false;
			}
			public function is_hidden() {
				return false;
			}
			public function get_viewable_tasks() {
				return array(
					new class() extends \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task {
						public function get_id() {
							return 'payments';
						}
						public function get_title() {
							return 'Set up payments';
						}
						public function get_content() {
							return 'Choose payment providers and enable payment methods at checkout.';
						}
						public function get_time() {
							return '5 minutes';
						}
						public function get_action_url() {
							return 'payments';
						}
						public function get_action_label() {
							return 'Configure payments';
						}
						public function is_complete() {
							return false;
						}
						public function is_in_progress() {
							return true;
						}
						public function in_progress_label() {
							return 'Test account';
						}
						public function get_image_url() {
							return WC()->plugin_url() . '/assets/images/task_list/payment-illustration.svg';
						}
						public function get_image_alt() {
							return 'Payment illustration';
						}
					},
				);
			}
		};
		// phpcs:enable Squiz.Commenting

		$widget = $this->get_widget();
		$widget->set_task_list( $task_list );

		ob_start();
		$widget->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'dashboard-widget-finish-setup__in-progress', $html );
		$this->assertStringContainsString( 'Set up payments', $html );
		$this->assertStringContainsString( 'Choose payment providers and enable payment methods at checkout.', $html );
		$this->assertStringContainsString( 'Configure payments', $html );
		$this->assertStringContainsString( 'payment-illustration.svg', $html );
		$this->assertStringContainsString( 'Test account', $html );
		$this->assertMatchesRegularExpression( '/<h3 class="dashboard-widget-finish-setup__title">.*Test account.*<\/h3>/s', $html );
	}

	/**
	 * Tests completed task count as it completes one by one
	 */
	public function test_widget_renders_completed_task_count() {
		// Force the "payments" task to be considered completed
		// by faking a valid payment gateway.
		add_filter(
			'woocommerce_available_payment_gateways',
			function () {
				return array(
					new class() extends WC_Payment_Gateway {
					},
				);
			}
		);

		$completed_tasks_count = $this->get_widget()->get_completed_tasks_count();
		$tasks_count           = count( $this->get_widget()->get_tasks() );
		$step_number           = $completed_tasks_count + 1;
		if ( $completed_tasks_count === $tasks_count ) {
			$this->assertEmpty( $this->get_widget_output() );
		} else {
			$this->assertMatchesRegularExpression( "/Step {$step_number} of {$tasks_count}/", $this->get_widget_output() );
		}
	}

	/**
	 * Tests the widget renders a task-provided contextual image.
	 *
	 * @testdox Widget output uses the task's own image_url and image_alt when provided.
	 */
	public function test_widget_output_uses_task_provided_image() {
		// phpcs:disable Squiz.Commenting
		$task_list = new class() {
			public function is_complete() {
				return false;
			}
			public function is_hidden() {
				return false;
			}
			public function get_viewable_tasks() {
				return array(
					new class() extends \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task {
						public function get_id() {
							return 'third-party-task';
						}
						public function get_title() {
							return 'Third party task';
						}
						public function get_content() {
							return 'A task added by an extension.';
						}
						public function get_time() {
							return '5 minutes';
						}
						public function is_complete() {
							return false;
						}
						public function get_image_url() {
							return 'https://example.com/custom-illustration.png';
						}
						public function get_image_alt() {
							return 'Custom extension illustration';
						}
					},
				);
			}
		};
		// phpcs:enable Squiz.Commenting

		$widget = $this->get_widget();
		$widget->set_task_list( $task_list );

		ob_start();
		$widget->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'https://example.com/custom-illustration.png', $html );
		$this->assertStringContainsString( 'Custom extension illustration', $html );
		$this->assertStringNotContainsString( 'dashboard-widget-setup.png', $html );
	}

	/**
	 * Tests the widget falls back to the default image when the task has none.
	 *
	 * @testdox Widget output falls back to the generic setup image when the task provides no image.
	 */
	public function test_widget_output_falls_back_to_default_image() {
		// phpcs:disable Squiz.Commenting
		$task_list = new class() {
			public function is_complete() {
				return false;
			}
			public function is_hidden() {
				return false;
			}
			public function get_viewable_tasks() {
				return array(
					new class() extends \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task {
						public function get_id() {
							return 'third-party-task';
						}
						public function get_title() {
							return 'Third party task';
						}
						public function get_content() {
							return 'A task added by an extension.';
						}
						public function get_time() {
							return '5 minutes';
						}
						public function is_complete() {
							return false;
						}
					},
				);
			}
		};
		// phpcs:enable Squiz.Commenting

		$widget = $this->get_widget();
		$widget->set_task_list( $task_list );

		ob_start();
		$widget->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'dashboard-widget-setup.png', $html );
		$this->assertStringContainsString( 'WooCommerce setup illustration', $html );
	}

	/**
	 * Tests get_button_link redirects to core profiler when it needs completion.
	 */
	public function test_get_button_link_redirects_to_core_profiler_when_needed() {
		// Set up onboarding profile data to indicate profiler is not completed.
		update_option( 'woocommerce_onboarding_profile', array( 'completed' => false ) );

		$widget    = $this->get_widget();
		$task_list = $widget->get_task_list();
		$tasks     = $task_list->get_viewable_tasks();

		if ( ! empty( $tasks ) ) {
			$first_task  = $tasks[0];
			$button_link = $widget->get_button_link( $first_task );

			// Should redirect to setup wizard when profiler is not completed.
			$this->assertStringContainsString( 'path=/setup-wizard', $button_link );
		}

		delete_option( 'woocommerce_onboarding_profile' );
	}

	/**
	 * Tests get_button_link redirects to core profiler when option does not exist.
	 */
	public function test_get_button_link_redirects_to_core_profiler_when_option_does_not_exist() {
		// Set up onboarding profile data to indicate profiler is not completed.
		delete_option( 'woocommerce_onboarding_profile' );

		$widget    = $this->get_widget();
		$task_list = $widget->get_task_list();
		$tasks     = $task_list->get_viewable_tasks();

		if ( ! empty( $tasks ) ) {
			$first_task  = $tasks[0];
			$button_link = $widget->get_button_link( $first_task );

			// Should redirect to setup wizard when profiler is not completed.
			$this->assertStringContainsString( 'path=/setup-wizard', $button_link );
		}

		delete_option( 'woocommerce_onboarding_profile' );
	}

	/**
	 * Tests get_button_link returns normal task URL when core profiler is completed.
	 */
	public function test_get_button_link_returns_normal_url_when_profiler_completed() {
		// Set up onboarding profile data to indicate profiler is completed.
		update_option( 'woocommerce_onboarding_profile', array( 'completed' => true ) );

		$widget    = $this->get_widget();
		$task_list = $widget->get_task_list();
		$tasks     = $task_list->get_viewable_tasks();

		if ( ! empty( $tasks ) ) {
			$first_task  = $tasks[0];
			$button_link = $widget->get_button_link( $first_task );

			// Should NOT redirect to setup wizard when profiler is completed.
			$this->assertStringNotContainsString( 'path=/setup-wizard', $button_link );
			// Should contain the task ID or actionUrl.
			$this->assertMatchesRegularExpression( '/(task=|path=)/', $button_link );
		}

		delete_option( 'woocommerce_onboarding_profile' );
	}

	/**
	 * Tests get_button_link returns normal task URL when core profiler is skipped.
	 */
	public function test_get_button_link_returns_normal_url_when_profiler_skipped() {
		// Set up onboarding profile data to indicate profiler is skipped.
		update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );

		$widget    = $this->get_widget();
		$task_list = $widget->get_task_list();
		$tasks     = $task_list->get_viewable_tasks();

		if ( ! empty( $tasks ) ) {
			$first_task  = $tasks[0];
			$button_link = $widget->get_button_link( $first_task );

			// Should NOT redirect to setup wizard when profiler is skipped.
			$this->assertStringNotContainsString( 'path=/setup-wizard', $button_link );
			// Should contain the task ID or actionUrl.
			$this->assertMatchesRegularExpression( '/(task=|path=)/', $button_link );
		}

		delete_option( 'woocommerce_onboarding_profile' );
	}
}
