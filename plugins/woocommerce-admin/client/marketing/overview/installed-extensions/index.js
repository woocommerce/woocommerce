/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import InstalledExtensionRow from './row';
import { STORE_KEY } from '../../data/constants';

class InstalledExtensions extends Component {
	activatePlugin( pluginSlug ) {
		const { activateInstalledPlugin } = this.props;
		activateInstalledPlugin( pluginSlug );
	}

	isActivatingPlugin( pluginSlug ) {
		const { activatingPlugins } = this.props;
		return activatingPlugins.includes( pluginSlug );
	}

	render() {
		const { plugins } = this.props;

		if ( plugins.length === 0 ) {
			return null;
		}

		return (
			<Card
				title={ __(
					'Installed marketing extensions',
					'woocommerce-admin'
				) }
				className="woocommerce-marketing-installed-extensions-card"
			>
				{ plugins.map( ( plugin ) => {
					return (
						<InstalledExtensionRow
							key={ plugin.slug }
							{ ...plugin }
							activatePlugin={ () =>
								this.activatePlugin( plugin.slug )
							}
							isLoading={ this.isActivatingPlugin( plugin.slug ) }
						/>
					);
				} ) }
			</Card>
		);
	}
}

InstalledExtensions.propTypes = {
	/**
	 * Array of installed plugin objects.
	 */
	plugins: PropTypes.arrayOf( PropTypes.object ).isRequired,
	/**
	 * Array of plugins that are currently activating.
	 */
	activatingPlugins: PropTypes.arrayOf( PropTypes.string ).isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const { getInstalledPlugins, getActivatingPlugins } = select(
			STORE_KEY
		);

		return {
			plugins: getInstalledPlugins(),
			activatingPlugins: getActivatingPlugins(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { activateInstalledPlugin } = dispatch( STORE_KEY );

		return {
			activateInstalledPlugin,
		};
	} )
)( InstalledExtensions );
