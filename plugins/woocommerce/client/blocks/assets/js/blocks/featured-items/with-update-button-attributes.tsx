/**
 * External dependencies
 */
import { useEffect, useMemo, useState } from '@wordpress/element';
import { WP_REST_API_Category } from 'wp-types';
import { ProductResponseItem } from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { EditorBlock } from './types';

interface WithUpdateButtonRequiredAttributes {
	editMode: boolean;
}

interface WithUpdateButtonAttributes< T > {
	attributes: WithUpdateButtonRequiredAttributes &
		EditorBlock< T >[ 'attributes' ];
}

interface WithUpdateButtonCategoryProps< T >
	extends WithUpdateButtonAttributes< T > {
	category: WP_REST_API_Category;
	product: never;
}

interface WithUpdateButtonProductProps< T >
	extends WithUpdateButtonAttributes< T > {
	category: never;
	product: ProductResponseItem;
}

type WithUpdateButtonProps< T extends EditorBlock< T > > =
	| ( T & WithUpdateButtonCategoryProps< T > )
	| ( T & WithUpdateButtonProductProps< T > );

export const withUpdateButtonAttributes =
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: WithUpdateButtonProps< T > ) => {
		const [ doUrlUpdate, setDoUrlUpdate ] = useState( false );
		const { attributes, category, clientId, product } = props;
		const item = category || product;

		const { editMode } = attributes;
		const permalink =
			( item as WP_REST_API_Category )?.link ||
			( item as ProductResponseItem )?.permalink;

		const block = useSelect(
			( select: any ) => {
				return select( 'core/block-editor' ).getBlock( clientId );
			},
			[ clientId ]
		);
		const findFirstButton = ( node?: any ): any | undefined => {
			if ( ! node ) return undefined;
			if (
				node.name === 'core/button' ||
				node.blockName === 'core/button'
			) {
				return node;
			}
			const children: any[] = node.innerBlocks || [];
			for ( const child of children ) {
				const found = findFirstButton( child );
				if ( found ) return found;
			}
			return undefined;
		};

		const innerRoot = block?.innerBlocks?.[ 0 ];
		const innerButton = findFirstButton( innerRoot );
		const buttonBlockId = innerButton?.clientId || '';
		const currentButtonAttributes = useMemo(
			() => innerButton?.attributes || {},
			[ innerButton ]
		);
		const { url } = currentButtonAttributes;

		const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

		useEffect( () => {
			if (
				doUrlUpdate &&
				buttonBlockId &&
				! editMode &&
				permalink &&
				url &&
				permalink !== url
			) {
				updateBlockAttributes( buttonBlockId, {
					url: permalink,
				} );
				setDoUrlUpdate( false );
			}
		}, [
			buttonBlockId,
			doUrlUpdate,
			editMode,
			permalink,
			updateBlockAttributes,
			url,
		] );

		const triggerUrlUpdate = () => setDoUrlUpdate( true );

		return <Component { ...props } triggerUrlUpdate={ triggerUrlUpdate } />;
	};
