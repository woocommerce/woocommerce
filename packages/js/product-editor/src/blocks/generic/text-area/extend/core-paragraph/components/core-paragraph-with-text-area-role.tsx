/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import { BaseControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import {
	// @ts-expect-error no exported member.
	ComponentType,
	createElement,
	useEffect,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Label } from '../../../../../../components/label/label';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface ProductEditorBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly name: string;
}
type WithTextAreaRoleBlockAttributes = BlockAttributes & {
	role?: 'product-editor/text-area-field';
	property: string;
};
type coreParagraphEditProps =
	ProductEditorBlockEditProps< WithTextAreaRoleBlockAttributes >;

type CoreParagrapahEditComponent = ComponentType< coreParagraphEditProps >;

const coreParagraphWithTextAreaRole =
	createHigherOrderComponent< CoreParagrapahEditComponent >(
		( BlockEdit: CoreParagrapahEditComponent ) => {
			return ( props: coreParagraphEditProps ) => {
				const { attributes, setAttributes, clientId } = props;
				const { role, label, help, required, note, property, content } =
					attributes;

				// Extend only when the role is set
				if ( role !== 'product-editor/text-area-field' ) {
					return <BlockEdit { ...props } />;
				}

				// Set core/paragraph block attributes
				useEffect( () => {
					setAttributes( {
						className:
							'wp-block-woocommerce-product-text-area-field',
					} );
				}, [ setAttributes ] );

				const [ propertyContent, setPropertyContent ] = useEntityProp(
					'postType',
					'product',
					property
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

				return (
					<BaseControl
						id={ clientId }
						label={
							<Label
								label={ label }
								required={ required }
								note={ note }
							/>
						}
						help={ help }
					>
						<BlockEdit { ...props } />
					</BaseControl>
				);
			};
		},
		'coreParagraphWithTextAreaRole'
	);

export default coreParagraphWithTextAreaRole;
