/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'text',
	label: __( 'Date', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

const dateFormatter = new Intl.DateTimeFormat( undefined, {
	dateStyle: 'medium',
} );

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.date_created ?? '',
	render: ( { item } ) => {
		const value = item.date_created;

		if ( ! value ) {
			return <span>{ '—' }</span>;
		}

		const parsed = new Date( value );

		if ( Number.isNaN( parsed.getTime() ) ) {
			return <span>{ value }</span>;
		}

		return <span>{ dateFormatter.format( parsed ) }</span>;
	},
};
