/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';

type MagicButtonProps = {
	title?: string;
	disabled?: boolean;
	onClick: () => void;
	label: string;
};

type WriteItForMeBtnProps = MagicButtonProps & {
	disabledMessage?: string;
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
	disabledMessage,
}: Omit< WriteItForMeBtnProps, 'title' | 'label' > ) => {
	return (
		<MagicButton
			disabled={ disabled }
			onClick={ onClick }
			label={ __( 'Write with AI', 'woocommerce' ) }
			title={ disabled ? disabledMessage : undefined }
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
			title={ __( 'Stop generating content.', 'woocommerce' ) }
		/>
	);
};
