/**
 * External dependencies
 */
import {
	BlockContextProvider,
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getAllowedBlocks } from '../shared/product-filters/utils/get-allowed-blocks';
import { getPriceFilterData } from './utils';
import { InitialDisabled } from '../shared/product-filters/components/initial-disabled';
import { useCollectionData } from '../shared/product-filters/hooks/use-collection-data';

const Edit = () => {
	const blockProps = useBlockProps();

	const { data, isLoading } = useCollectionData( {
		queryPrices: true,
		queryState: {},
		isEditor: true,
	} );

	return (
		<div { ...blockProps }>
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						'woocommerce/rangeInput': {
							...getPriceFilterData( data ),
							isLoading,
						},
					} }
				>
					<InnerBlocks
						allowedBlocks={ getAllowedBlocks() }
						template={ [
							[
								'core/heading',
								{
									level: 3,
									content: __( 'Price', 'woocommerce' ),
									style: {
										spacing: {
											margin: {
												bottom: '0.625rem',
												top: '0',
											},
										},
									},
								},
							],
							[ 'woocommerce/product-filter-price-slider', {} ],
						] }
					/>
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default Edit;
