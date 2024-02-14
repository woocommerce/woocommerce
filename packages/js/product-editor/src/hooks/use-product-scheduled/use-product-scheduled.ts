/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { isInTheFuture } from '@wordpress/date';

export function useProductScheduled( postType: string ) {
	const [ date ] = useEntityProp< string >(
		'postType',
		postType,
		'date_created'
	);

	return isInTheFuture( date );
}
