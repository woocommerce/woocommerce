/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps, BlockAttributes } from '@wordpress/blocks';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface CoreBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly name: string;
	readonly context: Record< string, string >;
}

type SourceArgs = {
	/*
	 * The name of the entity property to bind.
	 */
	prop: string;
};

type UseSourceReturn = {
	/*
	 * The placeholder value for the source.
	 */
	placeholder: string | null;
	/*
	 * The value of the source.
	 */
	useValue: [ string, ( newValue: string ) => void ];
};

const useSource = (
	blockProps: CoreBlockEditProps< BlockAttributes >,
	sourceArgs: SourceArgs
): UseSourceReturn => {
	const { context } = blockProps;
	const { postType: contextPostType } = context;
	const { prop: entityPropName } = sourceArgs;

	const postType = useSelect(
		( select ) => {
			return contextPostType
				? contextPostType
				: select( 'core/editor' ).getCurrentPostType();
		},
		[ contextPostType ]
	);

	const [ entityPropValue, setEntityPropValue ] = useEntityProp< string >(
		'postType',
		postType,
		entityPropName
	);

	return {
		placeholder: null,
		useValue: [
			entityPropValue,
			( newValue: string ) => {
				setEntityPropValue( newValue );
			},
		],
	};
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
	useSource,
	lockAttributesEditing: false,
};
