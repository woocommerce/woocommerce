/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { useCollection } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { formatQuery, getQueryParams } from './utils';
import type { EditProps } from './type';

const Edit = ( { clientId, setAttributes, context }: EditProps ) => {
	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( blockProps );

	// Get inner blocks by clientId
	const currentBlock = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlock( clientId );
	} );

	const { results } = useCollection( {
		namespace: '/wc/store/v1',
		resourceName: 'products/collection-data',
		query: {
			...formatQuery( context.query ),
			...getQueryParams( currentBlock ),
		},
	} );

	useEffect( () => {
		setAttributes( {
			collectionData: results,
		} );
	}, [ results, setAttributes ] );

	return <nav { ...innerBlockProps } />;
};

export default Edit;
