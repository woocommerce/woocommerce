<?php

namespace Automattic\WooCommerce\Tests\Internal\ReceiptRendering;

use Automattic\WooCommerce\Internal\ReceiptRendering\ReceiptRenderingEngine;
use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests for the ReceiptRenderingEngine class.
 */
class ReceiptRenderingEngineTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ReceiptRenderingEngine
	 */
	private ReceiptRenderingEngine $sut;

	/**
	 * Mock of the TransientFilesEngine class, used by the tested object.
	 *
	 * @var TransientFilesEngine
	 */
	private TransientFilesEngine $tfe_mock;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->tfe_mock = $this->getMockBuilder( TransientFilesEngine::class )->getMock();
		$this->sut      = new ReceiptRenderingEngine();
		$this->sut->init( $this->tfe_mock, wc_get_container()->get( LegacyProxy::class ) );
	}

	/**
	 * @testdox 'generate_receipt' returns null if the id of a not existing order is passed.
	 */
	public function test_generate_receipt_for_no_existing_order_returns_null() {
		$filename = $this->sut->generate_receipt( -1 );
		$this->assertNull( $filename );
	}

	/**
	 * @testdox 'generate_receipt' returns the file name of an already existing receipt if 'force_new' is not true.
	 */
	public function test_generate_receipt_returns_existing_receipt_if_force_new_is_not_true() {
		$order = OrderHelper::create_order();

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, 'the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::never() )
			->method( 'create_transient_file' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'get_transient_file_path' )
			->with( 'the_existing_receipt_filename' )
			->willReturn( 'transient_files/the_existing_receipt_filename' );

		$filename = $this->sut->generate_receipt( $order );
		$this->assertEquals( 'the_existing_receipt_filename', $filename );

		$meta = $order->get_meta( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY );
		$this->assertEquals( 'the_existing_receipt_filename', $meta );
	}

	/**
	 * @testdox 'generate_receipt' returns the file name of a new receipt if 'force_new' is not true but no receipt file exists.
	 */
	public function test_generate_receipt_returns_new_receipt_file_if_no_receipt_exists_and_force_new_is_not_true() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'strtotime' => fn ( $arg) => '+1 days' === $arg ? -1 : strtotime( $arg ),
				'gmdate'    => fn ( $format, $value) => 'Y-m-d' === $format && -1 === $value ? '2999-12-31' : gmdate( $format, $value ),
			)
		);

		$order = OrderHelper::create_order();

		$this->tfe_mock
			->expects( self::once() )
			->method( 'create_transient_file' )
			->with( self::isType( 'string' ), self::equalTo( '2999-12-31' ) )
			->willReturn( 'the_generated_file_name' );

		$filename = $this->sut->generate_receipt( $order );
		$this->assertEquals( 'the_generated_file_name', $filename );

		$meta = $order->get_meta( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY );
		$this->assertEquals( 'the_generated_file_name', $meta );
	}

	/**
	 * @testdox 'generate_receipt' returns the file name of a new receipt if a receipt file already exists but 'force_new' is true.
	 */
	public function test_generate_receipt_returns_new_receipt_file_if_receipt_exists_but_force_new_is_true() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'strtotime' => fn ( $arg) => '+1 days' === $arg ? -1 : strtotime( $arg ),
				'gmdate'    => fn ( $format, $value) => 'Y-m-d' === $format && -1 === $value ? '2999-12-31' : gmdate( $format, $value ),
			)
		);

		$order = OrderHelper::create_order();

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, 'the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::never() )
			->method( 'get_transient_file_path' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'create_transient_file' )
			->with( self::isType( 'string' ), self::equalTo( '2999-12-31' ) )
			->willReturn( 'the_generated_file_name' );

		$filename = $this->sut->generate_receipt( $order, null, true );
		$this->assertEquals( 'the_generated_file_name', $filename );

		$meta = $order->get_meta( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY );
		$this->assertEquals( 'the_generated_file_name', $meta );
	}

	/**
	 * @testdox 'generate_receipt' uses the supplied expiration date to create the transient file.
	 */
	public function test_generate_receipt_with_custom_expiration_date() {
		$order = OrderHelper::create_order();

		$this->tfe_mock
			->expects( self::once() )
			->method( 'create_transient_file' )
			->with( self::isType( 'string' ), self::equalTo( '2888-10-20' ) )
			->willReturn( 'the_generated_file_name' );

		$filename = $this->sut->generate_receipt( $order, '2888-10-20' );
		$this->assertEquals( 'the_generated_file_name', $filename );

		$meta = $order->get_meta( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY );
		$this->assertEquals( 'the_generated_file_name', $meta );
	}

	/**
	 * @testdox 'generate_receipt' throws an exception if an invalid expiration date is supplied.
	 *
	 * @testWith ["NOT_A_DATE"]
	 *           ["2000-01-01"]
	 *           ["2999-34-89"]
	 *
	 * @param string $expiration_date The expiration date to test.
	 */
	public function test_generate_receipt_throws_for_bad_expiration_date( string $expiration_date ) {
		$this->expectException( \InvalidArgumentException::class );

		$sut = wc_get_container()->get( ReceiptRenderingEngine::class );

		$order = OrderHelper::create_order();
		$sut->generate_receipt( $order, $expiration_date );
	}

	/**
	 * @testdox 'get_existing_receipt' returns null if the id of a not existing order is passed.
	 */
	public function test_get_existing_receipt_for_no_existing_order_returns_null() {
		$filename = $this->sut->get_existing_receipt( -1 );
		$this->assertNull( $filename );
	}

	/**
	 * @testdox 'get_existing_receipt' returns the file name of an existing receipt if the appropriate order meta entry exists and the file actually exists too and has not expired.
	 */
	public function test_get_existing_receipt_returns_existing_receipt() {
		$order = OrderHelper::create_order();

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, 'the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'get_transient_file_path' )
			->with( 'the_existing_receipt_filename' )
			->willReturn( 'transient_files/the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'file_has_expired' )
			->with( 'transient_files/the_existing_receipt_filename' )
			->willReturn( false );

		$filename = $this->sut->get_existing_receipt( $order );
		$this->assertEquals( 'the_existing_receipt_filename', $filename );
	}

	/**
	 * @testdox 'get_existing_receipt' returns the file name of an existing receipt if the appropriate order meta entry doesn't exist.
	 */
	public function test_get_existing_receipt_returns_null_if_no_meta_entry() {
		$order = OrderHelper::create_order();

		$this->tfe_mock
			->expects( self::never() )
			->method( 'get_transient_file_path' );

		$filename = $this->sut->get_existing_receipt( $order );
		$this->assertNull( $filename );
	}

	/**
	 * @testdox 'get_existing_receipt' returns the file name of an existing receipt if the appropriate order meta entry exists but the file doesn't.
	 */
	public function test_get_existing_receipt_returns_null_if_meta_entry_exists_but_file_doesnt() {
		$order = OrderHelper::create_order();

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, 'the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'get_transient_file_path' )
			->with( 'the_existing_receipt_filename' )
			->willReturn( null );

		$filename = $this->sut->get_existing_receipt( $order );
		$this->assertNull( $filename );
	}

	/**
	 * @testdox 'get_existing_receipt' returns the file name of an existing receipt if the appropriate order meta entry exists and the file exists but has expired.
	 */
	public function test_get_existing_receipt_returns_null_if_meta_entry_exists_but_file_has_expired() {
		$order = OrderHelper::create_order();

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, 'the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'get_transient_file_path' )
			->with( 'the_existing_receipt_filename' )
			->willReturn( 'transient_files/the_existing_receipt_filename' );

		$this->tfe_mock
			->expects( self::once() )
			->method( 'file_has_expired' )
			->with( 'transient_files/the_existing_receipt_filename' )
			->willReturn( true );

		$filename = $this->sut->get_existing_receipt( $order );
		$this->assertNull( $filename );
	}
}
