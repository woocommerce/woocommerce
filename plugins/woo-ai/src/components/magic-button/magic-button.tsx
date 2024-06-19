/**
 * Internal dependencies
 */
import MagicIcon from '../../../assets/images/icons/magic.svg';

export type MagicButtonProps = {
	title?: string;
	disabled?: boolean;
	onClick: () => void;
	label: string;
};

export const MagicButton = ( {
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
