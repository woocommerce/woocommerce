/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { createElement, useMemo } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { DisplayState } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Condition, evaluateCondition } from '../../utils/evaluate-condition';

export function Edit( {
	attributes,
	context,
}: {
	attributes: BlockAttributes & {
		mustMatch: Record< string, Array< string > >;
	};
	context: {
		[ key: string ]: unknown;
	};
} ) {
	const blockProps = useBlockProps();
	const { conditions } = attributes;

	const productId = useEntityId( 'postType', 'product' );
	const product: Product = useSelect( ( select ) =>
		select( 'core' ).getEditedEntityRecord(
			'postType',
			'product',
			productId
		)
	);

	const displayBlocks = useMemo( () => {
		const result = conditions.reduce(
			( evaluated: boolean, condition: Condition ) => {
				if ( ! evaluated ) {
					return false;
				}
				return evaluateCondition( context, condition );
			},
			true
		);

		return result;
	}, [ conditions, product ] );

	return (
		<div { ...blockProps }>
			<DisplayState
				state={ displayBlocks ? 'visible' : 'visually-hidden' }
			>
				<InnerBlocks templateLock="all" />
			</DisplayState>
		</div>
	);
}
