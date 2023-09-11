<?php
/**
 * Unit tests for the Verticals Selector class.
 *
 * @package WooCommerce\Verticals\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\Verticals;

use Automattic\WooCommerce\Blocks\Verticals\ChatGPTClient;
use Automattic\WooCommerce\Blocks\Verticals\Client;
use Automattic\WooCommerce\Blocks\Verticals\VerticalsSelector;
use Mockery;
use \WP_UnitTestCase;

/**
 * Class Client_Test.
 */
class VerticalsSelectorTest extends WP_UnitTestCase {
	private const PROMPT = 'Filter the objects provided below and return the one that has a title that better matches this ' .
							'description of an online store with the following description: "The store description.". ' .
							'Objects: [ID=1, Name="Vertical 1"], [ID=2, Name="Vertical 2"]. The response should include ' .
							'exclusively the ID of the object that better matches the description in the following format: ' .
							'[id=selected_id]. Do not include other text or explanation.';

	/**
	 * The Verticals Selector instance.
	 *
	 * @var VerticalsSelector $selector
	 */
	private VerticalsSelector $selector;

	/**
	 * The verticals API client.
	 *
	 * @var Client $verticals_api_client
	 */
	private Client $verticals_api_client;

	/**
	 * The ChatGPT client.
	 *
	 * @var ChatGPTClient $chat_gpt_client
	 */
	private ChatGPTClient $chat_gpt_client;

	/**
	 * The response from the verticals API.
	 *
	 * @var array $valid_verticals_response
	 */
	private array $valid_verticals_response = array(
		array(
			'id'   => 1,
			'name' => 'Vertical 1',
		),
		array(
			'id'   => 2,
			'name' => 'Vertical 2',
		),
	);

	/**
	 * Initialize the client instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->verticals_api_client = Mockery::mock( Client::class );
		$this->chat_gpt_client      = Mockery::mock( ChatGPTClient::class );
		$this->selector             = new VerticalsSelector( $this->verticals_api_client, $this->chat_gpt_client );
	}

	/**
	 * Test get_vertical_id returns an error when the business description is empty.
	 */
	public function test_get_vertical_id_returns_an_error_when_the_business_description_is_empty() {
		update_option( VerticalsSelector::STORE_DESCRIPTION_OPTION_KEY, '' );

		$this->verticals_api_client->shouldReceive( 'get_verticals' )->never();
		$this->chat_gpt_client->shouldReceive( 'text_completion' )->never();

		$response = $this->selector->get_vertical_id();
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'empty_business_description', $response->get_error_code() );
		$this->assertEquals( 'The business description is empty.', $response->get_error_message() );
	}

	/**
	 * Test get_vertical_id returns an error when the verticals API request fails.
	 */
	public function test_get_vertical_id_returns_an_error_when_the_verticals_api_request_fails() {
		update_option( VerticalsSelector::STORE_DESCRIPTION_OPTION_KEY, 'The store description.' );

		$this->verticals_api_client->shouldReceive( 'get_verticals' )
			->once()
			->andReturn( new \WP_Error( 'verticals_api_error', 'Request to the Verticals API failed.' ) );
		$this->chat_gpt_client->shouldReceive( 'text_completion' )->never();

		$response = $this->selector->get_vertical_id();
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'verticals_api_error', $response->get_error_code() );
		$this->assertEquals( 'Request to the Verticals API failed.', $response->get_error_message() );
	}

	/**
	 * Test get_vertical_id returns an error when the ChatGPT API request is fails.
	 */
	public function test_get_vertical_id_returns_an_error_when_the_chatgpt_api_request_fails() {
		update_option( VerticalsSelector::STORE_DESCRIPTION_OPTION_KEY, 'The store description.' );

		$this->verticals_api_client->shouldReceive( 'get_verticals' )
			->once()
			->andReturn( $this->valid_verticals_response );

		$this->chat_gpt_client->shouldReceive( 'text_completion' )
			->once()
			->with( self::PROMPT )
			->andReturn( new \WP_Error( 'chatgpt_api_error', 'Request to the ChatGPT API failed.' ) );

		$response = $this->selector->get_vertical_id();
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'chatgpt_api_error', $response->get_error_code() );
		$this->assertEquals( 'Request to the ChatGPT API failed.', $response->get_error_message() );
	}

	/**
	 * Test get_vertical_id returns the vertical id when the response from the GPT API is in the expected format.
	 */
	public function test_get_vertical_id_returns_the_vertical_id_when_the_response_from_the_gpt_api_is_in_the_expected_format() {
		update_option( VerticalsSelector::STORE_DESCRIPTION_OPTION_KEY, 'The store description.' );

		$this->verticals_api_client->shouldReceive( 'get_verticals' )
			->once()
			->andReturn( $this->valid_verticals_response );

		$this->chat_gpt_client->shouldReceive( 'text_completion' )
			->once()
			->with( self::PROMPT )
			->andReturn( '[id=1]' );

		$response = $this->selector->get_vertical_id();
		$this->assertEquals( 1, $response );
	}

	/**
	 * Test get_vertical_id returns an error when the response from the GPT API is not in the expected format.
	 */
	public function test_get_vertical_id_returns_an_error_when_the_response_from_the_gpt_api_is_not_in_the_expected_format() {
		update_option( VerticalsSelector::STORE_DESCRIPTION_OPTION_KEY, 'The store description.' );

		$this->verticals_api_client->shouldReceive( 'get_verticals' )
			->once()
			->andReturn( $this->valid_verticals_response );

		$this->chat_gpt_client->shouldReceive( 'text_completion' )
			->once()
			->with( self::PROMPT )
			->andReturn( 'Unexpected response' );

		$response = $this->selector->get_vertical_id();
		$this->assertEquals( '', $response );
	}
}
