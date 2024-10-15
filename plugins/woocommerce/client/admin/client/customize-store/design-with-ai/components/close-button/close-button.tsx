/**
 * External dependencies
 */
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './close-button.scss';

type Props = {
	onClick?: () => void;
};

export const CloseButton = ( { onClick }: Props ) => {
	return (
		<div>
			<Button
				className="close-cys-design-with-ai"
				onClick={ onClick ? onClick : () => {} }
			>
				<svg
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						d="M5.40456 5L19 19M5 19L18.5954 5"
						stroke="#1E1E1E"
						strokeWidth="1.5"
					/>
				</svg>
			</Button>
		</div>
	);
};
