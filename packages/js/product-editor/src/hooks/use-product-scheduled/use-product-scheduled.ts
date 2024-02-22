/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { getDate, isInTheFuture, date as parseDate } from '@wordpress/date';
import { Product, ProductStatus, ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { formatScheduleDatetime, getSiteDatetime } from '../../utils';

export const TIMEZONELESS_FORMAT = 'Y-m-d\\TH:i:s';

export function useProductScheduled( postType: string ) {
	const [ date ] = useEntityProp< string >(
		'postType',
		postType,
		'date_created_gmt'
	);

	const [ editedStatus, , prevStatus ] = useEntityProp< ProductStatus >(
		'postType',
		postType,
		'status'
	);

	const gmtDate = `${ date }+00:00`;

	const siteDate = getSiteDatetime( gmtDate );

	async function schedule(
		publish: (
			productOrVariation?: Partial< Product | ProductVariation >
		) => Promise< Product | ProductVariation | undefined >,
		value?: string
	) {
		const newSiteDate = getDate( value ?? null );
		const newGmtDate = parseDate( TIMEZONELESS_FORMAT, newSiteDate, 'GMT' );

		let status = prevStatus;
		if ( isInTheFuture( newSiteDate.toISOString() ) ) {
			status = 'future';
		} else if ( prevStatus === 'future' ) {
			status = 'publish';
		}

		return publish( {
			status,
			date_created_gmt: newGmtDate,
		} );
	}

	return {
		isScheduled: editedStatus === 'future' || isInTheFuture( siteDate ),
		date: siteDate,
		formattedDate: formatScheduleDatetime( gmtDate ),
		schedule,
	};
}
