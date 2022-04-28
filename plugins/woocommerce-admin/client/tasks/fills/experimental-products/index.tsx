/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { Text } from '@woocommerce/experimental';
import { registerPlugin } from '@wordpress/plugins';
import { useMemo, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './index.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { getSurfacedProductTypeKeys } from './utils';
import useProductTypeListItems from './use-product-types-list-items';
import Stack from './stack';
import Footer from './footer';
import CardLayout from './card-layout';
import { LoadSampleProductType } from './constants';

// TODO: Use experiment data from the API, not hardcoded.
const SHOW_STACK_LAYOUT = true;

const getOnboardingProductType = (): string[] => {
	const onboardingData = getAdminSetting( 'onboarding' );
	return (
		( onboardingData?.profile &&
			onboardingData?.profile.product_types ) || [ 'physical' ]
	);
};

const ViewControlButton: React.FC< {
	isExpanded: boolean;
	onClick: () => void;
} > = ( { isExpanded, onClick } ) => (
	<Button
		className="woocommerce-task-products__button-view-less-product-types"
		onClick={ onClick }
	>
		{ isExpanded
			? __( `View less product types`, 'woocommerce' )
			: __( `View more product types`, 'woocommerce' ) }
		<Icon icon={ isExpanded ? chevronUp : chevronDown } />
	</Button>
);

export const Products = () => {
	const [ isExpanded, setIsExpanded ] = useState< boolean >( false );

	const productTypes = useProductTypeListItems();
	const surfacedProductTypeKeys = getSurfacedProductTypeKeys(
		getOnboardingProductType()
	);
	const visibleProductTypes = useMemo( () => {
		const surfacedProductTypes = productTypes.filter( ( productType ) =>
			surfacedProductTypeKeys.includes( productType.key )
		);
		if ( isExpanded ) {
			// To show product types in same order, we need to push the other product types to the end.
			productTypes.forEach(
				( productType ) =>
					! surfacedProductTypes.includes( productType ) &&
					surfacedProductTypes.push( productType )
			);

			if ( ! SHOW_STACK_LAYOUT ) {
				surfacedProductTypes.push( {
					...LoadSampleProductType,
					// TODO: Change to load sample product
					onClick: () => new Promise( () => {} ),
				} );
			}
		}
		return surfacedProductTypes;
	}, [ surfacedProductTypeKeys, isExpanded, productTypes ] );

	return (
		<div className="woocommerce-task-products">
			<Text
				variant="title"
				as="h2"
				className="woocommerce-task-products__title"
			>
				{ __( 'What product do you want to add?', 'woocommerce' ) }
			</Text>

			<div className="woocommerce-product-content">
				{ SHOW_STACK_LAYOUT ? (
					<Stack items={ visibleProductTypes } />
				) : (
					<CardLayout items={ visibleProductTypes } />
				) }
				<ViewControlButton
					isExpanded={ isExpanded }
					onClick={ () => setIsExpanded( ! isExpanded ) }
				/>
				<Footer />
			</div>
		</div>
	);
};

registerPlugin( 'wc-admin-onboarding-task-products', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-tasks',
	render: () => (
		// @ts-expect-error WooOnboardingTask is a pure JS component.
		<WooOnboardingTask id="products">
			<Products />
		</WooOnboardingTask>
	),
} );
