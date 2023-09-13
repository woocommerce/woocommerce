/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import HelpIcon from './help-icon';
import HiddenIcon from './hidden-icon';

export default function HiddenWithHelpIcon( {
	width = 24,
	height = 24,
	...props
}: React.HTMLProps< HTMLDivElement > ) {
	return (
		<div className="woocommerce-hidden-with-help-icon" { ...props }>
			<HiddenIcon
				className="woocommerce-hidden-with-help-icon__hidden-icon"
				width={ width }
				height={ height }
			/>
			<HelpIcon className="woocommerce-hidden-with-help-icon__help-icon" />
		</div>
	);
}
