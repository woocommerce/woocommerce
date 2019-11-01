/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from 'newspack-components';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import { WebPreview } from '@woocommerce/components';

/**
 * Internal depdencies
 */
import { recordEvent } from 'lib/tracks';

const devices = [
	{
		key: 'mobile',
		icon: 'phone_android',
	},
	{
		key: 'tablet',
		icon: 'tablet_android',
	},
	{
		key: 'desktop',
		icon: 'desktop_windows',
	},
];

class ThemePreview extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			device: 'desktop',
		};

		this.handleDeviceClick = this.handleDeviceClick.bind( this );
	}

	handleDeviceClick( device ) {
		const { theme } = this.props;
		recordEvent( 'storeprofiler_store_theme_demo_device', { device, theme: theme.slug } );
		this.setState( { device } );
	}

	render() {
		const { isBusy, onChoose, onClose, theme } = this.props;
		const { demo_url, slug, title } = theme;
		const { device: currentDevice } = this.state;

		return (
			<div className="woocommerce-theme-preview">
				<div className="woocommerce-theme-preview__toolbar">
					<Button className="woocommerce-theme-preview__close" onClick={ onClose }>
						<i className="material-icons-outlined">close</i>
					</Button>
					<div className="woocommerce-theme-preview__theme-name">
						{ interpolateComponents( {
							/* translators: Describing who a previewed theme is developed by. E.g., Storefront developed by WooCommerce */
							mixedString: sprintf(
								__( '{{strong}}%s{{/strong}} developed by WooCommerce', 'woocommerce-admin' ),
								title
							),
							components: {
								strong: <strong />,
							},
						} ) }
					</div>
					<div className="woocommerce-theme-preview__devices">
						{ devices.map( device => (
							<Button
								key={ device.key }
								className={ classnames( 'woocommerce-theme-preview__device', {
									'is-selected': device.key === currentDevice,
								} ) }
								onClick={ () => this.handleDeviceClick( device.key ) }
							>
								<i className="material-icons-outlined">{ device.icon }</i>
							</Button>
						) ) }
					</div>
					<Button isPrimary onClick={ () => onChoose( slug, 'preview' ) } isBusy={ isBusy }>
						{ __( 'Choose', 'woocommerce-admin' ) }
					</Button>
				</div>
				<WebPreview src={ demo_url } title={ title } className={ `is-${ currentDevice }` } />
			</div>
		);
	}
}

export default ThemePreview;
