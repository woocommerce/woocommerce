/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { CheckboxControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CatalogVisibilityBlockAttributes } from './types';

export function Edit( {
	attributes,
}: {
	attributes: CatalogVisibilityBlockAttributes;
} ) {
	const { label, visibilty } = attributes;

	const blockProps = useBlockProps();

	const [ catalogVisibility, setCatalogVisibility ] = useEntityProp<
		Product[ 'catalog_visibility' ]
	>( 'postType', 'product', 'catalog_visibility' );

	const checked =
		catalogVisibility === visibilty || catalogVisibility === 'hidden';

	function handleChange( selected: boolean ) {
		if ( selected ) {
			if ( catalogVisibility === 'visible' ) {
				setCatalogVisibility( visibilty );
				return;
			}
			setCatalogVisibility( 'hidden' );
		} else {
			if ( catalogVisibility === 'hidden' ) {
				if ( visibilty === 'catalog' ) {
					setCatalogVisibility( 'search' );
					return;
				}
				if ( visibilty === 'search' ) {
					setCatalogVisibility( 'catalog' );
					return;
				}
				return;
			}
			setCatalogVisibility( 'visible' );
		}
	}

	return (
		<div { ...blockProps }>
			<CheckboxControl
				label={ label }
				checked={ checked }
				onChange={ handleChange }
			/>
		</div>
	);
}
