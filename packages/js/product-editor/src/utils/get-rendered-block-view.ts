/**
 * External dependencies
 */
import { ReactElement } from 'react';
import { createElement } from '@wordpress/element';
// import { v4 as uuid } from 'uuid';

/**
 * Internal dependencies
 */
import { TabBlockEdit } from '../blocks/generic/tab/edit';
import { SectionBlockEdit } from '../blocks/generic/section/edit';
import { NameBlockEdit } from '../blocks/product-fields/name/edit';
import { Edit as RegularPriceBlockEdit } from '../blocks/product-fields/regular-price/edit';
import { Edit as SalePriceBlockEdit } from '../blocks/product-fields/sale-price/edit';
import { Edit as ScheduleSaleBlockEdit } from '../blocks/product-fields/schedule-sale/edit';
import { Edit as RadioBlockEdit } from '../blocks/generic/radio/edit';
import { SummaryBlockEdit } from '../blocks/product-fields/summary/edit';

type ComponentMap = {
	[ key: string ]: unknown;
};

const componentMap: ComponentMap = {
	'woocommerce/product-tab': TabBlockEdit,
	'woocommerce/product-section': SectionBlockEdit,
	'woocommerce/product-name-field': NameBlockEdit,
	'woocommerce/product-regular-price-field': RegularPriceBlockEdit,
	'woocommerce/product-sale-price-field': SalePriceBlockEdit,
	'woocommerce/product-schedule-sale-fields': ScheduleSaleBlockEdit,
	'woocommerce/product-radio-field': RadioBlockEdit,
	'woocommerce/product-summary-field': SummaryBlockEdit,
};

function getComponentName( blockName: string | null ) {
	if ( ! blockName ) {
		return null;
	}
	return componentMap[ blockName ];
}

export function getRenderedBlockView(
	domElement: Element,
	context: unknown
): ReactElement[] {
	const rendered: ReactElement[] = [];

	for ( let i = 0; i < domElement.children.length; i++ ) {
		const child = domElement.children[ i ];
		const blockName = child.getAttribute( 'data-block-name' );
		const children = getRenderedBlockView( child, context );
		const componentName = getComponentName( blockName );

		if ( componentName ) {
			rendered.push(
				createElement(
					// @ts-ignore
					componentName,
					{
						// @ts-ignore No types for dataset yet.
						attributes: child.dataset,
						// @ts-ignore
						context,
						clientId: '@ptodo',
						setAttributes: () => {},
						// clientId: uuid(),
					},
					children
				)
			);
		} else {
			rendered.push(
				createElement( child.tagName, child.attributes, children )
			);
		}
	}

	return rendered;
}
