/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';
import CrossIcon from 'gridicons/dist/cross';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
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
			<CardBody>
				<Button
					label={ __( 'Hide', 'woocommerce' ) }
					onClick={ hide }
					className="woocommerce-marketing-overview-welcome-card__hide-button"
				>
					<CrossIcon />
				</Button>
				<img src={ WelcomeImage } alt="" />
				<h3>
					{ __(
						'Grow your customer base and increase your sales with marketing tools built for WooCommerce',
						'woocommerce'
					) }
				</h3>
			</CardBody>
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
