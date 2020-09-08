/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import ProductControl from '@woocommerce/block-components/product-control';
import { Placeholder, Button, Toolbar } from '@wordpress/components';
import { BlockControls } from '@wordpress/block-editor';
import TextToolbarButton from '@woocommerce/block-components/text-toolbar-button';
import { useProductDataContext } from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import './editor.scss';

/**
 * This HOC shows a product selection interface if context is not present in the editor.
 *
 * @param {Object} selectorArgs Options for the selector.
 *
 */
const withProductSelector = ( selectorArgs ) => ( OriginalComponent ) => {
	return ( props ) => {
		const productDataContext = useProductDataContext();
		const { attributes, setAttributes } = props;
		const { productId } = attributes;
		const [ isEditing, setIsEditing ] = useState( ! productId );

		if ( productDataContext.hasContext ) {
			return <OriginalComponent { ...props } />;
		}

		return (
			<>
				{ isEditing ? (
					<Placeholder
						icon={ selectorArgs.icon || '' }
						label={ selectorArgs.label || '' }
						className="wc-atomic-blocks-product"
					>
						{ !! selectorArgs.description && (
							<div>{ selectorArgs.description }</div>
						) }
						<div className="wc-atomic-blocks-product__selection">
							<ProductControl
								selected={ productId || 0 }
								showVariations
								onChange={ ( value = [] ) => {
									setAttributes( {
										productId: value[ 0 ]
											? value[ 0 ].id
											: 0,
									} );
								} }
							/>
							<Button
								isDefault
								disabled={ ! productId }
								onClick={ () => {
									setIsEditing( false );
								} }
							>
								{ __( 'Done', 'woocommerce' ) }
							</Button>
						</div>
					</Placeholder>
				) : (
					<>
						<BlockControls>
							<Toolbar>
								<TextToolbarButton
									onClick={ () => setIsEditing( true ) }
								>
									{ __(
										'Switch product…',
										'woocommerce'
									) }
								</TextToolbarButton>
							</Toolbar>
						</BlockControls>
						<OriginalComponent { ...props } />
					</>
				) }
			</>
		);
	};
};

export default withProductSelector;
