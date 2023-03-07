/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Create a Fill for extensions to add custom page titles.
 *
 * @slotFill WooHeaderPageTitle
 * @scope woocommerce-admin
 * @example
 * const MyPageTitle = () => (
 * 	<WooHeaderPageTitle>My page title</WooHeaderPageTitle>
 * );
 *
 * registerPlugin( 'my-page-title', {
 * 	render: MyPageTitle,
 * 	scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 */
export const WooHeaderPageTitle: React.FC< {
	children?: React.ReactNode;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children } ) => {
	return <Fill name={ 'woocommerce_header_page_title' }>{ children }</Fill>;
};

WooHeaderPageTitle.Slot = ( { fillProps } ) => (
	<Slot name={ 'woocommerce_header_page_title' } fillProps={ fillProps }>
		{ ( fills ) => {
			return <>{ [ ...fills ].pop() }</>;
		} }
	</Slot>
);
