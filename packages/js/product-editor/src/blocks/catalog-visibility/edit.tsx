/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { CheckboxControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	CatalogVisibilityBlockAttributes,
	ProductCatalogVisibility,
} from './types';

export function Edit( {
	attributes,
}: {
	attributes: CatalogVisibilityBlockAttributes;
} ) {
	const { label } = attributes;

	const blockProps = useBlockProps();

	const [ catalogVisibility, setCatalogVisibility ] =
		useEntityProp< ProductCatalogVisibility >(
			'postType',
			'product',
			'catalog_visibility'
		);

	const checked =
		catalogVisibility === 'catalog' || catalogVisibility === 'hidden';

	function handleChange( selected: boolean ) {
		if ( selected ) {
			if ( catalogVisibility === 'search' ) {
				setCatalogVisibility( 'hidden' );
			} else {
				setCatalogVisibility( 'catalog' );
			}
		} else if ( catalogVisibility === 'hidden' ) {
			setCatalogVisibility( 'search' );
		} else {
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
