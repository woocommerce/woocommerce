<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Internal\OrderReviews\StarRating;
use WC_Unit_Test_Case;

/**
 * Tests for the accessible star-rating renderer.
 */
class StarRatingTest extends WC_Unit_Test_Case {

	/**
	 * Reset filter state between tests.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_review_order_rating_labels' );
		parent::tearDown();
	}

	/**
	 * @testdox render() emits a radiogroup, five radios, and a caption span.
	 */
	public function test_render_emits_complete_markup(): void {
		$html = StarRating::render(
			array(
				'name'      => 'reviews[42][rating]',
				'id_prefix' => 'review-rating-42',
				'label_id'  => 'review-rating-label-42',
			)
		);

		$this->assertStringContainsString( 'role="radiogroup"', $html );
		$this->assertStringContainsString( 'aria-labelledby="review-rating-label-42"', $html );
		$this->assertStringContainsString( 'aria-describedby="review-rating-42-caption"', $html );

		// Five inputs, each with a unique id.
		foreach ( range( 1, 5 ) as $value ) {
			$this->assertStringContainsString( 'id="review-rating-42-' . $value . '"', $html );
			$this->assertStringContainsString( 'value="' . $value . '"', $html );
		}

		$this->assertStringContainsString( 'name="reviews[42][rating]"', $html );
		$this->assertStringContainsString( 'aria-live="polite"', $html );
	}

	/**
	 * @testdox render() returns an empty string when required args are missing.
	 */
	public function test_render_returns_empty_when_required_args_missing(): void {
		$this->assertSame( '', StarRating::render( array() ) );
		$this->assertSame(
			'',
			StarRating::render(
				array(
					'name'      => 'foo',
					'id_prefix' => '',
					'label_id'  => 'bar',
				)
			)
		);
	}

	/**
	 * @testdox render() pre-checks the supplied selected value.
	 */
	public function test_render_marks_selected_value_checked(): void {
		$html = StarRating::render(
			array(
				'name'      => 'reviews[42][rating]',
				'id_prefix' => 'review-rating-42',
				'label_id'  => 'review-rating-label-42',
				'selected'  => 4,
			)
		);

		// The `checked()` helper emits `checked='checked'`.
		$this->assertMatchesRegularExpression(
			'#id="review-rating-42-4"[^>]*checked#',
			$html
		);
		$this->assertMatchesRegularExpression( '#class="woocommerce-star-rating__caption"[^<]*>\s*Good\s*</span>#s', $html );
	}

	/**
	 * @testdox render() treats an out-of-range selected value as no selection.
	 */
	public function test_render_rejects_out_of_range_selected(): void {
		$args = array(
			'name'      => 'reviews[42][rating]',
			'id_prefix' => 'review-rating-42',
			'label_id'  => 'review-rating-label-42',
		);

		$above = StarRating::render( array_merge( $args, array( 'selected' => 99 ) ) );
		$below = StarRating::render( array_merge( $args, array( 'selected' => -3 ) ) );

		$this->assertDoesNotMatchRegularExpression( '#<input[^>]*\bchecked\b#', $above );
		$this->assertDoesNotMatchRegularExpression( '#<input[^>]*\bchecked\b#', $below );
	}

	/**
	 * @testdox get_labels() returns the documented defaults out of the box.
	 */
	public function test_get_labels_returns_defaults(): void {
		$labels = StarRating::get_labels();

		$this->assertSame(
			array(
				1 => 'Very poor',
				2 => 'Not that bad',
				3 => 'Average',
				4 => 'Good',
				5 => 'Perfect',
			),
			$labels
		);
	}

	/**
	 * @testdox woocommerce_review_order_rating_labels filter overrides the defaults.
	 */
	public function test_filter_can_override_labels(): void {
		add_filter(
			'woocommerce_review_order_rating_labels',
			static function () {
				return array(
					1 => 'Hated it',
					2 => 'Meh',
					3 => 'OK',
					4 => 'Liked it',
					5 => 'Loved it',
				);
			}
		);

		$this->assertSame( 'Loved it', StarRating::get_labels()[5] );
	}

	/**
	 * @testdox A buggy filter that drops keys falls back to the defaults for the missing slots.
	 */
	public function test_filter_falls_back_when_keys_missing(): void {
		add_filter(
			'woocommerce_review_order_rating_labels',
			static function () {
				// Drop entries 2 and 4 entirely; replace 5.
				return array(
					1 => 'Tiny',
					3 => 'Mid',
					5 => 'Huge',
				);
			}
		);

		$labels = StarRating::get_labels();

		$this->assertSame( 'Tiny', $labels[1] );
		$this->assertSame( 'Not that bad', $labels[2] );
		$this->assertSame( 'Mid', $labels[3] );
		$this->assertSame( 'Good', $labels[4] );
		$this->assertSame( 'Huge', $labels[5] );
	}
}
