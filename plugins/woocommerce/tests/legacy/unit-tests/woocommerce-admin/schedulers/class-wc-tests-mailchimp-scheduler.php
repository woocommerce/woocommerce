<?php
/**
 * MailchimpScheduler tests
 *
 * @package Automattic\WooCommerce\Internal\Admin\Schedulers
 */

use Automattic\WooCommerce\Internal\Admin\Onboarding;
use Automattic\WooCommerce\Internal\Admin\Schedulers\MailchimpScheduler;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * Class WC_Admin_Tests_Mailchimp_Scheduler
 */
class WC_Admin_Tests_Mailchimp_Scheduler extends WC_Unit_Test_Case {
	/**
	 * @var \Automattic\WooCommerce\Internal\Admin\Schedulers\MailchimpScheduler MailchimpScheduler instance to test
	 */
	private $instance;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject WC_Logger_Interface mock
	 */
	private $logger_mock;

	/**
	 * Overridden setUp() method.
	 */
	public function setUp(): void {
		$this->logger_mock = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();

		$this->instance = $this->getMockBuilder( MailchimpScheduler::class )
								->setConstructorArgs( array( $this->logger_mock ) )
								->setMethods( array( 'make_request' ) )
								->getMock();

		MailchimpScheduler::reset();

		parent::setUp();
	}

	/**
	 * When woocommerce_onboarding_subscribed_to_mailchimp value us 'yes'.
	 * Then it should abort.
	 */
	public function test_it_aborts_if_already_subscribed() {
		// When.
		update_option( MailchimpScheduler::SUBSCRIBED_OPTION_NAME, 'yes' );

		// Then.
		$this->assertFalse( $this->instance->run() );
	}

	/**
	 * When is_agree_marketing is missing or value is false.
	 * Then it should abort.
	 */
	public function test_it_aborts_if_is_agree_marketing_is_false_or_missing() {
		// When.
		$profile_data = array( 'store_email' => 'test@test.com' );
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );
		$this->assertFalse( $this->instance->run() );

		$profile_data = array(
			'is_agree_marketing' => false,
			'store_email'        => 'test@test.com',
		);
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );

		// Then.
		$this->assertFalse( $this->instance->run() );
	}

	/**
	 * When store_email is missing.
	 * Then it should abort.
	 */
	public function test_it_aborts_if_store_email_is_missing() {
		// When.
		$profile_data = array( 'is_agree_marketing' => true );
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );

		// Then.
		$this->assertFalse( $this->instance->run() );
	}


	/**
	 * When failed requests reaches the threshold.
	 * Then it should abort.
	 */
	public function test_it_aborts_if_failed_requests_reaches_the_threshold() {
		// Given.
		$profile_data = array(
			'is_agree_marketing' => true,
			'store_email'        => 'test@test.com',
		);
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );

		// When.
		update_option(
			MailchimpScheduler::SUBSCRIBED_ERROR_COUNT_OPTION_NAME,
			MailchimpScheduler::MAX_ERROR_THRESHOLD
		);

		// Then.
		$this->assertFalse( $this->instance->run() );
	}

	/**
	 * Given is_agree_marketing and store_email values are set.
	 * When the request to the API returns WP_Error.
	 * Then it should log error message and error count.
	 */
	public function test_it_logs_error_when_wp_error_is_returned() {
		// Given.
		$profile_data = array(
			'is_agree_marketing' => true,
			'store_email'        => 'test@test.com',
		);
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );

		// When.
		$wp_error = new WP_Error();
		$this->instance->method( 'make_request' )->willReturn( $wp_error );

		// Then.
		$this->logger_mock->expects( $this->exactly( 2 ) )
							->method( 'error' )
							->with( 'Error getting a response from Mailchimp API.', array( 'source' => MailchimpScheduler::LOGGER_CONTEXT ) );

		$this->instance->run();
		$this->assertEquals( 1, get_option( MailchimpScheduler::SUBSCRIBED_ERROR_COUNT_OPTION_NAME ) );

		// Check for the missing 'body'.
		$this->instance->method( 'make_request' )->willReturn( array() );
		$this->instance->run();
		$this->assertEquals( 2, get_option( MailchimpScheduler::SUBSCRIBED_ERROR_COUNT_OPTION_NAME ) );
	}



	/**
	 * Given is_agree_marketing and store_email values are set.
	 * When the request to the API returns body without 'success'.
	 * Then it should log error message and error count.
	 */
	public function test_it_logs_error_when_response_data_is_incorrect() {
		// Given.
		$profile_data = array(
			'is_agree_marketing' => true,
			'store_email'        => 'test@test.com',
		);
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );

		// When.
		$body = wp_json_encode( array() );
		$this->instance->method( 'make_request' )->willReturn( array( 'body' => $body ) );

		// Then.
		$this->logger_mock->expects( $this->once() )
						->method( 'error' )
						->with(
		                  // phpcs:ignore
							'Incorrect response from Mailchimp API with: ' . print_r( array(), true ),
							array( 'source' => MailchimpScheduler::LOGGER_CONTEXT )
						);

		$this->instance->run();
		$this->assertEquals( 1, get_option( MailchimpScheduler::SUBSCRIBED_ERROR_COUNT_OPTION_NAME ) );
	}

	/**
	 * Given is_agree_marketing and store_email values are set
	 * When the request to the API returns success: true
	 * Then woocommerce_onboarding_subscribed_to_mailchimp should be updated to 'yes'
	 */
	public function test_it_updates_option_value_when_everything_went_well() {
		// Given.
		$profile_data = array(
			'is_agree_marketing' => true,
			'store_email'        => 'test@test.com',
		);
		update_option( OnboardingProfile::DATA_OPTION, $profile_data );

		// When.
		$body = wp_json_encode( array( 'success' => true ) );
		$this->instance->method( 'make_request' )->willReturn( array( 'body' => $body ) );

		$this->instance->run();

		// Then.
		$this->assertEquals( 'yes', get_option( 'woocommerce_onboarding_subscribed_to_mailchimp' ) );
	}
}
