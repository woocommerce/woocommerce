/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './icon-with-text.scss';

export interface IconWithTextProps {
	icon: string;
	text: string;
}

export default function IconWithText( props: IconWithTextProps ): JSX.Element {
	const { icon, text } = props;
	return (
		<span className="icon-group">
			<span className="icon-group__icon-background">
				<img
					className="icon"
					src={ icon }
					alt={ sprintf(
						// translators: %s is the screen reader text for the icon
						__( '%s icon', 'woocommerce' ),
						icon
					) }
				/>
			</span>
			<p>{ text }</p>
		</span>
	);
}
