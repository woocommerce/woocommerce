/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, closeSmall } from '@wordpress/icons';
import type { ComponentProps } from 'react';

declare module '@wordpress/icons' {
	interface IconProps extends Partial< ComponentProps< 'div' > > {
		icon: JSX.Element;
		size?: number;
	}
}

/**
 * Internal dependencies
 */
import Chip, { ChipProps } from './chip';

export interface RemovableChipProps extends ChipProps {
	/**
	 * Aria label content.
	 */
	ariaLabel?: string;
	/**
	 * CSS class used.
	 */
	className?: string;
	/**
	 * Whether action is disabled or not.
	 */
	disabled?: boolean;
	/**
	 * Function to call when remove event is fired.
	 */
	onRemove?: () => void;
	/**
	 * Whether to expand click area for remove event.
	 */
	removeOnAnyClick?: boolean;
}

/**
 * Component used to render a "chip" -- an item containing some text with
 * an X button to remove/dismiss each chip.
 */
export const RemovableChip = ( {
	ariaLabel = '',
	className = '',
	disabled = false,
	onRemove = () => void 0,
	removeOnAnyClick = false,
	text,
	screenReaderText = '',
	...props
}: RemovableChipProps ): JSX.Element => {
	const RemoveElement = removeOnAnyClick ? 'span' : 'button';

	if ( ! ariaLabel ) {
		const ariaLabelText =
			screenReaderText && typeof screenReaderText === 'string'
				? screenReaderText
				: text;
		ariaLabel =
			typeof ariaLabelText !== 'string'
				? /* translators: Remove chip. */
				  __( 'Remove', 'woocommerce' )
				: sprintf(
						/* translators: %s text of the chip to remove. */
						__( 'Remove "%s"', 'woocommerce' ),
						ariaLabelText
				  );
	}

	const clickableElementProps = {
		'aria-label': ariaLabel,
		disabled,
		onClick: onRemove,
		onKeyDown: ( e: React.KeyboardEvent ) => {
			if ( e.key === 'Backspace' || e.key === 'Delete' ) {
				onRemove();
			}
		},
	};

	const chipProps = removeOnAnyClick ? clickableElementProps : {};
	const removeProps = removeOnAnyClick
		? { 'aria-hidden': true }
		: clickableElementProps;

	return (
		<Chip
			{ ...props }
			{ ...chipProps }
			className={ clsx( className, 'is-removable' ) }
			element={ removeOnAnyClick ? 'button' : props.element || 'li' }
			screenReaderText={ screenReaderText }
			text={ text }
		>
			<RemoveElement
				className="wc-block-components-chip__remove"
				{ ...removeProps }
			>
				{ /* @ts-expect-error - TS wants the Icon component to define svg specific props, but it's not always SVG */ }
				<Icon
					className="wc-block-components-chip__remove-icon"
					icon={ closeSmall }
					size={ 16 }
					role="img"
				/>
			</RemoveElement>
		</Chip>
	);
};

export default RemovableChip;
