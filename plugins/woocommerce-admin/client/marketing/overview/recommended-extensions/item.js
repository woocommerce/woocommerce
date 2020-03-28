/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import './style.scss'
import { ProductIcon } from '../../components/';
import { recordEvent } from 'lib/tracks';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import { getNewPath } from '@woocommerce/navigation';

class RecommendedExtensionsItem extends Component {

	onProductClick = () => {
		const { title } = this.props;
		recordEvent( 'marketing_recommended_extension', { name: title } );
	}

	render() {
		const { title, description, icon, url } = this.props;
		const classNameBase = 'woocommerce-marketing-recommended-extensions-item';

		const { connectNonce } = getSetting( 'marketing', {} );

		const connectURL = addQueryArgs( url, {
			'wccom-site': getSetting( 'siteUrl' ),
			'wccom-back': '/wp-admin/' + getNewPath( {} ),
			'wccom-woo-version': getSetting( 'wcVersion' ),
			'wccom-connect-nonce': connectNonce,
		} );

		return (
			<a href={ connectURL }
				className={ classNameBase }
				onClick={ this.onProductClick }
			>
				<ProductIcon src={ icon } />

				<div className={ `${ classNameBase }__text` }>
					<h4>{ title }</h4>
					<p>{ description }</p>
				</div>
			</a>
		)
	}
}

RecommendedExtensionsItem.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	icon: PropTypes.string.isRequired,
	url: PropTypes.string.isRequired,
};

export default RecommendedExtensionsItem;
