/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => {
		if ( ! item.downloadable ) {
			return <span>{ __( 'Not downloadable', 'woocommerce' ) }</span>;
		}

		const downloads = Array.isArray( item.downloads ) ? item.downloads : [];
		const count = downloads.length;

		if ( count === 0 ) {
			return <span>{ __( 'No files', 'woocommerce' ) }</span>;
		}

		const label = sprintf(
			/* translators: %d: number of downloadable files */
			_n( '%d file', '%d files', count, 'woocommerce' ),
			count
		);

		return <span>{ label }</span>;
	},
};
