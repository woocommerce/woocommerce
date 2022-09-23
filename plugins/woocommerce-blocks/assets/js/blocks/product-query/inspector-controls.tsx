/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { EditorBlock } from '@woocommerce/types';
import { ElementType } from 'react';

/**
 * Internal dependencies
 */
import { ProductQueryBlock } from './types';
import {
	isWooQueryBlockVariation,
	setCustomQueryAttribute,
	useAllowedControls,
} from './utils';

export const INSPECTOR_CONTROLS = {
	onSale: ( props: ProductQueryBlock ) => (
		<ToggleControl
			label={ __(
				'Show only products on sale',
				'woo-gutenberg-products-block'
			) }
			checked={ props.attributes.query.__woocommerceOnSale || false }
			onChange={ ( __woocommerceOnSale ) => {
				setCustomQueryAttribute( props, { __woocommerceOnSale } );
			} }
		/>
	),
};

export const withProductQueryControls =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props: ProductQueryBlock ) => {
		const allowedControls = useAllowedControls( props.attributes );
		return isWooQueryBlockVariation( props ) ? (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					{ Object.entries( INSPECTOR_CONTROLS ).map(
						( [ key, Control ] ) =>
							allowedControls?.includes( key ) ? (
								<Control { ...props } />
							) : null
					) }
				</InspectorControls>
			</>
		) : (
			<BlockEdit { ...props } />
		);
	};

addFilter( 'editor.BlockEdit', 'core/query', withProductQueryControls );
