/**
 * External dependencies
 */
import {
	Block,
	BlockConfiguration,
	BlockEditProps,
	registerBlockType,
} from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { evaluate } from '@woocommerce/expression-evaluation';
import { ComponentType } from 'react';

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Partial< BlockConfiguration< T > >;
}

function getEdit<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, object > = Record< string, object >
>(
	edit: ComponentType< BlockEditProps< T > >,
	useEvaluationContext: ( context: any ) => {
		getEvaluationContext: ( select: any ) => any;
	}
): ComponentType< BlockEditProps< T > > {
	return ( props ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore context is added to the block props by the block editor.
		const { context } = props;
		const { _templateBlockHideConditions: hideConditions } =
			props.attributes;

		const { getEvaluationContext } = useEvaluationContext( context );

		const shouldHide = useSelect(
			( select ) => {
				if ( ! hideConditions || ! Array.isArray( hideConditions ) ) {
					return false;
				}

				const evaluationContext = getEvaluationContext( select );

				return hideConditions.some( ( condition ) =>
					evaluate( condition.expression, evaluationContext )
				);
			},
			[ getEvaluationContext, hideConditions ]
		);

		if ( ! edit || shouldHide ) {
			return null;
		}

		return createElement( edit, props );
	};
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
	useEvaluationContext: ( context: any ) => {
		getEvaluationContext: ( select: any ) => any;
	}
): Block< T > | undefined {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	const { edit } = settings;

	if ( ! edit ) {
		return;
	}

	const templateBlockAttributes = {
		_templateBlockId: {
			type: 'string',
			__experimentalRole: 'content',
		},
		_templateBlockOrder: {
			type: 'integer',
			__experimentalRole: 'content',
		},
		_templateBlockHideConditions: {
			type: 'array',
			__experimentalRole: 'content',
		},
	};

	const augmentedMetadata = {
		...metadata,
		attributes: {
			...metadata.attributes,
			...templateBlockAttributes,
		},
	};

	return registerBlockType< T >(
		{ name, ...augmentedMetadata },
		{ ...settings, edit: getEdit< T >( edit, useEvaluationContext ) }
	);
}
