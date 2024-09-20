/**
 * External dependencies
 */
import { Fragment, createElement } from '@wordpress/element';
import { BlockEditProps, TemplateArray, getBlockType } from '@wordpress/blocks';
import { Field } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';
import { __, sprintf } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ErrorBoundary } from './error-boundary';
import { ProductColumnWrapper } from './product-column-wrapper';

export type TemplateBlockAttributes = {
	_templateBlockId: string;
	_templateBlockOrder?: number;
	property?: string;
	label?: string;
};

const BLOCK_EXCEPTIONS = [ 'woocommerce/product-details-section-description' ];
const BLOCKS_THAT_REQUIRE_BLOCKTOOLS = [
	'woocommerce/product-description-field',
	'woocommerce/product-summary-field',
];

export function transpileBlockToDataformField(
	blockName: string,
	blockAttributes?: TemplateBlockAttributes,
	children?: TemplateArray,
	context?: { postType: string }
): Field< Product > | null {
	const block = getBlockType( blockName );
	if (
		! block ||
		! blockAttributes ||
		BLOCK_EXCEPTIONS.includes( blockName )
	) {
		return null;
	}
	let id = blockAttributes?._templateBlockId;
	if ( blockAttributes?.property ) {
		id = blockAttributes?.property;
	}
	return {
		id,
		label: blockAttributes?.label || block.title,
		render: ( { item: product }: { item: Product } ) => {
			if ( blockAttributes && blockAttributes.property ) {
				return (
					<>
						{ JSON.stringify(
							product[ blockAttributes.property ]
						) }
					</>
				);
			}
			return null;
		},
		Edit: () => {
			const BlockEdit = block.edit as
				| React.ComponentType<
						Partial<
							// eslint-disable-next-line @typescript-eslint/no-explicit-any
							BlockEditProps< Record< string, any > >
						> & {
							context?: Record< string, string >;
						}
				  >
				| undefined;
			const OptionalWrapper = BLOCKS_THAT_REQUIRE_BLOCKTOOLS.includes(
				block.name
			)
				? BlockTools
				: Fragment;
			return (
				<ErrorBoundary
					errorMessage={ sprintf(
						/* translators: %1$s: rating, %2$s: total number of stars */
						__( 'The %s failed to render.', 'woocommerce' ),
						block.name
					) }
				>
					{ blockName === 'core/columns' && children && (
						<ProductColumnWrapper
							columns={ children }
							context={ context }
						/>
					) }
					{ BlockEdit && blockName !== 'core/columns' && (
						<OptionalWrapper>
							<BlockEdit
								attributes={ blockAttributes || {} }
								context={ context }
							/>
						</OptionalWrapper>
					) }
				</ErrorBoundary>
			);
		},
	};
}
