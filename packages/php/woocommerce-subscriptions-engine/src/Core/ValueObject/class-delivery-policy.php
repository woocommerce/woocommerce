<?php
/**
 * Delivery_Policy - typed value object for a plan's delivery anchors, cutoff,
 * and intent.
 *
 * Mirrors the `delivery_policy` JSON column shape, deliberately thin for now.
 * Shape:
 *   {
 *     anchors: [{ type: 'MONTHDAY', day: int }, { type: 'YEARDAY', day: int, month: int }, ...],
 *     cutoff:  ?mixed,
 *     intent:  ?mixed
 *   }
 *
 * The shipping/delivery policy parameter set is a new concept still being
 * designed, so `cutoff` and `intent` are passed through verbatim and anchor
 * entries stay as plain associative arrays until a call site needs typed access.
 *
 * Lives in the WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

defined( 'ABSPATH' ) || exit;

/**
 * Delivery_Policy value object.
 *
 * Immutable. Construct via {@see self::from_array()} when hydrating from a
 * stored row, or via the constructor when building one in code.
 */
final class Delivery_Policy {

	/**
	 * Anchor entries. Each: `{type, day, month?}`.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $anchors;

	/**
	 * Cutoff window - shape to be designed; passed through verbatim.
	 *
	 * @var mixed
	 */
	private $cutoff;

	/**
	 * Delivery intent - shape to be designed; passed through verbatim.
	 *
	 * @var mixed
	 */
	private $intent;

	/**
	 * Build a delivery policy.
	 *
	 * @param array<int, array<string, mixed>> $anchors Anchor entries.
	 * @param mixed                            $cutoff  Cutoff window.
	 * @param mixed                            $intent  Delivery intent.
	 */
	public function __construct( array $anchors, $cutoff, $intent ) {
		$this->anchors = $anchors;
		$this->cutoff  = $cutoff;
		$this->intent  = $intent;
	}

	/**
	 * Hydrate from the JSON-decoded `delivery_policy` column shape.
	 *
	 * Missing keys default to safe values - empty array for `anchors`, null for
	 * `cutoff` and `intent`.
	 *
	 * @param array<string, mixed> $data Decoded delivery_policy row.
	 */
	public static function from_array( array $data ): self {
		$anchors = is_array( $data['anchors'] ?? null ) ? $data['anchors'] : array();
		return new self(
			$anchors,
			$data['cutoff'] ?? null,
			$data['intent'] ?? null
		);
	}

	/**
	 * Anchor entries describing when in the cycle a charge fires.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_anchors(): array {
		return $this->anchors;
	}

	/**
	 * Cutoff window. Shape to be designed; returned verbatim.
	 *
	 * @return mixed
	 */
	public function get_cutoff() {
		return $this->cutoff;
	}

	/**
	 * Delivery intent. Shape to be designed; returned verbatim.
	 *
	 * @return mixed
	 */
	public function get_intent() {
		return $this->intent;
	}

	/**
	 * Serialize back to the JSON column shape. Lossless round-trip with from_array().
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'anchors' => $this->anchors,
			'cutoff'  => $this->cutoff,
			'intent'  => $this->intent,
		);
	}
}
