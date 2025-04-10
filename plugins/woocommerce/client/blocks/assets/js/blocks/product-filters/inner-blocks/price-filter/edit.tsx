/**
 * External dependencies
 */
import {
	BlockContextProvider,
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { useCollectionData } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getAllowedBlocks } from '../../utils/get-allowed-blocks';
import { getPriceFilterData } from './utils';
import { InitialDisabled } from '../../components/initial-disabled';

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
						filterData: {
							price: getPriceFilterData( data ),
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
