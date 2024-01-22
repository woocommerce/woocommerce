/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import debugFactory from 'debug';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { BlockControls } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import ai from '../../../icons/ai';
import {
	ProductTitleBlockEditProps,
	ProductTitleBlockEditComponent,
} from '../../../types';

const debug = debugFactory( 'woo-ai:product-editor:name-field' );

const productNameFieldWithAi =
	createHigherOrderComponent< ProductTitleBlockEditComponent >(
		( BlockEdit: ProductTitleBlockEditComponent ) => {
			return ( props: ProductTitleBlockEditProps ) => {
				// Only extend summary field block instances
				if ( props?.name !== 'woocommerce/product-name-field' ) {
					return <BlockEdit { ...props } />;
				}

				// Only add the `Full editor` button when the block is selected
				if ( ! props?.isSelected ) {
					return <BlockEdit { ...props } />;
				}

				debug( 'Extending product name field block' );

				const blockControlProps = { group: 'other' };

				return (
					<>
						<BlockControls { ...blockControlProps }>
							<ToolbarButton
								icon={ ai }
								label={ __(
									'Get AI suggestions for product name',
									'woocommerce'
								) }
								onClick={ console.log }
							>
								{ __( 'Get title suggestions', 'woocommerce' ) }
							</ToolbarButton>
						</BlockControls>

						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'productNameFieldWithAi'
	);

export default productNameFieldWithAi;
