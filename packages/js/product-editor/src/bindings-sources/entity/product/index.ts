/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type {
	AttributeBindingProps,
	BindingSourceHandlerProps,
	BindingUseSourceProps,
	BlockProps,
	BoundBlockAttributes,
} from '../../../bindings/types';
import { WooEntitySourceArgs } from './types';

/**
 * Giving the block attributes,
 * return the list of source props
 *
 * @param {BoundBlockAttributes} attributes - The block attributes.
 * @return {string[]} The list of source props.
 */
export function getBlockBoundSourePropsList(
	attributes: BoundBlockAttributes
): string[] {
	const bindings = attributes?.metadata?.bindings;
	if ( ! bindings ) {
		return [];
	}

	return Object.values( bindings ).map(
		( binding: AttributeBindingProps ) => binding.args?.prop
	);
}

/**
 * React custom hook to bind a source to a block.
 *
 * @param {BlockProps}          blockProps - The block props.
 * @param {WooEntitySourceArgs} sourceArgs - The source args.
 * @return {BindingUseSourceProps} The source value and setter.
 */
const useSource = (
	blockProps: BlockProps,
	sourceArgs: WooEntitySourceArgs
): BindingUseSourceProps => {
	if ( typeof sourceArgs === 'undefined' ) {
		throw new Error( 'The "args" argument is required.' );
	}

	if ( ! sourceArgs?.prop ) {
		throw new Error( 'The "prop" argument is required.' );
	}

	const { prop, id } = sourceArgs;

	const [ value, updateValue ] = useEntityProp(
		'postType',
		'product',
		prop,
		id
	);

	const updateValueHandler = useCallback(
		( nextEntityPropValue: string ) => {
			updateValue( nextEntityPropValue );
		},
		[ updateValue ]
	);

	return {
		placeholder: null,
		value,
		updateValue: updateValueHandler,
	};
};

/*
 * Create the product-entity
 * block binding source handler.
 *
 * source ID: `woocommerce/entity/product`
 * args:
 * - prop: The name of the entity property to bind.
 *
 * In the example below,
 * the `content` attribute is bound to the `short_description` property.
 * `product` entity and `postType` kind are defined by the context.
 *
 * ```
 * metadata: {
 *   bindings: {
 *     content: {
 *       source: 'woocommerce/entity/product',
 *       args: {
 *         prop: 'short_description',
 *       },
 *    },
 * },
 * ```
 */
export default {
	name: 'woocommerce/entity/product',
	label: __( 'Product Entity', 'woocommerce' ),
	useSource,
	lockAttributesEditing: true,
} as BindingSourceHandlerProps< WooEntitySourceArgs >;
