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
 * Validates whether a block inside a Query Loop has product context.
 *
 * @param params           Parameters object.
 * @param params.clientId  The client ID of the block.
 * @param params.postType  The current post type.
 * @param params.blockName The block name to display in the warning.
 * @return Validation state and warning element.
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
