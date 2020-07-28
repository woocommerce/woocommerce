/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import Gridicon from 'gridicons';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import { recordEvent } from 'lib/tracks';
import WelcomeImage from './images/welcome.svg';

const WelcomeCard = ( { isHidden, updateOptions } ) => {
	const hide = () => {
		updateOptions( {
			woocommerce_marketing_overview_welcome_hidden: 'yes',
		} );
		recordEvent( 'marketing_intro_close', {} );
	};

	if ( isHidden ) {
		return null;
	}

	return (
		<Card className="woocommerce-marketing-overview-welcome-card">
			<Button
				label={ __( 'Hide', 'woocommerce-admin' ) }
				onClick={ hide }
				className="woocommerce-marketing-overview-welcome-card__hide-button"
			>
				<Gridicon icon="cross" />
			</Button>
			<img src={ WelcomeImage } alt="" />
			<h3>
				{ __(
					'Grow your customer base and increase your sales with marketing tools built for WooCommerce',
					'woocommerce-admin'
				) }
			</h3>
		</Card>
	);
};

WelcomeCard.propTypes = {
	/**
	 * Whether the card is hidden.
	 */
	isHidden: PropTypes.bool.isRequired,
	/**
	 * updateOptions function.
	 */
	updateOptions: PropTypes.func.isRequired,
};

// named export
export { WelcomeCard };

// default export
export default compose(
	withSelect( ( select ) => {
		const { getOption, isOptionsUpdating } = select( OPTIONS_STORE_NAME );
		const isUpdateRequesting = isOptionsUpdating();

		return {
			isHidden:
				getOption( 'woocommerce_marketing_overview_welcome_hidden' ) ===
					'yes' || isUpdateRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		return {
			updateOptions,
		};
	} )
)( WelcomeCard );
