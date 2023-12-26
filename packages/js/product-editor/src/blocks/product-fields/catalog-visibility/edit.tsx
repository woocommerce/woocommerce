/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CatalogVisibilityBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< CatalogVisibilityBlockAttributes > ) {
	const { label, visibility } = attributes;

	const blockProps = useWooBlockProps( attributes );

	const [ catalogVisibility, setCatalogVisibility ] = useEntityProp<
		Product[ 'catalog_visibility' ]
	>( 'postType', 'product', 'catalog_visibility' );

	const checked =
		catalogVisibility === visibility || catalogVisibility === 'hidden';

	function handleChange( selected: boolean ) {
		if ( selected ) {
			if ( catalogVisibility === 'visible' ) {
				setCatalogVisibility( visibility );
				return;
			}
			setCatalogVisibility( 'hidden' );
		} else {
			if ( catalogVisibility === 'hidden' ) {
				if ( visibility === 'catalog' ) {
					setCatalogVisibility( 'search' );
					return;
				}
				if ( visibility === 'search' ) {
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
