/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import type {
	BindingSourceHandlerProps,
	BindingUseSourceProps,
	BlockProps,
} from '../../types';

export type ProductEntitySourceArgs = {
	/*
	 * The name of the entity property to bind.
	 */
	prop: string;
};

/**
 * React custom hook to bind a source to a block.
 *
 * @param {BlockProps}              blockProps - The block props.
 * @param {ProductEntitySourceArgs} sourceArgs - The source args.
 * @return {BindingUseSourceProps} The source value and setter.
 */
const useSource = (
	blockProps: BlockProps,
	sourceArgs: ProductEntitySourceArgs
): BindingUseSourceProps => {
	const { context } = blockProps;
	const { postType: contextPostType } = context;
	const { prop: entityPropName } = sourceArgs;

	const postType = useSelect(
		( privateSelect ) => {
			return contextPostType
				? contextPostType
				: privateSelect( 'core/editor' ).getCurrentPostType();
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
} as BindingSourceHandlerProps< ProductEntitySourceArgs >;
