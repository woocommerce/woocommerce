/**
 * External dependencies
 */
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Stacks from '../experimental-products/stack';
import CardList from './CardList';
import { importTypes } from './importTypes';
import './style.scss';
import useProductTypeListItems from '../experimental-products/use-product-types-list-items';
import { getProductTypes } from '../experimental-products/utils';
import LoadSampleProductModal from '../components/load-sample-product-modal';
import useLoadSampleProducts from '../components/use-load-sample-products';

const Products = () => {
	const [ showStacks, setStackVisibility ] = useState< boolean >( false );

	const {
		loadSampleProduct,
		isLoadingSampleProducts,
	} = useLoadSampleProducts( {
		redirectUrlAfterSuccess: getAdminLink(
			'edit.php?post_type=product&wc_onboarding_active_task=products'
		),
	} );
	const StacksComponent = (
		<Stacks
			items={ useProductTypeListItems(
				getProductTypes( [ 'subscription' ] )
			) }
			onClickLoadSampleProduct={ loadSampleProduct }
		/>
	);
	return (
		<div className="woocommerce-task-import-products">
			<h1>{ __( 'Import your products', 'woocommerce' ) }</h1>
			<CardList items={ importTypes } />
			<div className="woocommerce-task-import-products-stacks">
				<Button onClick={ () => setStackVisibility( ! showStacks ) }>
					{ __( 'Or add your products from scratch', 'woocommerce' ) }
					<Icon icon={ showStacks ? chevronUp : chevronDown } />
				</Button>
				{ showStacks && StacksComponent }
			</div>
			{ isLoadingSampleProducts && <LoadSampleProductModal /> }
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
