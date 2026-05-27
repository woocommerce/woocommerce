/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	getProductAttributeRows,
	getVariationAttributeRows,
} from './attribute-rows';
import type { ProductEntityRecord } from '../../fields/types';
import {
	AttributeTable,
	DEFAULT_PRODUCT_ATTRIBUTE_COLUMNS,
	DEFAULT_PRODUCT_ATTRIBUTE_LAYOUT_STYLES,
	DEFAULT_VARIATION_ATTRIBUTE_COLUMNS,
	DEFAULT_VARIATION_ATTRIBUTE_LAYOUT_STYLES,
} from './dataviews';

const VARIATIONS_PANEL_SELECTOR = '#variable_product_options';
const VARIATIONS_TAB_SELECTOR =
	'#woocommerce-product-data ul.product_data_tabs a[href="#variable_product_options"]';

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

	const notice =
		hasResolved && product?.type === 'variable' ? (
			<ProductAttributesNotice />
		) : undefined;

	return (
		<AttributeTable
			columns={ DEFAULT_PRODUCT_ATTRIBUTE_COLUMNS }
			getRows={ getProductAttributeRows }
			helpText={ __(
				'Product attributes describe details customers can use to search, filter, and compare products.',
				'woocommerce'
			) }
			nameLabel={ __( 'Name', 'woocommerce' ) }
			notice={ notice }
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
