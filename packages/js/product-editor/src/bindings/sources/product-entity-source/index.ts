/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect, select, dispatch } from '@wordpress/data';
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

	// console.log( '(ProductEntitySource) entityPropName:', entityPropName ); // eslint-disable-line no-console
	// console.log( '(ProductEntitySource) entityPropValue:', entityPropValue ); // eslint-disable-line no-console

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

/**
 * Helper function to get the source property value.
 *
 * @param {BlockProps}              blockProps - The block props.
 * @param {ProductEntitySourceArgs} sourceArgs - The source args.
 * @return {Object} The source property value.
 */
async function getPropValue(
	blockProps: BlockProps,
	sourceArgs: ProductEntitySourceArgs
) {
	const { context } = blockProps;
	// @ts-expect-error There are no types for this.
	const { getEntityRecord, getEditedEntityRecord } = select( 'core' );

	const { prop: propertyName } = sourceArgs;

	// Post type
	let postType = context?.postType
		? context.postType
		: select( 'core/editor' ).getCurrentPostType();
	postType = postType || 'product';

	// Post ID
	const postId = Number(
		context?.postType
			? context.postId
			: select( 'core/editor' ).getCurrentPostId()
	);

	// Pick the entity record.
	const editedRecord = getEditedEntityRecord( 'postType', postType, postId );

	const record = getEntityRecord( 'postType', postType, postId );

	const propertyValue =
		record && editedRecord
			? {
					value: editedRecord[ propertyName ],
					fullValue: record[ propertyName ],
			  }
			: {
					value: '',
					fullValue: '',
			  };

	return propertyValue;
}

/**
 * Given a blockProps and sourceArgs, return a function that
 * updates the source property.
 *
 * @param {BlockProps}              blockProps - The block props.
 * @param {ProductEntitySourceArgs} sourceArgs - The source args.
 * @return {Function} The function that updates the source property.
 */
function updatePropHandler(
	blockProps: BlockProps,
	sourceArgs: ProductEntitySourceArgs
): ( newValue: string ) => void {
	const { context } = blockProps;
	// Post type
	let postType = context?.postType
		? context.postType
		: select( 'core/editor' ).getCurrentPostType();
	postType = postType || 'product';

	// Post ID
	const postId = Number(
		context?.postType
			? context.postId
			: select( 'core/editor' ).getCurrentPostId()
	);
	const { prop: propertyName } = sourceArgs;

	return function ( newValue: string ) {
		// @ts-expect-error There are no types for this.
		dispatch( 'core' ).editEntityRecord( 'postType', postType, postId, {
			[ propertyName ]: newValue,
		} );
	};
}

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
	/*
	 * Helper function to get the source property value.
	 */
	getPropValue,

	/*
	 * Helper function to update the source property value.
	 */
	updatePropHandler,

	lockAttributesEditing: false,
} as BindingSourceHandlerProps< ProductEntitySourceArgs >;
