<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions;

use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestionIncentives;
use Automattic\WooCommerce\Tests\Internal\Admin\Suggestions\Mocks\FakeIncentive;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * PaymentExtensionSuggestionIncentives provider test.
 *
 * @class PaymentExtensionSuggestionIncentives
 */
class PaymentExtensionSuggestionIncentivesTest extends WC_Unit_Test_Case {
	/**
	 * @var PaymentExtensionSuggestionIncentives
	 */
	protected $sut;

	/**
	 * @var FakeIncentive|MockObject
	 */
	protected $mock_incentive_provider;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mock_incentive_provider = $this->getMockBuilder( FakeIncentive::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_all', 'is_visible', 'is_dismissed' ) )
			->getMock();

		// Mock the get_incentive_instance method.
		$this->sut = $this->getMockBuilder( PaymentExtensionSuggestionIncentives::class )
			->onlyMethods( array( 'get_incentive_instance' ) )
			->getMock();
	}

	/**
	 * Test get_incentive with no provider.
	 */
	public function test_get_incentive_no_provider() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( null );

		// Act.
		$incentive = $this->sut->get_incentive( 'suggestion1', 'RO' );

		// Assert.
		$this->assertNull( $incentive );
	}

	/**
	 * Test get_incentive when there are no incentives.
	 */
	public function test_get_incentive_no_incentive() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO' )
			->willReturn( array() );

		// Act.
		$incentive = $this->sut->get_incentive( 'suggestion1', 'RO' );

		// Assert.
		$this->assertNull( $incentive );
	}

	/**
	 * Test get_incentive.
	 */
	public function test_get_incentive() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		// All are visible.
		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_visible' )
			->willReturn( true );

		// Act.
		$incentive = $this->sut->get_incentive( 'suggestion1', 'RO' );

		// Assert.
		$this->assertSame( 'incentive1', $incentive['id'] );
	}

	/**
	 * Test get_incentive with type.
	 */
	public function test_get_incentive_with_type() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO', 'type1' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		// All are visible.
		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_visible' )
			->willReturn( true );

		// Act.
		$incentive = $this->sut->get_incentive( 'suggestion1', 'RO', 'type1' );

		// Assert.
		$this->assertSame( 'incentive1', $incentive['id'] );
	}

	/**
	 * Test get_incentive with type when there is no incentive.
	 */
	public function test_get_incentive_with_type_no_incentive() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO', 'bogus_type' )
			->willReturn( array() );

		// Act.
		$incentive = $this->sut->get_incentive( 'suggestion1', 'RO', 'bogus_type' );

		// Assert.
		$this->assertNull( $incentive );
	}

	/**
	 * Test get_incentives when there is no incentive provider.
	 */
	public function test_get_incentives_no_provider() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( null );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO' );

		// Assert.
		$this->assertSame( array(), $incentives );
	}

	/**
	 * Test get_incentives when there are no incentives.
	 */
	public function test_get_incentives_no_incentives() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO' )
			->willReturn( array() );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO' );

		// Assert.
		$this->assertSame( array(), $incentives );
	}

	/**
	 * Test get_incentives when there are incentives.
	 */
	public function test_get_incentives() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// All are visible.
		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_visible' )
			->willReturn( true );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO' );

		// Assert.
		$this->assertCount( 2, $incentives );
		$this->assertSame( 'incentive1', $incentives[0]['id'] );
		$this->assertSame( 'incentive2', $incentives[1]['id'] );
	}

	/**
	 * Test get_incentives when there are incentives but none are visible.
	 */
	public function test_get_incentives_not_visible() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// None are visible.
		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_visible' )
			->willReturn( false );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO' );

		// Assert.
		$this->assertCount( 0, $incentives );
	}

	/**
	 * Test get_incentives with type.
	 */
	public function test_get_incentives_with_type() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO', 'type2' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type2',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// All are visible.
		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_visible' )
			->willReturn( true );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO', 'type2' );

		// Assert.
		$this->assertCount( 2, $incentives );
		$this->assertSame( 'incentive1', $incentives[0]['id'] );
		$this->assertSame( 'incentive2', $incentives[1]['id'] );
	}

	/**
	 * Test get_incentives with type when there are no incentives.
	 */
	public function test_get_incentives_with_type_no_incentives() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO', 'bogus_type' )
			->willReturn( array() );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO', 'bogus_type' );

		// Assert.
		$this->assertSame( array(), $incentives );
	}

	/**
	 * Test get_incentives when skipping visibility checks.
	 */
	public function test_get_incentives_skips_visibility_checks() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_all' )
			->with( 'RO', 'type2' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type2',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// Assert.
		$this->mock_incentive_provider
			->expects( $this->never() )
			->method( 'is_visible' );

		// Act.
		$incentives = $this->sut->get_incentives( 'suggestion1', 'RO', 'type2', true );

		// Assert.
		$this->assertCount( 2, $incentives );
		$this->assertSame( 'incentive1', $incentives[0]['id'] );
		$this->assertSame( 'incentive2', $incentives[1]['id'] );
	}

	/**
	 * Test is_incentive_visible when there is no incentive provider.
	 */
	public function test_is_incentive_visible_no_provider() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( null );

		// Act.
		$incentive_visible = $this->sut->is_incentive_visible( 'incentive1', 'suggestion1', 'RO' );

		// Assert.
		$this->assertFalse( $incentive_visible );
	}

	/**
	 * Test is_incentive_visible.
	 */
	public function test_is_incentive_visible() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->once() )
			->method( 'is_visible' )
			->with( 'incentive1', 'RO', true )
			->willReturn( true );

		// Act.
		$this->assertTrue( $this->sut->is_incentive_visible( 'incentive1', 'suggestion1', 'RO', true ) );
	}

	/**
	 * Test is_incentive_dismissed when there is no incentive provider.
	 */
	public function test_is_incentive_dismissed_no_provider() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( null );

		// Act.
		$incentive_dismissed = $this->sut->is_incentive_dismissed( 'incentive1', 'suggestion1' );

		// Assert.
		$this->assertFalse( $incentive_dismissed );
	}

	/**
	 * Test is_incentive_dismissed.
	 */
	public function test_is_incentive_dismissed() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->once() )
			->method( 'is_dismissed' )
			->with( 'incentive1' )
			->willReturn( true );

		// Act.
		$incentive_dismissed = $this->sut->is_incentive_dismissed( 'incentive1', 'suggestion1' );

		// Assert.
		$this->assertTrue( $incentive_dismissed );
	}

	/**
	 * Test is_incentive_dismissed when the incentive is not dismissed.
	 */
	public function test_is_incentive_dismissed_no() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( $this->mock_incentive_provider );

		$this->mock_incentive_provider
			->expects( $this->once() )
			->method( 'is_dismissed' )
			->with( 'incentive1' )
			->willReturn( false );

		// Act.
		$incentive_dismissed = $this->sut->is_incentive_dismissed( 'incentive1', 'suggestion1' );

		// Assert.
		$this->assertFalse( $incentive_dismissed );
	}

	/**
	 * Test has_incentive_provider when there is no incentive provider.
	 */
	public function test_has_incentive_provider_no_provider() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( null );

		// Act.
		$incentive_provider = $this->sut->has_incentive_provider( 'suggestion1' );

		// Assert.
		$this->assertFalse( $incentive_provider );
	}

	/**
	 * Test has_incentive_provider.
	 */
	public function test_has_incentive_provider() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentive_instance' )
			->with( 'suggestion1' )
			->willReturn( new FakeIncentive( 'suggestion1' ) );

		// Act.
		$incentive_provider = $this->sut->has_incentive_provider( 'suggestion1' );

		// Assert.
		$this->assertTrue( $incentive_provider );
	}
}
