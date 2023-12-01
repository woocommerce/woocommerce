/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type {
	DescriptionBlockEditComponent,
	DescriptionBlockEditProps,
} from '../types';
import FullEditorToolbarButton from './full-editor-toolbar-button';

const wooBlockwithFullEditorToolbarButton =
	createHigherOrderComponent< DescriptionBlockEditComponent >(
		( BlockEdit: DescriptionBlockEditComponent ) => {
			return ( props: DescriptionBlockEditProps ) => {
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
							<FullEditorToolbarButton />
						</BlockControls>
						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'wooBlockwithFullEditorToolbarButton'
	);

export default wooBlockwithFullEditorToolbarButton;
