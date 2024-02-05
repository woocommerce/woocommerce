/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps, BlockAttributes } from '@wordpress/blocks';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface CoreBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly name: string;
	readonly context: Record< string, string >;
}

type SourceAttributes = {
	/*
	 * The name of the entity property to bind.
	 */
	prop: string;
};

/*
 * Create the product-entity
 * block binding source handler.
 *
 * source ID:
 * `woo/product-entity`
 * args:
 * - prop: The name of the entity property to bind.
 *
 * example:
 * ```
 * metadata: {
 *   bindings: {
 *     content: {
 *       source: 'woo/product-entity',
 *       args: {
 *         prop: 'short_description',
 *       },
 *    },
 * },
 * ```
 */
export default {
	name: 'woo/product-entity',
	label: __( 'Product Entity', 'woocommerce' ),
	useSource(
		props: CoreBlockEditProps< BlockAttributes >,
		sourceAttributes: SourceAttributes
	) {
		const { context } = props;
		const { postType: contextPostType } = context;
		const { prop: entityPropName } = sourceAttributes;

		const postType = useSelect(
			( select ) => {
				return contextPostType
					? contextPostType
					: select( 'core/editor' ).getCurrentPostType();
			},
			[ contextPostType ]
		);

		const [ entityPropValue ] = useEntityProp(
			'postType',
			postType,
			entityPropName
		);

		/*
		 * Set a noop function for now.
		 * Let's ensure core handles the setting of the entity prop value.
		 * @see https://github.com/WordPress/gutenberg/blob/f38eb429b8ba5153c50fabad3367f94c3289746d/packages/block-editor/src/hooks/use-bindings-attributes.js#L51
		 */
		const setEntityPropValue = () => {};

		return {
			placeholder: entityPropName,
			useValue: [ entityPropValue, setEntityPropValue ],
		};
	},
	lockAttributesEditing: false,
};
