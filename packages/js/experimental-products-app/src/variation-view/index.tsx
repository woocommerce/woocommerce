/**
 * External dependencies
 */
import { DataViews, type Action, type View } from '@wordpress/dataviews';
import { Notice } from '@wordpress/components';
import { Button, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { experimentalProductVariationsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { DEFAULT_LAYOUTS, DEFAULT_VIEW, PAGE_SIZE } from './constants';
import { buildVariationViewQuery } from './query';
import { normalizeVariation } from './normalization';
import { variationFields } from './fields';
import { VariationEditModal } from './variation-edit-modal';
import type { VariationEntityRecord } from './types';

const EMPTY_ARRAY: VariationEntityRecord[] = [];

type VariationViewProps = {
	productId: number;
};

export function VariationView( { productId }: VariationViewProps ) {
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );
	const [ selection, setSelection ] = useState< string[] >( [] );
	const [ editingVariation, setEditingVariation ] =
		useState< VariationEntityRecord | null >( null );

	const query = useMemo(
		() => buildVariationViewQuery( view, productId ),
		[ productId, view ]
	);

	const { records, totalItems, error } = useSelect(
		( select ) => {
			const store = select( experimentalProductVariationsStore );
			return {
				// @ts-expect-error missing types.
				records: store.getProductVariations( query ),
				// @ts-expect-error missing types.
				totalItems: store.getProductVariationsTotalCount( query ),
				// @ts-expect-error missing types.
				error: store.getProductVariationsError( query ),
			};
		},
		[ query ]
	);

	const variations = useMemo(
		() => records?.map( normalizeVariation ) || EMPTY_ARRAY,
		[ records ]
	);
	const perPage = view.perPage || PAGE_SIZE;
	const paginationInfo = useMemo(
		() => ( {
			totalItems: totalItems ?? 0,
			totalPages: Math.ceil( ( totalItems ?? 0 ) / perPage ),
		} ),
		[ perPage, totalItems ]
	);

	const handleEditVariation = useCallback(
		( variation: VariationEntityRecord ) => {
			setEditingVariation( variation );
		},
		[]
	);

	const actions: Action< VariationEntityRecord >[] = useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit', 'woocommerce' ),
				isPrimary: true,
				callback: ( items ) => handleEditVariation( items[ 0 ] ),
			},
			{
				id: 'delete-variation',
				label: __( 'Delete variation', 'woocommerce' ),
				supportsBulk: true,
				callback: () => {},
			},
		],
		[ handleEditVariation ]
	);

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Failed to load variations.', 'woocommerce' ) }
			</Notice>
		);
	}

	return (
		<div className="woocommerce-variation-view">
			<DataViews
				data={ variations }
				fields={ variationFields }
				view={ view }
				onClickItem={ handleEditVariation }
				onChangeView={ setView }
				isLoading={ ! records }
				paginationInfo={ paginationInfo }
				getItemId={ ( item: VariationEntityRecord ) =>
					String( item.id )
				}
				defaultLayouts={ DEFAULT_LAYOUTS }
				actions={ actions }
				selection={ selection }
				onChangeSelection={ setSelection }
			>
				<Stack
					direction="row"
					align="center"
					justify="space-between"
					className="woocommerce-variation-view__toolbar"
				>
					<DataViews.Search
						label={ __( 'Search variations', 'woocommerce' ) }
					/>
					<Stack direction="row" gap="xs">
						<DataViews.ViewConfig />
						<Button disabled>
							{ __( 'Edit options', 'woocommerce' ) }
						</Button>
					</Stack>
				</Stack>
				<DataViews.Layout />
				<DataViews.Footer />
			</DataViews>
			{ editingVariation && (
				<VariationEditModal
					variation={ editingVariation }
					onClose={ () => setEditingVariation( null ) }
				/>
			) }
		</div>
	);
}
