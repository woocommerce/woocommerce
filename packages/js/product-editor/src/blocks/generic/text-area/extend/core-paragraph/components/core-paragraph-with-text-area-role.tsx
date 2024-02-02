/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import {
	// @ts-expect-error no exported member.
	ComponentType,
	createElement,
	useEffect,
} from '@wordpress/element';
/**
 * Internal dependencies
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface ProductEditorBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly name: string;
}
type WithTextAreaRoleBlockAttributes = BlockAttributes & {
	role?: 'product-editor/text-area-field';
};
type coreParagraphEditProps =
	ProductEditorBlockEditProps< WithTextAreaRoleBlockAttributes >;

type CoreParagrapahEditComponent = ComponentType< coreParagraphEditProps >;

const coreParagraphWithTextAreaRole =
	createHigherOrderComponent< CoreParagrapahEditComponent >(
		( BlockEdit: CoreParagrapahEditComponent ) => {
			return ( props: coreParagraphEditProps ) => {
				const { attributes, setAttributes } = props;

				const { role } = attributes;

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

				return <BlockEdit { ...props } />;
			};
		},
		'coreParagraphWithTextAreaRole'
	);

export default coreParagraphWithTextAreaRole;
