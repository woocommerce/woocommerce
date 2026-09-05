/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Popover } from '@wordpress/components';
import { Link, Pill } from '@woocommerce/components';
import { createInterpolateElement, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { recordPaymentsEvent } from '~/settings-payments/utils';

interface OfficialBadgeProps {
	/**
	 * The style of the badge.
	 */
	variant: 'expanded' | 'compact';

	/**
	 * The id of the official suggestion.
	 */
	suggestionId: string;
}

/**
 * A component that displays an official badge.
 *
 * The style of the badge can be either "expanded" or "compact".
 *
 * @example
 * // Render an official badge with icon and text.
 * <OfficialBadge variant="expanded" suggestionId="some_id" />
 *
 * @example
 * // Render an official badge with just the icon.
 * <OfficialBadge variant="compact" suggestionId="some_id" />
 */
export const OfficialBadge = ( {
	variant,
	suggestionId,
}: OfficialBadgeProps ) => {
	const [ isPopoverVisible, setPopoverVisible ] = useState( false );
	const buttonRef = useRef< HTMLButtonElement >( null );

	const handleClick = ( event: React.MouseEvent | React.KeyboardEvent ) => {
		const clickedElement = event.target as HTMLElement;
		const parentSpan = clickedElement.closest(
			'.woocommerce-official-extension-badge__container'
		);

		if ( buttonRef.current && parentSpan !== buttonRef.current ) {
			return;
		}

		setPopoverVisible( ( prev ) => ! prev );

		// Record the event when the user clicks on the badge.
		recordPaymentsEvent( 'official_badge_click', {
			suggestion_id: suggestionId,
		} );
	};

	const handleFocusOutside = () => {
		setPopoverVisible( false );
	};

	const handleKeyDown = ( event: React.KeyboardEvent ) => {
		if ( event.key === 'Escape' && isPopoverVisible ) {
			event.stopPropagation();
			setPopoverVisible( false );
			buttonRef.current?.focus();
		} else if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			handleClick( event );
		}
	};

	return (
		<Pill className={ `woocommerce-official-extension-badge` }>
			<span
				className="woocommerce-official-extension-badge__container"
				tabIndex={ 0 }
				role="button"
				ref={ buttonRef }
				onClick={ handleClick }
				onKeyDown={ handleKeyDown }
			>
				<img
					src={ WC_ASSET_URL + 'images/icons/official-extension.svg' }
					alt={ __(
						'Official WooCommerce extension badge',
						'woocommerce'
					) }
				/>
				{ variant === 'expanded' && (
					<span>{ __( 'Official', 'woocommerce' ) }</span>
				) }
				{ isPopoverVisible && (
					<Popover
						className="woocommerce-official-extension-badge-popover"
						placement="top-start"
						offset={ 4 }
						variant="unstyled"
						focusOnMount={ true }
						noArrow={ true }
						shift={ true }
						onFocusOutside={ handleFocusOutside }
						onKeyDown={ handleKeyDown }
					>
						{ /* eslint-disable-next-line jsx-a11y/no-static-element-interactions */ }
						<div
							className="components-popover__content-container"
							onKeyDown={ handleKeyDown }
						>
							<p>
								{ createInterpolateElement(
									__(
										'This is an Official WooCommerce payment extension. <learnMoreLink />',
										'woocommerce'
									),
									{
										learnMoreLink: (
											<Link
												href="https://woocommerce.com/learn-more-about-official-partner-badging/"
												target="_blank"
												rel="noreferrer"
												type="external"
												onClick={ () => {
													// Record the event when the user clicks on the learn more link.
													recordPaymentsEvent(
														'official_badge_learn_more_click',
														{
															suggestion_id:
																suggestionId,
														}
													);
												} }
											>
												{ __(
													'Learn more',
													'woocommerce'
												) }
											</Link>
										),
									}
								) }
							</p>
						</div>
					</Popover>
				) }
			</span>
		</Pill>
	);
};
