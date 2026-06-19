/**
 * External dependencies
 */
import {
	useBlockProps,
	BlockContextProvider,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useCollectionData } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { InitialDisabled } from '../../components/initial-disabled';
import { Inspector } from './inspector';
import type { EditProps } from './types';
import type { FilterItemFields } from '../../types';
import type { SelectableItemsContext } from '../../../../types/type-defs/selectable-items';

const Edit = ( props: EditProps ) => {
	const { showCounts, hideEmpty } = props.attributes;
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			template: [
				[
					'core/heading',
					{
						level: 3,
						content: __( 'Status', 'woocommerce' ),
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
				[ 'woocommerce/product-filter-checkbox-list' ],
			],
		}
	);

	const stockStatusOptions: Record< string, string > = getSetting(
		'stockStatusOptions',
		{}
	);

	const { data: filteredCounts, isLoading } = useCollectionData( {
		queryStock: true,
		queryState: {},
		isEditor: true,
	} );

	const items = useMemo( () => {
		return Object.entries( stockStatusOptions )
			.filter( ( [ key ] ) => {
				if ( ! hideEmpty ) return true;
				const count =
					filteredCounts?.stock_status_counts?.find(
						( item ) => item.status === key
					)?.count ?? 0;
				return count > 0;
			} )
			.map( ( [ key, value ], index ) => {
				const count =
					filteredCounts?.stock_status_counts?.find(
						( item ) => item.status === key
					)?.count ?? 0;

				return {
					label: value,
					ariaLabel: value,
					value: key,
					selected: index === 0,
					...( showCounts && { count } ),
					type: 'status',
				};
			} );
	}, [ stockStatusOptions, filteredCounts, hideEmpty, showCounts ] );

	return (
		<div { ...innerBlocksProps }>
			<Inspector { ...props } />
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						'woocommerce/selectableItems': {
							items,
							selectionMode: 'multiple' as const,
							storeNamespace: 'woocommerce/product-filters',
							isLoading,
						} satisfies SelectableItemsContext< FilterItemFields >,
					} }
				>
					{ children }
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default Edit;
