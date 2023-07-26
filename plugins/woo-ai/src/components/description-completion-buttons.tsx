/**
 * External dependencies
 */
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MIN_TITLE_LENGTH_FOR_DESCRIPTION } from '../constants';
import { MagicButton, MagicButtonProps } from '../components';

export const WriteItForMeBtn = ( {
	disabled,
	onClick,
}: Omit< MagicButtonProps, 'title' | 'label' > ) => {
	return (
		<MagicButton
			disabled={ disabled }
			onClick={ onClick }
			label={ __( 'Write with AI', 'woocommerce' ) }
			title={
				disabled
					? sprintf(
							/* translators: %d: Minimum characters for product title */
							__(
								'Please create a product title before generating a description. It must be %d characters or longer.',
								'woocommerce'
							),
							MIN_TITLE_LENGTH_FOR_DESCRIPTION
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
