/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './plugin-banner.scss';

type Feature = {
	icon: string;
	title: string;
	description: string;
};

type PluginBannerProps = {
	logo: {
		image: string;
		alt?: string;
	};
	features: Array< Feature >;
};

export const PluginBanner = ( { logo, features }: PluginBannerProps ) => {
	return (
		<div className="woocommerce-task-shipping-recommendation__plugins-install">
			<div className="plugins-install__plugin-banner-image">
				<img src={ logo.image } alt={ logo?.alt } />
			</div>
			<div className="plugins-install__list">
				{ features.map( ( feature: Feature, index ) => {
					return (
						<div
							className="plugins-install__list-item"
							key={ index }
						>
							<div className="plugins-install__list-icon">
								<img src={ feature.icon } alt="" />
							</div>
							<div>
								<div>
									<strong>{ feature.title }</strong>
								</div>
								<div>{ feature.description }</div>
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
};
