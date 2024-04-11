/**
 * External dependencies
 */
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import {
	Button,
	Icon,
	__unstableMotion as motion,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { wordpress } from '@wordpress/icons';
import { store as coreStore } from '@wordpress/core-data';
import { useReducedMotion } from '@wordpress/compose';
import { createElement } from '@wordpress/element';

type FullscreenModeCloseProps = {
	showTooltip?: boolean;
	icon?: string;
	href?: string;
};
function FullscreenModeClose( {
	showTooltip,
	icon,
	href,
}: FullscreenModeCloseProps ) {
	const { isRequestingSiteIcon, siteIconUrl } = useSelect( ( select ) => {
		const { getEntityRecord, isResolving } = select( coreStore );
		const siteData =
			getEntityRecord( 'root', '__unstableBase', undefined ) || {};
		return {
			isRequestingSiteIcon: isResolving( 'getEntityRecord', [
				'root',
				'__unstableBase',
				undefined,
			] ),
			siteIconUrl: siteData.site_icon_url,
		};
	}, [] );

	const disableMotion = useReducedMotion();

	let buttonIcon: JSX.Element | null = (
		<Icon size="36px" icon={ wordpress } />
	);

	const effect = {
		expand: {
			scale: 1.25,
			transition: { type: 'tween', duration: '0.3' },
		},
	};

	if ( siteIconUrl ) {
		buttonIcon = (
			<motion.img
				variants={ ! disableMotion && effect }
				alt={ __( 'Site Icon', 'woocommerce' ) }
				className="edit-post-fullscreen-mode-close_site-icon"
				src={ siteIconUrl }
			/>
		);
	}

	if ( isRequestingSiteIcon ) {
		buttonIcon = null;
	}

	// Override default icon if custom icon is provided via props.
	if ( icon ) {
		buttonIcon = <Icon size="36px" icon={ icon } />;
	}

	const classes = classnames( {
		'edit-post-fullscreen-mode-close': true,
		'has-icon': siteIconUrl,
	} );

	const buttonHref =
		href ??
		addQueryArgs( 'edit.php', {
			post_type: 'product',
		} );

	const buttonLabel = __( 'Back', 'woocommerce' );

	return (
		<motion.div whileHover="expand">
			<Button
				className={ classes }
				href={ buttonHref }
				label={ buttonLabel }
				showTooltip={ showTooltip }
			>
				{ buttonIcon }
			</Button>
		</motion.div>
	);
}

export { FullscreenModeClose };
