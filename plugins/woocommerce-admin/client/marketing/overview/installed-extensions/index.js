/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';
import { Card, CardHeader } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

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

		const title = __( 'Installed marketing extensions', 'woocommerce' );

		return (
			<Card className="woocommerce-marketing-installed-extensions-card">
				<CardHeader>
					<Text variant="title.small" size="20" lineHeight="28px">
						{ title }
					</Text>
				</CardHeader>
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
		const { getInstalledPlugins, getActivatingPlugins } =
			select( STORE_KEY );

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
