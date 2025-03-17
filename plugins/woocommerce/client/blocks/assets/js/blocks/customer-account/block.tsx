/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import {
	customerAccountStyle,
	customerAccountStyleAlt,
	customerAccountStyleLine,
} from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Attributes, DisplayStyle, IconStyle } from './types';

const icons = {
	default: customerAccountStyle,
	alt: customerAccountStyleAlt,
	line: customerAccountStyleLine,
};

const AccountIcon = ( {
	iconStyle,
	displayStyle,
	iconClass,
}: {
	iconStyle: IconStyle;
	displayStyle: DisplayStyle;
	iconClass: string;
} ) => {
	return displayStyle === DisplayStyle.TEXT_ONLY ? null : (
		<Icon className={ iconClass } icon={ icons[ iconStyle ] } size={ 18 } />
	);
};

const Label = ( { displayStyle }: { displayStyle: DisplayStyle } ) => {
	if ( displayStyle === DisplayStyle.ICON_ONLY ) {
		return null;
	}

	const currentUserId = getSetting( 'currentUserId', null );

	return (
		<span className="label">
			{ currentUserId
				? __( 'My Account', 'woocommerce' )
				: __( 'Log in', 'woocommerce' ) }
		</span>
	);
};

export const CustomerAccountBlock = ( {
	attributes,
}: {
	attributes: Attributes;
} ): JSX.Element => {
	const { displayStyle, iconStyle, iconClass } = attributes;

	const ariaAttributes =
		displayStyle === DisplayStyle.ICON_ONLY
			? {
					'aria-label': __( 'My Account', 'woocommerce' ),
			  }
			: {};

	return (
		<a
			href={ getSetting(
				'dashboardUrl',
				getSetting( 'wpLoginUrl', '/wp-login.php' )
			) }
			{ ...ariaAttributes }
		>
			<AccountIcon
				iconStyle={ iconStyle }
				displayStyle={ displayStyle }
				iconClass={ iconClass }
			/>
			<Label displayStyle={ displayStyle } />
		</a>
	);
};

export default CustomerAccountBlock;
