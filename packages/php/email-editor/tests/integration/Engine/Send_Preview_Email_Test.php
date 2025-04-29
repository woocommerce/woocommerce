<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Codeception\Stub\Expected;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Unit test for Send_Preview_Email_Test class.
 */
class Send_Preview_Email_Test extends \Email_Editor_Integration_Test_Case {

	/**
	 * Instance of Send_Preview_Email
	 *
	 * @var Send_Preview_Email
	 */
	private $send_preview_email;

	/**
	 * Instance of Renderer
	 *
	 * @var Renderer
	 */
	private $renderer_mock;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();

		$this->renderer_mock = $this->createMock( Renderer::class );
		$this->renderer_mock->method( 'render' )->willReturn(
			array(
				'html' => 'test html',
				'text' => 'test text',
			)
		);

		$this->send_preview_email = $this->getServiceWithOverrides(
			Send_Preview_Email::class,
			array(
				'renderer' => $this->renderer_mock,
			)
		);
	}

	/**
	 * Test it sends preview email.
	 */
	public function testItSendsPreviewEmail(): void {
		$mock = $this->createPartialMock( Send_Preview_Email::class, array( 'send_email', 'set_personalize_content' ) );
		$mock->expects( $this->once() )
			->method( 'send_email' )
			->willReturn( true );
		$mock->method( 'set_personalize_content' )
			->willReturnArgument( 0 );

		$personalizer = $this->createMock( Personalizer::class );
		$mock->__construct( $this->renderer_mock, $personalizer );

		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
			)
		);

		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => $email_post_id,
		);

		$result = $mock->send_preview_email( $post_data );

		$this->assertTrue( $result );
	}

	/**
	 * Test it returns the status of send_mail.
	 */
	public function testItReturnsTheStatusOfSendMail(): void {
		$mailing_status = false;

		$mock = $this->createPartialMock( Send_Preview_Email::class, array( 'send_email', 'set_personalize_content' ) );
		$mock->expects( $this->once() )
			->method( 'send_email' )
			->willReturn( $mailing_status );

		$mock->method( 'set_personalize_content' )
			->willReturnArgument( 0 );

		$personalizer = $this->createMock( Personalizer::class );
		$mock->__construct( $this->renderer_mock, $personalizer );

		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
			)
		);

		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => $email_post_id,
		);

		$result = $mock->send_preview_email( $post_data );

		$this->assertEquals( $mailing_status, $result );
	}

	/**
	 * Test it throws an exception with invalid email
	 */
	public function testItThrowsAnExceptionWithInvalidEmail(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid email' );
		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example',
			'postId'       => 4,
		);
		$this->send_preview_email->send_preview_email( $post_data );
	}

	/**
	 * Test it throws an exception when post id is not provided
	 */
	public function testItThrowsAnExceptionWhenPostIdIsNotProvided(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Missing required data' );
		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => null,
		);
		$this->send_preview_email->send_preview_email( $post_data );
	}

	/**
	 * Test it throws an exception when the post cannot be found
	 */
	public function testItThrowsAnExceptionWhenPostCannotBeFound(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid post' );
		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => 100,
		);
		$this->send_preview_email->send_preview_email( $post_data );
	}
}
