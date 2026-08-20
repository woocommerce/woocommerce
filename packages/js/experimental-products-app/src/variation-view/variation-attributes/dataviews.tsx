/**
 * External dependencies
 */
import { DataViews, type View, type ViewTable } from '@wordpress/dataviews';
import { Tooltip } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Stack } from '@wordpress/ui';
import { help, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../../fields/types';
import { getAttributeTableFields, type AttributeTableColumn } from './fields';
import type { VariationAttributeRow } from './attribute-rows';

const EMPTY_ARRAY: VariationAttributeRow[] = [];
const ATTRIBUTE_PAGE_SIZE = 50;
const noop = () => undefined;

type AttributeTableLayoutStyles = NonNullable<
	ViewTable[ 'layout' ]
>[ 'styles' ];
type AttributeRowGetter = (
	product?: Pick< ProductEntityRecord, 'attributes' | 'default_attributes' >
) => VariationAttributeRow[];

function getAttributeTableView(
	columns: AttributeTableColumn[],
	styles: AttributeTableLayoutStyles
): View {
	return {
		type: 'table',
		page: 1,
		perPage: ATTRIBUTE_PAGE_SIZE,
		titleField: 'name',
		fields: columns,
		layout: {
			styles,
		},
	};
}

export const DEFAULT_VARIATION_ATTRIBUTE_COLUMNS: AttributeTableColumn[] = [
	'values',
	'defaultValue',
	'isGlobal',
];

export const DEFAULT_PRODUCT_ATTRIBUTE_COLUMNS: AttributeTableColumn[] = [
	'values',
	'isVisible',
	'isGlobal',
];

export const DEFAULT_VARIATION_ATTRIBUTE_LAYOUT_STYLES = {
	name: { width: '160px' },
	values: { width: '100%' },
	defaultValue: { width: '180px', minWidth: '180px' },
	isGlobal: { width: '120px', minWidth: '120px' },
};

export const DEFAULT_PRODUCT_ATTRIBUTE_LAYOUT_STYLES = {
	name: { width: '220px' },
	values: { width: '100%' },
	isVisible: { width: '240px', minWidth: '240px' },
	isGlobal: { width: '120px', minWidth: '120px' },
};

type AttributeTableProps = {
	columns: AttributeTableColumn[];
	getRows: AttributeRowGetter;
	hasSeparator?: boolean;
	helpText: string;
	hideWhenEmpty?: boolean;
	nameLabel: string;
	notice?: JSX.Element;
	productId: number;
	styles: AttributeTableLayoutStyles;
	title: string;
};

export function AttributeTable( {
	columns,
	getRows,
	hasSeparator = false,
	helpText,
	hideWhenEmpty = false,
	nameLabel,
	notice,
	productId,
	styles,
	title,
}: AttributeTableProps ) {
	const [ view, setView ] = useState< View >( () =>
		getAttributeTableView( columns, styles )
	);
	const fields = useMemo(
		() => getAttributeTableFields( nameLabel ),
		[ nameLabel ]
	);
	const { product, hasResolved } = useSelect(
		( select ) => {
			const coreSelect = select( coreStore );
			const resolutionArgs = [ 'root', 'product', productId ];

			return {
				hasResolved: coreSelect.hasFinishedResolution(
					'getEntityRecord',
					resolutionArgs
				),
				product: coreSelect.getEditedEntityRecord(
					'root',
					'product',
					productId
				) as unknown as ProductEntityRecord | undefined,
			};
		},
		[ productId ]
	);

	const rows = useMemo(
		() => ( hasResolved ? getRows( product ) : EMPTY_ARRAY ),
		[ getRows, hasResolved, product ]
	);
	const perPage = view.perPage || ATTRIBUTE_PAGE_SIZE;
	const pageRows = useMemo< VariationAttributeRow[] >( () => {
		const page = view.page ?? 1;
		const offset = ( page - 1 ) * perPage;

		return rows.slice( offset, offset + perPage );
	}, [ perPage, rows, view.page ] );
	const paginationInfo = useMemo(
		() => ( {
			totalItems: rows.length,
			totalPages: Math.ceil( rows.length / perPage ),
		} ),
		[ perPage, rows.length ]
	);

	if ( hideWhenEmpty && hasResolved && rows.length === 0 ) {
		return null;
	}

	return (
		<section
			className={
				hasSeparator
					? 'woocommerce-variation-attributes woocommerce-variation-attributes--has-separator'
					: 'woocommerce-variation-attributes'
			}
		>
			{ notice }
			<Stack
				direction="row"
				align="center"
				justify="space-between"
				className="woocommerce-variation-attributes__header"
			>
				<Stack
					direction="row"
					align="center"
					gap="xs"
					className="woocommerce-variation-attributes__title-group"
				>
					<h3 className="woocommerce-variation-attributes__title">
						{ title }
					</h3>
					<Tooltip text={ helpText }>
						<span
							className="woocommerce-variation-attributes__help"
							tabIndex={ 0 }
							aria-label={ __( 'Help', 'woocommerce' ) }
						>
							<Icon icon={ help } size={ 20 } />
						</span>
					</Tooltip>
				</Stack>
				<Button variant="outline" onClick={ noop }>
					{ __( 'Edit', 'woocommerce' ) }
				</Button>
			</Stack>
			<div className="woocommerce-variation-attributes__body">
				<div className="woocommerce-variation-attributes__dataview">
					<DataViews
						data={ pageRows }
						fields={ fields }
						view={ view }
						onChangeView={ setView }
						paginationInfo={ paginationInfo }
						defaultLayouts={ { table: {} } }
						getItemId={ ( item ) => item.id }
						search={ false }
						isLoading={ ! hasResolved }
					>
						<DataViews.Layout />
					</DataViews>
				</div>
			</div>
		</section>
	);
}
