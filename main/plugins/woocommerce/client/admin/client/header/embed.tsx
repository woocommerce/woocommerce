/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { cog, help } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import { BaseHeader } from './shared';
import { useWpAdminChrome } from './use-wp-admin-chrome';

export const EmbedHeader = ( {
	sections,
	query,
}: {
	sections: string[];
	query: Record< string, string >;
} ) => {
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
					onClick={ () => {
						// Capture the pre-click state so we can tell `open`
						// from `close` clicks. wp-admin's screen-meta.js flips
						// aria-expanded synchronously inside triggerMetaIcon(),
						// so reading it after would lose the original signal.
						recordEvent( 'header_meta_icon_click', {
							icon: 'screen-options',
							action:
								activeMetaIcon === 'screen-options'
									? 'close'
									: 'open',
						} );
						triggerMetaIcon(
							'screen-options',
							'#show-settings-link'
						);
					} }
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
					onClick={ () => {
						recordEvent( 'header_meta_icon_click', {
							icon: 'help',
							action:
								activeMetaIcon === 'help' ? 'close' : 'open',
						} );
						triggerMetaIcon( 'help', '#contextual-help-link' );
					} }
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
			suppressTitle={ hasWpAdminH1 }
			compact={ hasWpAdminH1 }
			trailingItems={ trailingItems }
		/>
	);
};
