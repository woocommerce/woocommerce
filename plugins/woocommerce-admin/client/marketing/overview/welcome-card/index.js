/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { get } from 'lodash';
import { Button } from '@wordpress/components';
import Gridicon from 'gridicons';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { recordEvent } from 'lib/tracks';
import withSelect from 'wc-api/with-select';
import WelcomeImage from './images/welcome.svg';

const WelcomeCard = ( {
	isHidden,
	updateOptions,
} ) => {

	const hide = () => {
		updateOptions( {
			woocommerce_marketing_overview_welcome_hidden: 'yes',
		} );
		recordEvent( 'marketing_intro_close', {} );
	}

	if ( isHidden ) {
		return null;
	}

	return (
		<Card
			className="woocommerce-marketing-overview-welcome-card"
		>
			<Button
				label={ __( 'Hide', 'woocommerce-admin' ) }
				onClick={ hide }
				className="woocommerce-marketing-overview-welcome-card__hide-button"
			>
				<Gridicon icon="cross" />
			</Button>
			<img src={ WelcomeImage } alt="" />
			<h3>{ __( 'Grow your customer base and increase your sales with marketing tools built for WooCommerce', 'woocommerce-admin' ) }</h3>
		</Card>
	)
}

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
export { WelcomeCard }

// default export
export default compose(
	withSelect( ( select ) => {
		const { getOptions, isUpdateOptionsRequesting } = select( 'wc-api' );
		const hideOptionName = 'woocommerce_marketing_overview_welcome_hidden';
		const options = getOptions( [ hideOptionName ] );
		const isHidden = get( options, [ hideOptionName ], 'no' ) === 'yes';
		const isUpdateRequesting = Boolean( isUpdateOptionsRequesting( [ hideOptionName ] ) );
		return {
			isHidden: isHidden || isUpdateRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			updateOptions,
		};
	} )
)( WelcomeCard );
