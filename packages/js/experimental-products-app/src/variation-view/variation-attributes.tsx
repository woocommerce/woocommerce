/**
 * External dependencies
 */
import {
	DataViews,
	type Field,
	type View,
	type ViewTable,
} from '@wordpress/dataviews';
import { Notice, Tooltip } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	createInterpolateElement,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { Badge, Button, Stack } from '@wordpress/ui';
import { help, Icon, link as linkIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import {
	getProductAttributeRows,
	getVariationAttributeRows,
	type VariationAttributeRow,
} from './attribute-rows';

const EMPTY_ARRAY: VariationAttributeRow[] = [];

const noop = () => undefined;
const VARIATIONS_PANEL_SELECTOR = '#variable_product_options';
const VARIATIONS_TAB_SELECTOR =
	'#woocommerce-product-data ul.product_data_tabs a[href="#variable_product_options"]';

type AttributeTableColumn =
	| 'values'
	| 'defaultValue'
	| 'isVisible'
	| 'isGlobal';

type AttributeTableLayoutStyles = NonNullable<
	ViewTable[ 'layout' ]
>[ 'styles' ];
type AttributeRowGetter = (
	product?: Pick< ProductEntityRecord, 'attributes' | 'default_attributes' >
) => VariationAttributeRow[];

function getGlobalAttributeTermsLink( attributeSlug: string ): string {
	return getAdminLink(
		addQueryArgs( 'edit-tags.php', {
			taxonomy: attributeSlug,
			post_type: 'product',
		} )
	);
}

function openVariationsTab() {
	const tabLink = document.querySelector< HTMLAnchorElement >(
		VARIATIONS_TAB_SELECTOR
	);
	const panel = document.querySelector< HTMLElement >(
		VARIATIONS_PANEL_SELECTOR
	);
	const tab = tabLink?.closest< HTMLElement >( 'li' );

	if ( ! tabLink || ! panel || ! tab ) {
		return;
	}

	if ( getComputedStyle( tab ).display === 'none' ) {
		return;
	}

	tabLink.click();

	if (
		tab.classList.contains( 'active' ) &&
		getComputedStyle( panel ).display !== 'none'
	) {
		return;
	}

	const panelWrap = tabLink.closest< HTMLElement >( 'div.panel-wrap' );

	if ( ! panelWrap ) {
		return;
	}

	panelWrap
		.querySelectorAll( 'ul.wc-tabs li' )
		.forEach( ( item ) => item.classList.remove( 'active' ) );
	tab.classList.add( 'active' );

	panelWrap
		.querySelectorAll< HTMLElement >( 'div.panel' )
		.forEach( ( item ) => {
			item.style.display = item === panel ? 'block' : 'none';
		} );
	panel.dispatchEvent( new Event( 'woocommerce_tab_shown' ) );
}

function getBooleanLabel( value: boolean ) {
	return value ? __( 'Yes', 'woocommerce' ) : __( 'No', 'woocommerce' );
}

function EmptyDefaultValue() {
	return (
		<span className="woocommerce-variation-attributes__muted">&mdash;</span>
	);
}

function BooleanValue( { value }: { value: boolean } ) {
	const label = getBooleanLabel( value );

	if ( value ) {
		return <>{ label }</>;
	}

	return (
		<span className="woocommerce-variation-attributes__muted">
			{ label }
		</span>
	);
}

function AttributeValuePills( { values }: { values: string[] } ) {
	if ( values.length === 0 ) {
		return <EmptyDefaultValue />;
	}

	return (
		<Stack
			direction="row"
			gap="xs"
			wrap="wrap"
			className="woocommerce-variation-attributes__pill-list"
		>
			{ values.map( ( value ) => (
				<Badge
					key={ value }
					intent="none"
					className="woocommerce-variation-attributes__pill"
				>
					{ value }
				</Badge>
			) ) }
		</Stack>
	);
}

function getAttributeTableFields(
	nameLabel: string
): Field< VariationAttributeRow >[] {
	return [
		{
			id: 'name',
			label: nameLabel,
			enableHiding: false,
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => item.name,
		},
		{
			id: 'values',
			label: __( 'Values', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => item.values.join( ' ' ),
			render: ( { item } ) => (
				<AttributeValuePills values={ item.values } />
			),
		},
		{
			id: 'defaultValue',
			label: __( 'Default value', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => item.defaultValue,
			render: ( { item } ) =>
				item.defaultValue ? (
					<Badge
						intent="none"
						className="woocommerce-variation-attributes__pill"
					>
						{ item.defaultValue }
					</Badge>
				) : (
					<EmptyDefaultValue />
				),
		},
		{
			id: 'isVisible',
			label: __( 'Visible on product page', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => getBooleanLabel( item.isVisible ),
			render: ( { item } ) => <BooleanValue value={ item.isVisible } />,
		},
		{
			id: 'isGlobal',
			label: __( 'Global', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => getBooleanLabel( item.isGlobal ),
			render: ( { item } ) =>
				item.isGlobal ? (
					<Tooltip
						text={ __(
							'Available across all products. Customers can filter your catalog by this attribute.',
							'woocommerce'
						) }
					>
						<a
							className="woocommerce-variation-attributes__global-link"
							href={ getGlobalAttributeTermsLink( item.slug ) }
							target="_blank"
							rel="noreferrer"
							onClick={ ( event ) => event.stopPropagation() }
						>
							{ __( 'Yes', 'woocommerce' ) }
							<Icon icon={ linkIcon } size={ 16 } />
						</a>
					</Tooltip>
				) : (
					<BooleanValue value={ item.isGlobal } />
				),
		},
	];
}

function getAttributeTableView(
	columns: AttributeTableColumn[],
	styles: AttributeTableLayoutStyles
): View {
	return {
		type: 'table',
		page: 1,
		perPage: 50,
		titleField: 'name',
		fields: columns,
		layout: {
			styles,
		},
	};
}

const DEFAULT_VARIATION_ATTRIBUTE_COLUMNS: AttributeTableColumn[] = [
	'values',
	'defaultValue',
	'isGlobal',
];

const DEFAULT_PRODUCT_ATTRIBUTE_COLUMNS: AttributeTableColumn[] = [
	'values',
	'isVisible',
	'isGlobal',
];

const DEFAULT_VARIATION_ATTRIBUTE_LAYOUT_STYLES = {
	name: { width: '160px' },
	values: { width: '100%' },
	defaultValue: { width: '180px', minWidth: '180px' },
	isGlobal: { width: '120px', minWidth: '120px' },
};

const DEFAULT_PRODUCT_ATTRIBUTE_LAYOUT_STYLES = {
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

function AttributeTable( {
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
						data={ rows }
						fields={ fields }
						view={ view }
						onChangeView={ setView }
						paginationInfo={ {
							totalItems: rows.length,
							totalPages: 1,
						} }
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

function ProductAttributesNotice() {
	const [ isVisible, setIsVisible ] = useState( true );

	useEffect( () => {
		function handleVariationsLinkClick( event: MouseEvent ) {
			const target = event.target;

			if ( ! ( target instanceof Element ) ) {
				return;
			}

			const link = target.closest< HTMLAnchorElement >(
				'.woocommerce-variation-attributes__notice a[href="#variable_product_options"]'
			);

			if ( ! link ) {
				return;
			}

			event.preventDefault();
			openVariationsTab();
		}

		document.addEventListener( 'click', handleVariationsLinkClick, true );

		return () => {
			document.removeEventListener(
				'click',
				handleVariationsLinkClick,
				true
			);
		};
	}, [] );

	if ( ! isVisible ) {
		return null;
	}

	return (
		<Notice
			status="info"
			isDismissible
			className="woocommerce-variation-attributes__notice"
			onRemove={ () => setIsVisible( false ) }
		>
			{ createInterpolateElement(
				__(
					'Attributes used for variations have moved to the <variationsLink />.',
					'woocommerce'
				),
				{
					variationsLink: (
						<a href="#variable_product_options">
							{ __( 'Variations tab', 'woocommerce' ) }
						</a>
					),
				}
			) }
		</Notice>
	);
}

type VariationAttributesProps = {
	productId: number;
};

export function ProductAttributes( { productId }: VariationAttributesProps ) {
	return (
		<AttributeTable
			columns={ DEFAULT_PRODUCT_ATTRIBUTE_COLUMNS }
			getRows={ getProductAttributeRows }
			helpText={ __(
				'Product attributes describe details customers can use to search, filter, and compare products.',
				'woocommerce'
			) }
			nameLabel={ __( 'Name', 'woocommerce' ) }
			notice={ <ProductAttributesNotice /> }
			productId={ productId }
			styles={ DEFAULT_PRODUCT_ATTRIBUTE_LAYOUT_STYLES }
			title={ __( 'Product attributes', 'woocommerce' ) }
		/>
	);
}

export function VariationAttributes( { productId }: VariationAttributesProps ) {
	return (
		<AttributeTable
			columns={ DEFAULT_VARIATION_ATTRIBUTE_COLUMNS }
			getRows={ getVariationAttributeRows }
			hasSeparator
			helpText={ __(
				'Edit attribute values to update combinations. Customers see attributes in the order shown, with the default value pre-selected on the product page.',
				'woocommerce'
			) }
			hideWhenEmpty
			nameLabel={ __( 'Attribute', 'woocommerce' ) }
			productId={ productId }
			styles={ DEFAULT_VARIATION_ATTRIBUTE_LAYOUT_STYLES }
			title={ __( 'Variation attributes', 'woocommerce' ) }
		/>
	);
}
