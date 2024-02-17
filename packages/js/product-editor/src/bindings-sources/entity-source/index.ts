/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type {
	BindingSourceHandlerProps,
	BindingUseSourceProps,
	BlockProps,
} from '../../bindings/types';
import { WooEntitySourceArgs } from './types';

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

	const { context } = blockProps;
	const { kind = 'postType', name: nameFromArgs, prop, id } = sourceArgs;

	const { postType: nameFromContext } = context;

	/*
	 * Entity prop name:
	 * - If `name` is provided in the source args, use it.
	 * - If `name` is not provided in the source args, use the `postType` from the context.
	 * - Otherwise, try to get the current post type from the editor store.
	 */
	const name = useSelect(
		( select ) => {
			if ( nameFromArgs ) {
				return nameFromArgs;
			}

			if ( nameFromContext ) {
				return nameFromContext;
			}

			return select( 'core/editor' ).getCurrentPostType();
		},
		[ nameFromContext, nameFromArgs ]
	);

	const [ value, updateValue ] = useEntityProp( kind, name, prop, id );

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
 * source ID: `woo/entity`
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
 *       source: 'woo/entity',
 *       args: {
 *         prop: 'short_description',
 *       },
 *    },
 * },
 * ```
 */
export default {
	name: 'woo/entity',
	label: __( 'Product Entity', 'woocommerce' ),
	useSource,
	lockAttributesEditing: true,
} as BindingSourceHandlerProps< WooEntitySourceArgs >;
