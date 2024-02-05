/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls } from '@wordpress/block-editor';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AligmentToolbarButton from '../../../toolbar/toolbar-button-alignment';
import type {
	ConnectedBlockEditComponent,
	ConnectedBlockEditInstance,
	TextAreaBlockEditAttributes,
} from '../../../types';

const coreParagraphBlockEditWithTextareaToolbar =
	createHigherOrderComponent< ConnectedBlockEditComponent >(
		( BlockEdit: ConnectedBlockEditComponent ) => {
			return ( props: ConnectedBlockEditInstance ) => {
				const { attributes, setAttributes } = props;
				const { align } = attributes;

				function setAlignment(
					value: TextAreaBlockEditAttributes[ 'align' ]
				) {
					setAttributes( { align: value } );
				}

				const blockControlsBlockProps = { group: 'block' };

				return (
					<>
						<BlockControls { ...blockControlsBlockProps }>
							<AligmentToolbarButton
								align={ align }
								setAlignment={ setAlignment }
							/>
						</BlockControls>
						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'coreParagraphBlockEditWithTextareaToolbar'
	);

export default coreParagraphBlockEditWithTextareaToolbar;
