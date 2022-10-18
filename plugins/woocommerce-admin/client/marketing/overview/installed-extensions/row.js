/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Button, ProductIcon } from '../../components';

class InstalledExtensionRow extends Component {
	constructor( props ) {
		super( props );

		this.onActivateClick = this.onActivateClick.bind( this );
		this.onFinishSetupClick = this.onFinishSetupClick.bind( this );
	}

	getLinks() {
		const { docsUrl, settingsUrl, supportUrl, dashboardUrl } = this.props;
		const links = [];

		if ( docsUrl ) {
			links.push( {
				key: 'docs',
				href: docsUrl,
				text: __( 'Docs', 'woocommerce' ),
			} );
		}
		if ( supportUrl ) {
			links.push( {
				key: 'support',
				href: supportUrl,
				text: __( 'Get support', 'woocommerce' ),
			} );
		}
		if ( settingsUrl ) {
			links.push( {
				key: 'settings',
				href: settingsUrl,
				text: __( 'Settings', 'woocommerce' ),
			} );
		}
		if ( dashboardUrl ) {
			links.push( {
				key: 'dashboard',
				href: dashboardUrl,
				text: __( 'Dashboard', 'woocommerce' ),
			} );
		}

		return (
			<ul className="woocommerce-marketing-installed-extensions-card__item-links">
				{ links.map( ( link ) => {
					return (
						<li key={ link.key }>
							<Link
								href={ link.href }
								type="external"
								onClick={ this.onLinkClick.bind( this, link ) }
							>
								{ link.text }
							</Link>
						</li>
					);
				} ) }
			</ul>
		);
	}

	onLinkClick( link ) {
		const { name } = this.props;
		recordEvent( 'marketing_installed_options', { name, link: link.key } );
	}

	onActivateClick() {
		const { activatePlugin, name } = this.props;
		recordEvent( 'marketing_installed_activate', { name } );
		activatePlugin();
	}

	onFinishSetupClick() {
		const { name } = this.props;
		recordEvent( 'marketing_installed_finish_setup', { name } );
	}

	getActivateButton() {
		const { isLoading } = this.props;

		return (
			<Button
				isSecondary
				onClick={ this.onActivateClick }
				disabled={ isLoading }
			>
				{ __( 'Activate', 'woocommerce' ) }
			</Button>
		);
	}

	getFinishSetupButton() {
		return (
			<Button
				isSecondary
				href={ this.props.settingsUrl }
				onClick={ this.onFinishSetupClick }
			>
				{ __( 'Finish setup', 'woocommerce' ) }
			</Button>
		);
	}

	render() {
		const { name, description, status, slug } = this.props;
		let actions = null;

		switch ( status ) {
			case 'installed':
				actions = this.getActivateButton();
				break;
			case 'activated':
				actions = this.getFinishSetupButton();
				break;
			case 'configured':
				actions = this.getLinks();
				break;
		}

		return (
			<div className="woocommerce-marketing-installed-extensions-card__item">
				<ProductIcon product={ slug } />
				<div className="woocommerce-marketing-installed-extensions-card__item-text-and-actions">
					<div className="woocommerce-marketing-installed-extensions-card__item-text">
						<h4>{ name }</h4>
						{ status === 'configured' || (
							<p className="woocommerce-marketing-installed-extensions-card__item-description">
								{ description }
							</p>
						) }
					</div>
					<div className="woocommerce-marketing-installed-extensions-card__item-actions">
						{ actions }
					</div>
				</div>
			</div>
		);
	}
}

InstalledExtensionRow.defaultProps = {
	isLoading: false,
};

InstalledExtensionRow.propTypes = {
	name: PropTypes.string.isRequired,
	slug: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	status: PropTypes.string.isRequired,
	settingsUrl: PropTypes.string,
	docsUrl: PropTypes.string,
	supportUrl: PropTypes.string,
	dashboardUrl: PropTypes.string,
	activatePlugin: PropTypes.func.isRequired,
};

export default InstalledExtensionRow;
