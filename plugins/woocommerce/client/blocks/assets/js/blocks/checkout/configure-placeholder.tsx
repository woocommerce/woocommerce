/**
 * External dependencies
 */
import { Placeholder, Button } from '@wordpress/components';
import { Icon, blockDefault } from '@wordpress/icons';
import PALETTE from '@automattic/color-studio';

/**
 * Placeholder component that links to a settings page to configure something.
 */
const ConfigurePlaceholder = ( {
	label,
	description,
	buttonLabel,
	buttonHref,
	icon = blockDefault,
}: {
	label: string;
	description: string;
	buttonLabel: string;
	buttonHref: string;
	icon?: Icon;
} ) => {
	return (
		<Placeholder
			icon={ <Icon icon={ icon } /> }
			label={ label }
			className="wc-block-checkout__configure-placeholder"
		>
			<span className="wc-block-checkout__configure-placeholder-description">
				{ description }
			</span>
			<Button
				variant="primary"
				href={ buttonHref }
				target="_blank"
				rel="noopener noreferrer"
				style={ {
					backgroundColor: PALETTE.colors[ 'Gray 100' ],
					color: PALETTE.colors.White,
					pointerEvents: 'all',
				} }
			>
				{ buttonLabel }
			</Button>
		</Placeholder>
	);
};

export default ConfigurePlaceholder;
