/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { WebPreview } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { Icon, close } from '@wordpress/icons';
import Phone from 'gridicons/dist/phone';
import Tablet from 'gridicons/dist/tablet';
import Computer from 'gridicons/dist/computer';

/**
 * Internal dependencies
 */

const devices = [
	{
		key: 'mobile',
		icon: Phone,
	},
	{
		key: 'tablet',
		icon: Tablet,
	},
	{
		key: 'desktop',
		icon: Computer,
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
		recordEvent( 'storeprofiler_store_theme_demo_device', {
			device,
			theme: theme.slug,
		} );
		this.setState( { device } );
	}

	render() {
		const { isBusy, onChoose, onClose, theme } = this.props;
		const { demo_url: demoUrl, slug, title } = theme;
		const { device: currentDevice } = this.state;

		return (
			<div className="woocommerce-theme-preview">
				<div className="woocommerce-theme-preview__toolbar">
					<Button
						className="woocommerce-theme-preview__close"
						onClick={ onClose }
					>
						<Icon icon={ close } />
					</Button>
					<div className="woocommerce-theme-preview__theme-name">
						{ interpolateComponents( {
							/* translators: Describing who a previewed theme is developed by. E.g., Storefront developed by WooCommerce */
							mixedString: sprintf(
								__(
									'{{strong}}%s{{/strong}} developed by WooCommerce',
									'woocommerce'
								),
								title
							),
							components: {
								strong: <strong />,
							},
						} ) }
					</div>
					<div className="woocommerce-theme-preview__devices">
						{ devices.map( ( device ) => {
							const DeviceTag = device.icon;
							return (
								<Button
									key={ device.key }
									className={ classnames(
										'woocommerce-theme-preview__device',
										{
											'is-selected':
												device.key === currentDevice,
										}
									) }
									onClick={ () =>
										this.handleDeviceClick( device.key )
									}
								>
									<DeviceTag />
								</Button>
							);
						} ) }
					</div>
					<Button
						isPrimary
						onClick={ () => onChoose( slug, 'preview' ) }
						isBusy={ isBusy }
					>
						{ __( 'Choose', 'woocommerce' ) }
					</Button>
				</div>
				<WebPreview
					src={ demoUrl }
					title={ title }
					className={ `is-${ currentDevice }` }
				/>
			</div>
		);
	}
}

export default ThemePreview;
