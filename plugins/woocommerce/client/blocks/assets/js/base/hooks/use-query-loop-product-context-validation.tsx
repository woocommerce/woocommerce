/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	Warning,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

interface QueryLoopProductContextValidation {
	hasInvalidContext: boolean;
	warningElement: JSX.Element;
}

/**
 * Hook that validates if a block is inside a Query Loop context with proper product post type.
 * Returns validation state and warning element if context is invalid.
 *
 * @param {Object} params           - The parameters object.
 * @param {string} params.clientId  - The client ID of the block.
 * @param {string} params.postType  - The current post type.
 * @param {string} params.blockName - The name of the block to display in warning.
 * @return {QueryLoopProductContextValidation} Object containing validation state and warning element.
 */
export const useQueryLoopProductContextValidation = ( {
	clientId,
	postType,
	blockName,
}: {
	clientId: string;
	postType: string;
	blockName: string;
} ): QueryLoopProductContextValidation => {
	const hasInvalidContext = useSelect(
		( select ) => {
			const queryLoopAncestors = select(
				blockEditorStore
			).getBlockParentsByBlockName( clientId, 'core/post-template' );
			return queryLoopAncestors.length > 0 && postType !== 'product';
		},
		[ clientId, postType ]
	);

	const warningElement = (
		<div { ...useBlockProps() }>
			<Warning>
				{ sprintf(
					/* translators: %s: block name */
					__(
						'The %s block requires a product context. When used in a Query Loop, the Query Loop must be configured to display products.',
						'woocommerce'
					),
					blockName
				) }
			</Warning>
		</div>
	);

	return {
		hasInvalidContext,
		warningElement,
	};
};
