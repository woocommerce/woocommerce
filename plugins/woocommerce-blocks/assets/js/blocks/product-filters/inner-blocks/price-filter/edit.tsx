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
import { getAllowedBlocks } from '../../utils';
import { getPriceFilterData } from './utils';
import { InitialDisabled } from '../../components/initial-disabled';

const Edit = () => {
	const blockProps = useBlockProps();

	const { results, isLoading } = useCollectionData( {
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
							price: getPriceFilterData( results ),
							isLoading,
						},
					} }
				>
					<InnerBlocks
						allowedBlocks={ getAllowedBlocks() }
						template={ [
							[
								'core/group',
								{
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
									},
									metadata: {
										name: __( 'Header', 'woocommerce' ),
									},
									style: {
										spacing: {
											blockGap: '0',
										},
									},
								},
								[
									[
										'core/heading',
										{
											level: 3,
											content: __(
												'Price',
												'woocommerce'
											),
										},
									],
									[
										'woocommerce/product-filter-clear-button',
										{
											lock: {
												remove: true,
												move: false,
											},
										},
									],
								],
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
