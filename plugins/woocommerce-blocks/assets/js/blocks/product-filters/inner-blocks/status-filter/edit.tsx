/**
 * External dependencies
 */
import {
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useCollectionData } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InitialDisabled } from '../../components/initial-disabled';
import { getStockFilterData } from './utils';
import { Inspector } from './inspector';
import { EditProps } from './types';

const Edit = ( props: EditProps ) => {
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			template: [
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
								content: __( 'Status', 'woocommerce' ),
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
				[
					'woocommerce/product-filter-checkbox-list',
					{
						lock: {
							remove: true,
						},
					},
				],
			],
		}
	);

	const { results, isLoading } = useCollectionData( {
		queryStock: true,
		queryState: {},
		isEditor: true,
	} );

	const stock = getStockFilterData( results );

	const labels = useMemo(
		() => ( {
			instock: __( 'In stock', 'woocommerce' ),
			outofstock: __( 'Out of stock', 'woocommerce' ),
			onbackorder: __( 'On backorder', 'woocommerce' ),
		} ),
		[]
	);

	const data = stock.map( ( { status, count } ) => {
		const label = labels[ status ];
		return {
			label:
				label +
				( props.attributes.showCounts
					? ` (${ count.toString() })`
					: '' ),
			value: status,
		};
	} );

	return (
		<div { ...innerBlocksProps }>
			<Inspector { ...props } />
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						filterData: {
							items: data,
							isLoading,
						},
					} }
				>
					{ children }
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default Edit;
