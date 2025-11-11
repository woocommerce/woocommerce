/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import clsx from 'clsx';
import { isRTL, __ } from '@wordpress/i18n';
import { Icon, chevronRight, chevronLeft, close } from '@wordpress/icons';
// @ts-ignore No types for this exist yet.
import SidebarButton from '@wordpress/edit-site/build-module/components/sidebar-button';
import {
	__experimentalHStack as HStack,
	__experimentalHeading as Heading,
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import React from 'react';
/**
 * Internal dependencies
 */

export type SidebarContainerProps = {
	title: React.ReactNode;
	description?: React.ReactNode;
	footer?: React.ReactNode;
	children: React.ReactNode;
	onMobileClose?: () => void;
};
export const SidebarContainer = ( {
	title,
	description,
	footer,
	children,
	onMobileClose,
}: SidebarContainerProps ) => {
	const chevronIcon = isRTL() ? chevronRight : chevronLeft;

	const hasOnClick = (
		el: React.ReactNode
	): el is React.ReactElement< { onClick: () => void } > =>
		React.isValidElement( el ) && typeof el.props.onClick === 'function';

	return (
		<>
			<VStack
				className={ clsx(
					'woocommerce-edit-site-sidebar-navigation-screen__main',
					{
						'has-footer': !! footer,
					}
				) }
				spacing={ 0 }
				justify="flex-start"
			>
				<HStack
					spacing={ 4 }
					alignment="flex-start"
					className="woocommerce-edit-site-sidebar-navigation-screen__title-icon"
				>
					<SidebarButton
						onClick={
							hasOnClick( title ) // inherit onClick from title, if it exists
								? title.props.onClick
								: undefined
						}
						icon={ chevronIcon }
						label={ __( 'Back', 'woocommerce' ) }
						showTooltip={ false }
					/>

					<Heading
						className="woocommerce-edit-site-sidebar-navigation-screen__title"
						level={ 1 }
						as="h1"
					>
						{ title }
					</Heading>
				</HStack>

				{ onMobileClose && (
					<div className="woocommerce-lys-mobile-sidebar-close">
						<Button
							className="mobile-sidebar-close"
							onClick={ onMobileClose }
							icon={ <Icon icon={ close } size={ 24 } /> }
							aria-label={ __( 'Close sidebar', 'woocommerce' ) }
						/>
					</div>
				) }

				<div className="woocommerce-edit-site-sidebar-navigation-screen__content">
					{ description && (
						<p className="woocommerce-edit-site-sidebar-navigation-screen__description">
							{ description }
						</p>
					) }
					{ children }
				</div>
			</VStack>
			{ footer && (
				<footer className="woocommerce-edit-site-sidebar-navigation-screen__footer">
					{ footer }
				</footer>
			) }
		</>
	);
};
