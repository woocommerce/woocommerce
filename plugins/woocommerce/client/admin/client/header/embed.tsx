/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { cog, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import { isTaskListActive } from '~/hooks/use-tasklists-state';
import { BaseHeader } from './shared';
import { useWpAdminChrome } from './use-wp-admin-chrome';

export const EmbedHeader = ( {
	sections,
	query,
}: {
	sections: string[];
	query: Record< string, string >;
} ) => {
	const isReactifyPaymentsSettingsScreen = Boolean(
		query?.page === 'wc-settings' && query?.tab === 'checkout'
	);
	const showReminderBar = Boolean(
		isTaskListActive( 'setup' ) && ! isReactifyPaymentsSettingsScreen
	);

	// Embed pages live on top of classic wp-admin screens. Detect the wp-admin
	// chrome wp-admin already rendered so we can suppress the duplicate <h1>,
	// proxy the Screen Options / Help dropdowns through floating-header icons,
	// and collapse the bar to chrome-only height when there is no title to show.
	const {
		hasH1: hasWpAdminH1,
		hasScreenOptions,
		hasContextualHelp,
		activeMetaIcon,
		triggerMetaIcon,
	} = useWpAdminChrome( query );

	const trailingItems = (
		<>
			{ /* Screen Options + Help icons consolidated into the floating
			header. Only rendered when wp-admin would have rendered the
			corresponding entry point. The original wp-admin wraps are
			visually hidden via CSS and these icons proxy clicks into them
			through triggerMetaIcon. */ }
			{ hasScreenOptions && (
				<Button
					className={ clsx( 'woocommerce-layout__header-meta-icon', {
						'is-active': activeMetaIcon === 'screen-options',
					} ) }
					label={ __( 'Screen options', 'woocommerce' ) }
					aria-expanded={ activeMetaIcon === 'screen-options' }
					showTooltip
					onClick={ () =>
						triggerMetaIcon(
							'screen-options',
							'#show-settings-link'
						)
					}
				>
					<Icon icon={ cog } size={ 18 } />
				</Button>
			) }
			{ hasContextualHelp && (
				<Button
					className={ clsx( 'woocommerce-layout__header-meta-icon', {
						'is-active': activeMetaIcon === 'help',
					} ) }
					label={ __( 'Help', 'woocommerce' ) }
					aria-expanded={ activeMetaIcon === 'help' }
					showTooltip
					onClick={ () =>
						triggerMetaIcon( 'help', '#contextual-help-link' )
					}
				>
					<Icon icon={ help } size={ 18 } />
				</Button>
			) }
		</>
	);

	return (
		<BaseHeader
			isEmbedded={ true }
			query={ query }
			sections={ sections }
			showReminderBar={ showReminderBar }
			suppressTitle={ hasWpAdminH1 }
			compact={ hasWpAdminH1 }
			trailingItems={ trailingItems }
		/>
	);
};
