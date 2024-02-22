/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { getDate, isInTheFuture, date as parseDate } from '@wordpress/date';
import { Product, ProductStatus } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { formatScheduleDatetime, getSiteDatetime } from '../../utils';

export const TIMEZONELESS_FORMAT = 'Y-m-d\\TH:i:s';

export function useProductScheduled( postType: string ) {
	const [ productId ] = useEntityProp< number >( 'postType', postType, 'id' );

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

	// @ts-expect-error There are no types for this.
	const { editEntityRecord } = useDispatch( 'core' );

	async function schedule( value?: string ) {
		const siteDate = getDate( value ?? null );
		const newGmtDate = parseDate( TIMEZONELESS_FORMAT, siteDate, 'GMT' );

		let status = prevStatus;
		if ( isInTheFuture( siteDate.toISOString() ) ) {
			status = 'future';
		} else if ( prevStatus === 'future' ) {
			status = 'publish';
		}

		await editEntityRecord( 'postType', postType, productId, {
			status,
			date_created_gmt: newGmtDate,
		} satisfies Partial< Product > );
	}

	return {
		isScheduled: editedStatus === 'future' || isInTheFuture( siteDate ),
		date: siteDate,
		formattedDate: formatScheduleDatetime( gmtDate ),
		schedule,
	};
}
