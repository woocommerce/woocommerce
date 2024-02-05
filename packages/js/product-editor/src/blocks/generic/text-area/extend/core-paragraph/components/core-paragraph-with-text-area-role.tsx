/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import type { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import { useEntityProp } from '@wordpress/core-data';
import { BlockControls } from '@wordpress/block-editor';
import {
	// @ts-expect-error no exported member.
	type ComponentType,
	createElement,
	Fragment,
	useEffect,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import AligmentToolbarButton from '../../../toolbar/toolbar-button-alignment';
import type { TextAreaBlockEditAttributes } from '../../../types';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface ProductEditorBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly name: string;
	readonly context: Record< string, string >;
}
type WithTextAreaRoleBlockAttributes = BlockAttributes & {
	role?: 'product-editor/text-area-field';
	property: string;
};
type coreParagraphEditProps =
	ProductEditorBlockEditProps< WithTextAreaRoleBlockAttributes >;

type CoreParagrapahEditComponent = ComponentType< coreParagraphEditProps >;

const coreParagraphBlockEditChildTextArea =
	createHigherOrderComponent< CoreParagrapahEditComponent >(
		( BlockEdit: CoreParagrapahEditComponent ) => {
			return ( props: coreParagraphEditProps ) => {
				/*
				 * Check whether the property `product-editor/entity-prop`
				 * is set in the block context.
				 */
				const { context } = props;
				const entityProp = context?.[ 'product-editor/entity-prop' ];
				if ( ! entityProp ) {
					return <BlockEdit { ...props } />;
				}

				const { attributes, setAttributes } = props;
				const { content, align } = attributes;

				const [ propertyContent, setPropertyContent ] = useEntityProp(
					'postType',
					'product',
					entityProp
				);

				/*
				 * Populate initial content
				 * from the product entity to the block
				 */
				useEffect( () => {
					if ( ! propertyContent ) {
						return;
					}

					setAttributes( { content: propertyContent } );
				}, [ setAttributes ] ); // eslint-disable-line

				/*
				 * Update the product entity
				 * with the block content
				 */
				useEffect( () => {
					if ( ! content ) {
						return;
					}

					setPropertyContent( content );
				}, [ content, setPropertyContent ] );

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
						<BlockEdit { ...props } />;
					</>
				);
			};
		},
		'coreParagraphWithTextArea'
	);

export default coreParagraphBlockEditChildTextArea;
