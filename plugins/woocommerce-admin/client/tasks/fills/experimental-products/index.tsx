/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { Text } from '@woocommerce/experimental';
import { registerPlugin } from '@wordpress/plugins';
import { useMemo, useState } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './index.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { getSurfacedProductTypeKeys, getProductTypes } from './utils';
import useProductTypeListItems from './use-product-types-list-items';
import useLayoutExperiment from '../use-product-layout-experiment';
import Stack from './stack';
import Footer from './footer';
import CardLayout from './card-layout';
import { LoadSampleProductType } from './constants';
import LoadSampleProductModal from '../components/load-sample-product-modal';
import useLoadSampleProducts from '../components/use-load-sample-products';

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
	const [ isLoadingExperiment, experimentLayout ] = useLayoutExperiment();

	const productTypes = useProductTypeListItems( getProductTypes() );
	const surfacedProductTypeKeys = getSurfacedProductTypeKeys(
		getOnboardingProductType()
	);

	const {
		loadSampleProduct,
		isLoadingSampleProducts,
	} = useLoadSampleProducts( {
		redirectUrlAfterSuccess: getAdminLink(
			'edit.php?post_type=product&wc_onboarding_active_task=products'
		),
	} );

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

			if ( experimentLayout === 'card' ) {
				surfacedProductTypes.push( {
					...LoadSampleProductType,
					onClick: loadSampleProduct,
				} );
			}
		}
		return surfacedProductTypes;
	}, [
		surfacedProductTypeKeys,
		isExpanded,
		productTypes,
		experimentLayout,
		loadSampleProduct,
	] );

	return (
		<div className="woocommerce-task-products">
			{ isLoadingExperiment ? (
				<Spinner />
			) : (
				<>
					<Text
						variant="title"
						as="h2"
						className="woocommerce-task-products__title"
					>
						{ __(
							'What product do you want to add?',
							'woocommerce'
						) }
					</Text>

					<div className="woocommerce-product-content">
						{ experimentLayout === 'stacked' ? (
							<Stack
								items={ visibleProductTypes }
								onClickLoadSampleProduct={ loadSampleProduct }
							/>
						) : (
							<CardLayout items={ visibleProductTypes } />
						) }
						<ViewControlButton
							isExpanded={ isExpanded }
							onClick={ () => setIsExpanded( ! isExpanded ) }
						/>
						<Footer />
					</div>
					{ isLoadingSampleProducts && <LoadSampleProductModal /> }
				</>
			) }
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
