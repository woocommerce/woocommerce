<?php
/**
 * AccessProfileRegistry class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Actors;

defined( 'ABSPATH' ) || exit;

/**
 * PHP-defined POS access profiles (replaces the role-based capability map
 * from the prior WP-roles approach). Each profile maps every permission tag
 * to a tri-state value: allow | deny | approval_required.
 *
 * Built-in profile keys are reserved under the `pos_*` prefix. Extensions can
 * inject or override profiles/tags via the `woocommerce_pos_access_profiles`
 * filter at boot.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class AccessProfileRegistry {

	public const ACCESS_ALLOW             = 'allow';
	public const ACCESS_DENY              = 'deny';
	public const ACCESS_APPROVAL_REQUIRED = 'approval_required';

	public const PROFILE_CASHIER = 'pos_cashier';
	public const PROFILE_MANAGER = 'pos_manager';
	public const PROFILE_ADMIN   = 'pos_admin';

	/**
	 * Permission tags managed by the POS context. The mobile client uses
	 * these to gate UI actions; server-side validators consult them for
	 * override-approval checks.
	 */
	public const TAG_PROCESS_SALES           = 'process_sales';
	public const TAG_APPLY_EXISTING_COUPONS  = 'apply_existing_coupons';
	public const TAG_VIEW_ORDERS             = 'view_orders';
	public const TAG_CREATE_CUSTOM_DISCOUNTS = 'create_custom_discounts';
	public const TAG_REFUND_ORDERS           = 'refund_orders';
	public const TAG_EXIT_POS                = 'exit_pos';
	public const TAG_VIEW_POS_SETTINGS       = 'view_pos_settings';
	public const TAG_EDIT_POS_SETTINGS       = 'edit_pos_settings';
	public const TAG_MANAGE_POS_STAFF        = 'manage_pos_staff';
	public const TAG_MANAGER_APPROVAL        = 'manager_approval';

	/**
	 * Memoized profile map (after filter application).
	 *
	 * @var array<string, array{name: string, permissions: array<string, string>}>|null
	 */
	private ?array $profiles = null;

	/**
	 * Return all registered profiles, keyed by profile key.
	 *
	 * @return array<string, array{name: string, permissions: array<string, string>}>
	 */
	public function all(): array {
		if ( null === $this->profiles ) {
			/**
			 * Filters the registered POS access profiles.
			 *
			 * Each profile is an array with `name` (string) and `permissions`
			 * (map of tag => allow|deny|approval_required). Built-in keys use
			 * the `pos_*` prefix and should not be removed by extensions.
			 *
			 * @since 10.9.0
			 *
			 * @param array<string, array{name: string, permissions: array<string, string>}> $profiles
			 */
			$this->profiles = (array) apply_filters( 'woocommerce_pos_access_profiles', $this->built_in_profiles() );
		}
		return $this->profiles;
	}

	/**
	 * Return a single profile by key, or null if not registered.
	 *
	 * @param string $key Profile key (e.g. 'pos_cashier').
	 * @return array{name: string, permissions: array<string, string>}|null
	 */
	public function get( string $key ): ?array {
		$profiles = $this->all();
		return $profiles[ $key ] ?? null;
	}

	/**
	 * Return whether a profile key is registered.
	 *
	 * @param string $key Profile key.
	 * @return bool
	 */
	public function exists( string $key ): bool {
		return null !== $this->get( $key );
	}

	/**
	 * Return the access value (allow|deny|approval_required) for a given
	 * profile/tag pair. Returns 'deny' if the profile is unknown or the tag
	 * isn't listed (fail-closed default).
	 *
	 * @param string $profile_key Profile key.
	 * @param string $tag         Permission tag.
	 * @return string
	 */
	public function resolve( string $profile_key, string $tag ): string {
		$profile = $this->get( $profile_key );
		if ( null === $profile ) {
			return self::ACCESS_DENY;
		}
		return $profile['permissions'][ $tag ] ?? self::ACCESS_DENY;
	}

	/**
	 * Built-in profiles. Extensions can wrap/extend via the
	 * `woocommerce_pos_access_profiles` filter, but cannot replace this
	 * default set directly.
	 *
	 * @return array<string, array{name: string, permissions: array<string, string>}>
	 */
	private function built_in_profiles(): array {
		return array(
			self::PROFILE_CASHIER => array(
				'name'        => __( 'Cashier', 'woocommerce' ),
				'permissions' => array(
					self::TAG_PROCESS_SALES           => self::ACCESS_ALLOW,
					self::TAG_APPLY_EXISTING_COUPONS  => self::ACCESS_ALLOW,
					self::TAG_VIEW_ORDERS             => self::ACCESS_ALLOW,
					self::TAG_CREATE_CUSTOM_DISCOUNTS => self::ACCESS_APPROVAL_REQUIRED,
					self::TAG_REFUND_ORDERS           => self::ACCESS_APPROVAL_REQUIRED,
					self::TAG_EXIT_POS                => self::ACCESS_APPROVAL_REQUIRED,
					self::TAG_VIEW_POS_SETTINGS       => self::ACCESS_APPROVAL_REQUIRED,
					self::TAG_EDIT_POS_SETTINGS       => self::ACCESS_DENY,
					self::TAG_MANAGE_POS_STAFF        => self::ACCESS_DENY,
					self::TAG_MANAGER_APPROVAL        => self::ACCESS_DENY,
				),
			),
			self::PROFILE_MANAGER => array(
				'name'        => __( 'Manager', 'woocommerce' ),
				'permissions' => array(
					self::TAG_PROCESS_SALES           => self::ACCESS_ALLOW,
					self::TAG_APPLY_EXISTING_COUPONS  => self::ACCESS_ALLOW,
					self::TAG_VIEW_ORDERS             => self::ACCESS_ALLOW,
					self::TAG_CREATE_CUSTOM_DISCOUNTS => self::ACCESS_ALLOW,
					self::TAG_REFUND_ORDERS           => self::ACCESS_ALLOW,
					self::TAG_EXIT_POS                => self::ACCESS_ALLOW,
					self::TAG_VIEW_POS_SETTINGS       => self::ACCESS_ALLOW,
					self::TAG_EDIT_POS_SETTINGS       => self::ACCESS_APPROVAL_REQUIRED,
					self::TAG_MANAGE_POS_STAFF        => self::ACCESS_DENY,
					self::TAG_MANAGER_APPROVAL        => self::ACCESS_ALLOW,
				),
			),
			self::PROFILE_ADMIN => array(
				'name'        => __( 'POS Admin', 'woocommerce' ),
				'permissions' => array(
					self::TAG_PROCESS_SALES           => self::ACCESS_ALLOW,
					self::TAG_APPLY_EXISTING_COUPONS  => self::ACCESS_ALLOW,
					self::TAG_VIEW_ORDERS             => self::ACCESS_ALLOW,
					self::TAG_CREATE_CUSTOM_DISCOUNTS => self::ACCESS_ALLOW,
					self::TAG_REFUND_ORDERS           => self::ACCESS_ALLOW,
					self::TAG_EXIT_POS                => self::ACCESS_ALLOW,
					self::TAG_VIEW_POS_SETTINGS       => self::ACCESS_ALLOW,
					self::TAG_EDIT_POS_SETTINGS       => self::ACCESS_ALLOW,
					self::TAG_MANAGE_POS_STAFF        => self::ACCESS_ALLOW,
					self::TAG_MANAGER_APPROVAL        => self::ACCESS_ALLOW,
				),
			),
		);
	}
}
