/**
 * External dependencies
 */
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import { MIN_TITLE_LENGTH } from '../constants';

type MagicButtonProps = {
	title?: string;
	disabled?: boolean;
	onClick: () => void;
	label: string;
};

const MagicButton = ( {
	title,
	label,
	onClick,
	disabled = false,
}: MagicButtonProps ) => {
	return (
		<button
			className="button wp-media-button woo-ai-write-it-for-me-btn"
			type="button"
			disabled={ disabled }
			title={ title }
			onClick={ onClick }
		>
			<img src={ MagicIcon } alt="" />
			{ label }
		</button>
	);
};

export const WriteItForMeBtn = ( {
	disabled,
	onClick,
}: Omit< MagicButtonProps, 'title' | 'label' > ) => {
	return (
		<MagicButton
			disabled={ disabled }
			onClick={ onClick }
			label={ __( 'Write it for me', 'woocommerce' ) }
			title={
				disabled
					? sprintf(
							/* translators: %d: Minimum characters for product title */
							__(
								'Please create a product title before generating a description. It must be %d characters or longer.',
								'woocommerce'
							),
							MIN_TITLE_LENGTH
					  )
					: undefined
			}
		/>
	);
};

export const StopCompletionBtn = ( {
	disabled,
	onClick,
}: Omit< MagicButtonProps, 'title' | 'label' > ) => {
	return (
		<MagicButton
			disabled={ disabled }
			onClick={ onClick }
			label={ __( 'Stop writing…', 'woocommerce' ) }
			title={ __( 'Stop generating the description.', 'woocommerce' ) }
		/>
	);
};
