/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../../../../store/product-editor-ai';
import type {
	DescriptionBlockEditComponent,
	DescriptionBlockEditProps,
} from '../types';

const wooBlockwithFullEditorToolbarButton =
	createHigherOrderComponent< DescriptionBlockEditComponent >(
		( BlockEdit: DescriptionBlockEditComponent ) => {
			return ( props: DescriptionBlockEditProps ) => {
				const { openModalEditor } = useDispatch( store );

				// Only extend summary field block instances
				if ( props?.name !== 'woocommerce/product-summary-field' ) {
					return <BlockEdit { ...props } />;
				}

				// Only add the `Full editor` button when the block is selected
				if ( ! props?.isSelected ) {
					return <BlockEdit { ...props } />;
				}

				/*
				 * Extend the toolbar only to the sumary field block instance
				 * that has the `woocommerce/product-description-field__content` template block ID.
				 */
				if (
					props?.attributes?._templateBlockId !==
					'product-description__content'
				) {
					return <BlockEdit { ...props } />;
				}

				const blockControlProps = { group: 'other' };

				return (
					<>
						<BlockControls { ...blockControlProps }>
							<ToolbarButton
								label={ __(
									'Edit Product description',
									'woocommerce'
								) }
								onClick={ () => {
									recordEvent(
										'product_add_description_click'
									);
									openModalEditor();
								} }
							>
								{ __( 'Full editor', 'woocommerce' ) }
							</ToolbarButton>
						</BlockControls>
						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'wooBlockwithFullEditorToolbarButton'
	);

export default wooBlockwithFullEditorToolbarButton;
