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
} from '../../types';

export type ProductEntitySourceArgs = {
	/*
	 * The kind of entity to bind.
	 * Default is `postType`.
	 */
	kind?: string;

	/*
	 * The name of the entity to bind.
	 */
	name?: string;

	/*
	 * The name of the entity property to bind.
	 */
	prop: string;

	/*
	 * The ID of the entity to bind.
	 */
	id?: string;
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
			// Ensure the value is a string.
			if ( typeof nextEntityPropValue !== 'string' ) {
				return;
			}

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
	name: 'woo/entity',
	label: __( 'Product Entity', 'woocommerce' ),
	useSource,
	lockAttributesEditing: false,
} as BindingSourceHandlerProps< ProductEntitySourceArgs >;
