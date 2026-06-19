/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { InitialDisabled } from '../shared/product-filters/components/initial-disabled';
import { EXCLUDED_BLOCKS } from '../shared/product-filters/constants';
import { getAllowedBlocks } from '../shared/product-filters/utils/get-allowed-blocks';
import { filtersPreview } from './constants';
import type { RemovableItemsContext } from '../shared/product-filters/types';

const Edit = () => {
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
			template: [
				[ 'woocommerce/product-filter-removable-chips' ],
				[ 'woocommerce/product-filter-clear-button' ],
			],
		}
	);

	return (
		<div { ...innerBlocksProps }>
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						'woocommerce/removableItems': {
							items: filtersPreview,
							storeNamespace: 'woocommerce/product-filters',
						} satisfies RemovableItemsContext,
					} }
				>
					{ children }
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default Edit;
