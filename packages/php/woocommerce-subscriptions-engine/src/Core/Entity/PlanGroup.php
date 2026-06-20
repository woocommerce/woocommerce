<?php
/**
 * PlanGroup - a merchandising container for selling plans.
 *
 * `merchant_code` is an optional stable external identifier; when present it is
 * unique at the storage layer and is the deduplication key consumers use to
 * make group creation idempotent. `app_id` scopes a group to a solution family.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

defined( 'ABSPATH' ) || exit;

/**
 * PlanGroup entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) group or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class PlanGroup {

	/**
	 * Group id, or null before it is persisted.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Display name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Optional stable external identifier; unique at the storage layer.
	 *
	 * @var string|null
	 */
	private $merchant_code;

	/**
	 * Display ordering metadata, e.g. [{ name, position }].
	 *
	 * @var array<int, mixed>
	 */
	private $options_display;

	/**
	 * Solution-family scope, e.g. a third-party app slug.
	 *
	 * @var string|null
	 */
	private $app_id;

	/**
	 * Use {@see self::create()} or {@see self::from_storage()}.
	 *
	 * @param int|null          $id              Group id, or null before save.
	 * @param string            $name            Display name.
	 * @param string|null       $merchant_code   Optional stable external identifier.
	 * @param array<int, mixed> $options_display Display ordering metadata.
	 * @param string|null       $app_id          Solution-family scope.
	 */
	private function __construct( ?int $id, string $name, ?string $merchant_code, array $options_display, ?string $app_id ) {
		$this->id              = $id;
		$this->name            = $name;
		$this->merchant_code   = $merchant_code;
		$this->options_display = $options_display;
		$this->app_id          = $app_id;
	}

	/**
	 * Build a new, unsaved group.
	 *
	 * @param array<string, mixed> $args Group attributes.
	 */
	public static function create( array $args ): self {
		return new self(
			null,
			(string) $args['name'],
			$args['merchant_code'] ?? null,
			$args['options_display'] ?? array(),
			$args['app_id'] ?? null
		);
	}

	/**
	 * Hydrate from a stored row.
	 *
	 * @param array<string, mixed> $row Decoded plan-group row.
	 */
	public static function from_storage( array $row ): self {
		return new self(
			isset( $row['id'] ) ? (int) $row['id'] : null,
			(string) $row['name'],
			isset( $row['merchant_code'] ) ? (string) $row['merchant_code'] : null,
			is_array( $row['options_display'] ?? null ) ? $row['options_display'] : array(),
			isset( $row['app_id'] ) ? (string) $row['app_id'] : null
		);
	}

	/**
	 * Group id, or null before save.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Assign the id after a successful insert.
	 *
	 * @param int $id Group id.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Display name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the display name.
	 *
	 * @param string $name Display name.
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Optional stable external identifier; unique at the storage layer.
	 */
	public function get_merchant_code(): ?string {
		return $this->merchant_code;
	}

	/**
	 * Display ordering metadata.
	 *
	 * @return array<int, mixed>
	 */
	public function get_options_display(): array {
		return $this->options_display;
	}

	/**
	 * Set the display ordering metadata.
	 *
	 * @param array<int, mixed> $options_display Display ordering metadata.
	 */
	public function set_options_display( array $options_display ): void {
		$this->options_display = $options_display;
	}

	/**
	 * Solution-family scope.
	 */
	public function get_app_id(): ?string {
		return $this->app_id;
	}

	/**
	 * Serialize to the storage column shape (excluding generated id/timestamps).
	 *
	 * @return array<string, mixed>
	 */
	public function to_storage(): array {
		return array(
			'name'            => $this->name,
			'merchant_code'   => $this->merchant_code,
			'options_display' => $this->options_display,
			'app_id'          => $this->app_id,
		);
	}
}
