<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use Automattic\WooCommerce\Internal\ProductFilters\Params;

/**
 * Builds the `scopeName` of a product scope in the `woocommerce` Interactivity API store.
 *
 * The client keys a scope's unsaved input by its name, so the same place has to get the same
 * name on every render of its page, including renders caused by a client-side navigation.
 *
 * @internal Experimental API, not for use by extensions. It has no backwards-compatibility
 *           guarantee and may change or be removed in any release.
 */
final class ProductScopes {

	/**
	 * The name of the global scope: the name prefix alone, which is what a call with neither a
	 * form name nor the page composes. The client store uses it for a scope that declares no `scopeName`.
	 */
	public const GLOBAL_SCOPE_NAME = self::NAME_PREFIX;

	/**
	 * Start of every name. On its own it is the global scope's name.
	 */
	private const NAME_PREFIX = 'wc:';

	/**
	 * Separates the form name, which may be empty, from the page hash. Its character is outside the
	 * form-name alphabet and the prefix, so a per-page name never equals an every-page name.
	 */
	private const PAGE_SEPARATOR = '@';

	/**
	 * Query variables that change the view of a page, not what the page is: pagination and sorting.
	 */
	private const VIEW_QUERY_VARS = array( 'paged', 'page', 'cpage', 'orderby', 'order' );

	/**
	 * Returns the scope name to declare as the `scopeName` of a `woocommerce` context.
	 *
	 * The page is the public query variables WordPress resolved for the request, without
	 * pagination, sorting and product-filter parameters, so a client-side navigation within
	 * a page keeps its names. Making form names unique is up to the caller.
	 *
	 * @param string|int|null $form_name Letters, digits, `-`, `_` and `/`, without regard to case,
	 *                                   with surrounding spaces ignored. `null`, an empty string and
	 *                                   spaces only mean no form name. Any other type or character is rejected.
	 * @param bool            $per_page  True: a name for this page only. False: a name for every page.
	 * @return string|null The prefix, then the form name if any, then the separator and the page hash when
	 *                     `$per_page` is true. With neither a form name nor the page that is `GLOBAL_SCOPE_NAME`.
	 *                     Null when the form name is rejected, which callers must not declare.
	 */
	public static function get_scope_name( $form_name = null, bool $per_page = true ): ?string {
		if ( is_int( $form_name ) ) {
			$form_name = (string) $form_name;
		}

		if ( null === $form_name ) {
			$form_name = '';
		}

		if ( ! is_string( $form_name ) ) {
			return self::reject_form_name();
		}

		$form_name = trim( $form_name, ' ' );

		if ( '' !== $form_name && 1 !== preg_match( '/\A[A-Za-z0-9_\/-]+\z/', $form_name ) ) {
			return self::reject_form_name();
		}

		$name = self::NAME_PREFIX . strtolower( $form_name );

		if ( $per_page ) {
			$name .= self::PAGE_SEPARATOR . wp_unique_id_from_values( array( 'page' => self::get_page() ) );
		}

		return $name;
	}

	/**
	 * Reports a rejected form name.
	 *
	 * @return null Always null, so callers return it as the result.
	 */
	private static function reject_form_name() {
		wc_doing_it_wrong(
			self::class . '::get_scope_name',
			'The form name of a product scope must be a string or an integer made of letters, digits, "-", "_" and "/".',
			'11.3.0'
		);

		return null;
	}

	/**
	 * Returns the page of the current request: its public query variables without pagination,
	 * sorting and product-filter parameters, ordered by name.
	 *
	 * The product-filter parameters are read only when the request has query variables to filter.
	 * That is after WordPress parsed the request, which has already read them, so this never is
	 * the first read of that cached map.
	 *
	 * @return array Query variable names mapped to their values; empty before WordPress parses a request.
	 */
	private static function get_page(): array {
		global $wp;

		if ( ! isset( $wp->query_vars, $wp->public_query_vars ) || ! is_array( $wp->query_vars ) || ! is_array( $wp->public_query_vars ) ) {
			return array();
		}

		$page = array_intersect_key( $wp->query_vars, array_flip( $wp->public_query_vars ) );
		$page = array_diff_key( $page, array_flip( self::VIEW_QUERY_VARS ) );

		if ( empty( $page ) ) {
			return array();
		}

		$page = array_diff_key( $page, array_flip( wc_get_container()->get( Params::class )->get_param_keys() ) );

		return self::sort_by_key( $page );
	}

	/**
	 * Sorts an array by key, at every level, so equal maps hash alike whatever order they were built in.
	 *
	 * @param array $values The array to sort.
	 * @return array The sorted array.
	 */
	private static function sort_by_key( array $values ): array {
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$values[ $key ] = self::sort_by_key( $value );
			}
		}

		ksort( $values );

		return $values;
	}
}
