/**
 * External dependencies
 */
import classNames from 'classnames';
import { _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import Label from '@woocommerce/base-components/label';
import Title from '@woocommerce/base-components/title';
import { useSelectShippingRate } from '@woocommerce/base-hooks';
import type { ReactElement } from 'react';
import type { Rate, PackageRateOption } from '@woocommerce/type-defs/shipping';

/**
 * Internal dependencies
 */
import Panel from '../../panel';
import PackageRates from './package-rates';
import './style.scss';

interface PackageItem {
	name: string;
	key: string;
	quantity: number;
}

interface Destination {
	// eslint-disable-next-line camelcase
	address_1: string;
	// eslint-disable-next-line camelcase
	address_2: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
}

interface PackageProps {
	packageId: string;
	renderOption: ( option: Rate ) => PackageRateOption;
	collapse: boolean;
	packageData: {
		destination: Destination;
		name: string;
		// eslint-disable-next-line camelcase
		shipping_rates: Rate[];
		items: PackageItem[];
	};
	className?: string;
	collapsible?: boolean;
	noResultsMessage: ReactElement;
	showItems: boolean;
}

const Package = ( {
	packageId,
	className,
	noResultsMessage,
	renderOption,
	packageData,
	collapsible = false,
	collapse = false,
	showItems = false,
}: PackageProps ): ReactElement => {
	const { selectShippingRate, selectedShippingRate } = useSelectShippingRate(
		packageId,
		packageData.shipping_rates
	);

	const header = (
		<>
			{ ( showItems || collapsible ) && (
				<Title
					className="wc-block-components-shipping-rates-control__package-title"
					headingLevel="3"
				>
					{ packageData.name }
				</Title>
			) }
			{ showItems && (
				<ul className="wc-block-components-shipping-rates-control__package-items">
					{ Object.values( packageData.items ).map( ( v ) => {
						const name = decodeEntities( v.name );
						const quantity = v.quantity;
						return (
							<li
								key={ v.key }
								className="wc-block-components-shipping-rates-control__package-item"
							>
								<Label
									label={
										quantity > 1
											? `${ name } × ${ quantity }`
											: `${ name }`
									}
									screenReaderLabel={ sprintf(
										/* translators: %1$s name of the product (ie: Sunglasses), %2$d number of units in the current cart package */
										_n(
											'%1$s (%2$d unit)',
											'%1$s (%2$d units)',
											quantity,
											'woo-gutenberg-products-block'
										),
										name,
										quantity
									) }
								/>
							</li>
						);
					} ) }
				</ul>
			) }
		</>
	);
	const body = (
		<PackageRates
			className={ className }
			noResultsMessage={ noResultsMessage }
			rates={ packageData.shipping_rates }
			onSelectRate={ selectShippingRate }
			selected={ selectedShippingRate }
			renderOption={ renderOption }
		/>
	);
	if ( collapsible ) {
		return (
			<Panel
				className="wc-block-components-shipping-rates-control__package"
				initialOpen={ ! collapse }
				title={ header }
			>
				{ body }
			</Panel>
		);
	}
	return (
		<div
			className={ classNames(
				'wc-block-components-shipping-rates-control__package',
				className
			) }
		>
			{ header }
			{ body }
		</div>
	);
};

export default Package;
