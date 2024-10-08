/**
 * External dependencies
 */

import { isRTL, __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { chevronRight, chevronLeft } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { createElement, Fragment } from '@wordpress/element';
import {
	// @ts-expect-error missing type.
	__experimentalHStack as HStack,
	// @ts-expect-error missing type.
	__experimentalHeading as Heading,
	// @ts-expect-error missing type.
	__experimentalVStack as VStack,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';
import SidebarButton from './sidebar-button';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type SidebarNavigationScreenProps = {
	isRoot?: boolean;
	title: string;
	actions?: React.JSX.Element;
	meta?: string;
	content: React.JSX.Element;
	footer?: string;
	description?: string;
	backPath?: string;
};

export default function SidebarNavigationScreen( {
	isRoot,
	title,
	actions,
	meta,
	content,
	footer,
	description,
	backPath: backPathProp,
}: SidebarNavigationScreenProps ) {
	const { dashboardLink, dashboardLinkText } = useSelect( ( select ) => {
		const { getSettings } = unlock( select( 'core/edit-site' ) );
		return {
			dashboardLink: getSettings().__experimentalDashboardLink,
			dashboardLinkText: getSettings().__experimentalDashboardLinkText,
		};
	}, [] );
	const location = useLocation();
	const history = useHistory();
	const backPath = backPathProp ?? location.state?.backPath;
	const icon = isRTL() ? chevronRight : chevronLeft;

	return (
		<>
			<VStack
				className={ classNames(
					'edit-site-sidebar-navigation-screen__main',
					{
						'has-footer': !! footer,
					}
				) }
				spacing={ 0 }
				justify="flex-start"
			>
				<HStack
					spacing={ 3 }
					alignment="flex-start"
					className="edit-site-sidebar-navigation-screen__title-icon"
				>
					{ ! isRoot && (
						<SidebarButton
							onClick={ () => {
								history.push( backPath );
							} }
							icon={ icon }
							label={ __( 'Back', 'woocommerce' ) }
							showTooltip={ false }
						/>
					) }
					{ isRoot && (
						<SidebarButton
							icon={ icon }
							label={
								dashboardLinkText ||
								__( 'Go to the Dashboard', 'woocommerce' )
							}
							href={ dashboardLink || 'index.php' }
						/>
					) }
					<Heading
						className="edit-site-sidebar-navigation-screen__title"
						color={ '#e0e0e0' /* $gray-200 */ }
						level={ 1 }
						size={ 20 }
					>
						{ title }
					</Heading>
					{ actions && (
						<div className="edit-site-sidebar-navigation-screen__actions">
							{ actions }
						</div>
					) }
				</HStack>
				{ meta && (
					<>
						<div className="edit-site-sidebar-navigation-screen__meta">
							{ meta }
						</div>
					</>
				) }

				<div className="edit-site-sidebar-navigation-screen__content">
					{ description && (
						<p className="edit-site-sidebar-navigation-screen__description">
							{ description }
						</p>
					) }
					{ content }
				</div>
			</VStack>
			{ footer && (
				<footer className="edit-site-sidebar-navigation-screen__footer">
					{ footer }
				</footer>
			) }
		</>
	);
}
