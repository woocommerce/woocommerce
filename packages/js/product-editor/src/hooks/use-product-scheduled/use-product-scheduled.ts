/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { isInTheFuture, date as formatDate } from '@wordpress/date';
import { Product, ProductStatus } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { formatDatetime } from '../../utils';

export function useProductScheduled( postType: string ) {
	const [ productId ] = useEntityProp< string >( 'postType', postType, 'id' );

	const [ editedStatus, , prevStatus ] = useEntityProp< ProductStatus >(
		'postType',
		postType,
		'status'
	);

	const [ date ] = useEntityProp< string >(
		'postType',
		postType,
		'date_created_gmt'
	);

	const gmtDate = `${ date }+00:00`;

	// @ts-expect-error There are no types for this.
	const { editEntityRecord } = useDispatch( 'core' );

	return {
		isScheduled: editedStatus === 'future' || isInTheFuture( gmtDate ),
		date: gmtDate,
		formattedDate: formatDatetime( gmtDate ),
		async editDate( value: string ) {
			let status = prevStatus;
			if ( isInTheFuture( value ) ) {
				status = 'future';
			} else if ( prevStatus === 'future' ) {
				status = 'publish';
			}

			await editEntityRecord( 'postType', postType, productId, {
				status,
				date_created_gmt: formatDate( 'c', value, 'GMT' ),
			} satisfies Partial< Product > );
		},
	};
}
