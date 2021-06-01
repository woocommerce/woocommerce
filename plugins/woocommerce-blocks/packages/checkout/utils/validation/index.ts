/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Checks if value passed contain passed label.
 */
export const mustContain = ( value: string, label: string ): true | Error => {
	if ( ! value.includes( label ) ) {
		throw Error(
			sprintf(
				/* translators: %1$s value passed to filter, %2$s : value that must be included. */
				__(
					'Returned value must include %1$s, you passed "%2$s"',
					'woo-gutenberg-products-block'
				),
				value,
				label
			)
		);
	}
	return true;
};

/**
 * A function that always return true.
 * We need to have a single instance of this function so it doesn't
 * invalidate our memo comparison.
 */
export const returnTrue = (): true => true;
