/**
 * External dependencies
 */
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { find } from 'lodash';
/**
 * Internal dependencies
 */
import CardList from './CardList';
import { importTypes } from './importTypes';
import './style.scss';
import useProductTypeListItems from '../experimental-products/use-product-types-list-items';
import Stacks from '../experimental-products/stack';
import { getProductTypes } from '../experimental-products/utils';

const Products = () => {
	const [ showStacks, setStackVisibility ] = useState< boolean >( false );
	const StacksComponent = (
		<Stacks
			items={ useProductTypeListItems(
				getProductTypes( [ 'subscription' ] )
			) }
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
