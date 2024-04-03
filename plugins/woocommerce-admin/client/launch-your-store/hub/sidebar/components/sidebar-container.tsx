/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { isRTL, __ } from '@wordpress/i18n';
import { chevronRight, chevronLeft } from '@wordpress/icons';
// @ts-ignore No types for this exist yet.
import SidebarButton from '@wordpress/edit-site/build-module/components/sidebar-button';
import {
	// @ts-ignore No types for this exist yet.
	__experimentalHStack as HStack,
	// @ts-ignore No types for this exist yet.
	__experimentalHeading as Heading,
	// @ts-ignore No types for this exist yet.
	__experimentalVStack as VStack,
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
};
export const SidebarContainer = ( {
	title,
	description,
	footer,
	children,
}: SidebarContainerProps ) => {
	const chevronIcon = isRTL() ? chevronRight : chevronLeft;

	const hasOnClick = (
		el: React.ReactNode
	): el is React.ReactElement< { onClick: () => void } > =>
		React.isValidElement( el ) && typeof el.props.onClick === 'function';

	return (
		<>
			<VStack
				className={ classnames(
					'edit-site-sidebar-navigation-screen__main',
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
					className="edit-site-sidebar-navigation-screen__title-icon"
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
						className="edit-site-sidebar-navigation-screen__title"
						level={ 1 }
						size={ 20 }
					>
						{ title }
					</Heading>
				</HStack>

				<div className="edit-site-sidebar-navigation-screen__content">
					{ description && (
						<p className="edit-site-sidebar-navigation-screen__description">
							{ description }
						</p>
					) }
					{ children }
				</div>
			</VStack>
			{ footer && (
				<footer className="edit-site-sidebar-navigation-screen__footer">
					{ footer }
				</footer>
			) }
		</>
	);
};
