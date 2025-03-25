/**
 * External dependencies
 */
import {
	Block,
	BlockConfiguration,
	BlockEditProps,
	registerBlockType,
} from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { evaluate } from '@woocommerce/expression-evaluation';
import { isWpVersion, getSetting } from '@woocommerce/settings';
import { ComponentType } from 'react';
import { useSelect } from '@wordpress/data';

// Define a more generic type for the select function to avoid TypeScript errors
type SelectType = ( store: string ) => Record< string, unknown >;

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Partial< BlockConfiguration< T > >;
}

type UseEvaluationContext = ( context: Record< string, unknown > ) => {
	getEvaluationContext: ( select: SelectType ) => Record< string, unknown >;
};

function defaultUseEvaluationContext( context: Record< string, unknown > ) {
	return {
		getEvaluationContext: () => context,
	};
}

function getEdit<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, object > = Record< string, object >
>(
	edit: ComponentType< BlockEditProps< T > >,
	useEvaluationContext: UseEvaluationContext
): ComponentType< BlockEditProps< T > > {
	return ( props ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore context is added to the block props by the block editor.
		const { context } = props;
		const {
			_templateBlockHideConditions: hideConditions,
			_templateBlockDisableConditions: disableConditions,
		} = props.attributes;

		const { getEvaluationContext } = useEvaluationContext( context );

		const { shouldHide, shouldDisable } = useSelect(
			( select: SelectType ) => {
				const evaluationContext = getEvaluationContext( select );

				return {
					shouldHide:
						hideConditions &&
						Array.isArray( hideConditions ) &&
						hideConditions.some( ( condition ) =>
							evaluate( condition.expression, evaluationContext )
						),
					shouldDisable:
						disableConditions &&
						Array.isArray( disableConditions ) &&
						disableConditions.some( ( condition ) =>
							evaluate( condition.expression, evaluationContext )
						),
				};
			},
			[ getEvaluationContext, hideConditions, disableConditions ]
		);

		if ( ! edit || shouldHide ) {
			return null;
		}

		return createElement( edit, {
			...props,
			attributes: {
				...props.attributes,
				disabled: props.attributes.disabled || shouldDisable,
			},
		} );
	};
}

let requiresExperimentalRole = isWpVersion( '6.7', '<' );
const adminSettings: { gutenberg_version?: string } = getSetting( 'admin' );
if ( requiresExperimentalRole && adminSettings.gutenberg_version ) {
	requiresExperimentalRole =
		parseFloat( adminSettings?.gutenberg_version ) < 19.4;
}

function augmentAttributes<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( attributes: T ) {
	// Note: If you modify this function, also update the server-side
	// Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry::augment_attributes() function.
	const augmentedAttributes = {
		...attributes,
		...{
			_templateBlockId: {
				type: 'string',
				role: 'content',
			},
			_templateBlockOrder: {
				type: 'integer',
				role: 'content',
			},
			_templateBlockHideConditions: {
				type: 'array',
				role: 'content',
			},
			_templateBlockDisableConditions: {
				type: 'array',
				role: 'content',
			},
			disabled: attributes.disabled || {
				type: 'boolean',
				role: 'content',
			},
		},
	};
	if ( requiresExperimentalRole ) {
		return Object.keys( augmentedAttributes ).reduce(
			( acc, key: keyof T ) => {
				if ( augmentedAttributes[ key ].role ) {
					acc[ key ] = {
						...augmentedAttributes[ key ],
						__experimentalRole: augmentedAttributes[ key ].role,
					};
				} else {
					acc[ key ] = augmentedAttributes[ key ];
				}
				return acc;
			},
			{} as T
		);
	}
	return augmentedAttributes;
}

/**
 * Function to register an individual block.
 *
 * @param block The block to be registered.
 * @return The block, if it has been successfully registered; otherwise `undefined`.
 */
export function registerWooBlockType<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>(
	block: BlockRepresentation< T >,
	useEvaluationContext?: UseEvaluationContext
): Block< T > | undefined {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	const { edit } = settings;

	if ( ! edit ) {
		return;
	}

	const augmentedMetadata = {
		...metadata,
		attributes: augmentAttributes( metadata.attributes ),
	};

	return registerBlockType< T >(
		{ name, ...augmentedMetadata },
		{
			...settings,
			edit: getEdit< T >(
				edit,
				useEvaluationContext ?? defaultUseEvaluationContext
			),
		}
	);
}
