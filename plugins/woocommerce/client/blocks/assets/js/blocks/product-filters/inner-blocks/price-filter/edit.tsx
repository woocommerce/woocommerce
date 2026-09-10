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
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { getAllowedBlocks } from '../../utils/get-allowed-blocks';
import { getPriceFilterData } from './utils';
import { InitialDisabled } from '../../components/initial-disabled';
import { getProductCollectionQueryState } from '../../utils/get-product-collection-query-state';

const Edit = ( props: BlockEditProps< Record< string, never > > ) => {
	const blockProps = useBlockProps();
	const localQueryState = getProductCollectionQueryState(
		props.context.query
	);

	const { data, isLoading } = useCollectionData( {
		queryPrices: true,
		queryState: localQueryState ?? {},
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
