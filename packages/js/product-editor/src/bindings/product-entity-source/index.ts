/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect, select, dispatch } from '@wordpress/data';
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
		( privateSelect ) => {
			return contextPostType
				? contextPostType
				: privateSelect( 'core/editor' ).getCurrentPostType();
		},
		[ contextPostType ]
	);

	// console.log( { postType } );
	// console.log( { entityPropName } );

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

function getSourcePropValue(
	blockProps: CoreBlockEditProps< BlockAttributes >,
	sourceArgs: SourceArgs
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
 * @param {CoreBlockEditProps<BlockAttributes>} blockProps - The block props.
 * @param {SourceArgs}                          sourceArgs - The source args.
 * @return {Function} The function that updates the source property.
 */
function updateSourcePropHandler(
	blockProps: CoreBlockEditProps< BlockAttributes >,
	sourceArgs: SourceArgs
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
	getSourcePropValue,
	updateSourcePropHandler,
	lockAttributesEditing: false,
};
