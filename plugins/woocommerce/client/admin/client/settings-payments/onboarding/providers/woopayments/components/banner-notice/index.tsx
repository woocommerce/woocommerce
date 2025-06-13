/**
 * Based on the @wordpress/components `Notice` component.
 * Adjusted to meet WooCommerce Admin Design Library.
 */

/**
 * External dependencies
 */
import React, { ComponentProps } from 'react';
import { __ } from '@wordpress/i18n';
import { useEffect, renderToString } from '@wordpress/element';
import { speak } from '@wordpress/a11y';
import clsx from 'clsx';
import { Button } from '@wordpress/components';
import { check, info } from '@wordpress/icons';
import NoticeOutlineIcon from 'gridicons/dist/notice-outline';
import NoticeIcon from 'gridicons/dist/notice';
import CloseIcon from 'gridicons/dist/cross-small';

/**
 * Internal dependencies
 */
import './style.scss';

const statusIconMap = {
	success: check,
	error: NoticeIcon,
	warning: NoticeOutlineIcon,
	info,
};

type Status = keyof typeof statusIconMap;

/**
 * Custom hook which announces the message with politeness based on status,
 * if a valid message is provided.
 */
const useSpokenMessage = ( status?: string, message?: React.ReactNode ) => {
	const spokenMessage =
		typeof message === 'string' ? message : renderToString( message );
	const politeness = status === 'error' ? 'assertive' : 'polite';

	useEffect( () => {
		if ( spokenMessage ) {
			speak( spokenMessage, politeness );
		}
	}, [ spokenMessage, politeness ] );
};

interface Props {
	/**
	 * A CSS `class` to give to the wrapper element.
	 */
	className?: string;
	/**
	 * The displayed message of a notice. Also used as the spoken message for
	 * assistive technology, unless `spokenMessage` is provided as an alternative message.
	 */
	children: React.ReactNode;
	/**
	 * Determines the color of the notice: `warning` (yellow),
	 * `success` (green), `error` (red), or `'info'`.
	 * By default `'info'` will be blue, but if there is a parent Theme component
	 * with an accent color prop, the notice will take on that color instead.
	 *
	 * @default 'info'
	 */
	status?: Status;
	/**
	 * Whether the notice should be dismissible or not.
	 *
	 * @default true
	 */
	isDismissible?: boolean;
	/**
	 * An array of action objects. Each member object should contain:
	 *
	 * - `label`: `string` containing the text of the button/link
	 * - `url`: `string` OR `onClick`: `( event: SyntheticEvent ) => void` to specify
	 *    what the action does.
	 * - `urlTarget`: `string` (optional) to specify the target attribute of the link.
	 * - `className`: `string` (optional) to add custom classes to the button styles.
	 * - `variant`: `'primary' | 'secondary' | 'link'` (optional) You can denote a
	 *    primary button action for a notice by passing a value of `primary`.
	 *
	 * The default appearance of an action button is inferred based on whether
	 * `url` or `onClick` are provided, rendering the button as a link if
	 * appropriate. If both props are provided, `url` takes precedence, and the
	 * action button will render as an anchor tag.
	 *
	 * @default []
	 */
	actions?: ReadonlyArray< {
		label: string;
		className?: string;
		variant?: ComponentProps< typeof Button >[ 'variant' ];
		url?: string;
		urlTarget?: string;
		onClick?: React.MouseEventHandler< HTMLAnchorElement >;
	} >;
	/**
	 * Function called when dismissing the notice
	 *
	 * @default undefined
	 */
	onRemove?: () => void;
}

const BannerNotice: React.FC< Props > = ( {
	children,
	actions = [],
	className,
	status = 'info',
	isDismissible = true,
	onRemove,
} ) => {
	useSpokenMessage( status, children );

	const classes = clsx(
		className,
		'woopayments-banner-notice',
		'is-' + status
	);

	const handleRemove = () => onRemove?.();

	return (
		<div className={ classes }>
			<div className="woopayments-banner-notice__content">
				{ children }
				{ actions.length > 0 && (
					<div className="woopayments-banner-notice__actions">
						{ actions.map(
							(
								{
									className: buttonCustomClasses,
									label,
									variant,
									onClick,
									url,
									urlTarget,
								},
								index
							) => {
								let computedVariant = variant;
								if ( variant !== 'primary' ) {
									computedVariant = ! url
										? 'secondary'
										: 'link';
								}

								return (
									<Button
										key={ index }
										href={ url as string }
										variant={ computedVariant }
										onClick={ url ? undefined : onClick }
										className={ buttonCustomClasses }
										target={ urlTarget }
									>
										{ label }
									</Button>
								);
							}
						) }
					</div>
				) }
			</div>
			{ isDismissible && (
				<Button
					className="woopayments-banner-notice__dismiss"
					icon={ <CloseIcon /> }
					label={ __( 'Dismiss this notice', 'woocommerce' ) }
					onClick={ handleRemove }
					showTooltip={ false }
				/>
			) }
		</div>
	);
};

export default BannerNotice;
