/**
 * External dependencies
 */
import clsx from 'clsx';
/**
 * Internal dependencies
 */
import './plugin-banner.scss';

type Feature = {
	icon: string;
	title?: string;
	description: string;
};

type PluginBannerProps = {
	logo?: {
		image: string;
		alt?: string;
	};
	description?: string;
	layout?: 'single' | 'dual';
	features: Array< Feature >;
	children?: React.ReactNode;
};

export const PluginBanner = ( {
	logo,
	description,
	layout = 'single',
	features,
	children,
}: PluginBannerProps ) => {
	return (
		<div
			className={ clsx(
				'woocommerce-task-shipping-recommendation__plugins-install',
				layout
			) }
		>
			{ logo && (
				<div className="plugins-install__plugin-banner-image">
					<img src={ logo.image } alt={ logo?.alt } />
				</div>
			) }
			{ description && <p>{ description }</p> }
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
								{ feature.title && (
									<div>
										<strong>{ feature.title }</strong>
									</div>
								) }
								<div>{ feature.description }</div>
							</div>
						</div>
					);
				} ) }
			</div>
			{ children }
		</div>
	);
};
