/**
 * Internal dependencies
 */
import { SettingsCheckbox } from './components';

export const Content = ( { data } ) => {
	const { settings } = data;

	console.log( settings );

	return (
		<div>
			{ settings.map( ( setting, idx ) => {
				const key = setting.id || setting.title || idx;
				switch ( setting.type ) {
					case 'title':
						return (
							<div
								key={ key }
								className="woocommerce-settings-element"
							>
								<h3>{ setting.title }</h3>
							</div>
						);
					case 'checkbox':
						return (
							<SettingsCheckbox setting={ setting } key={ key } />
						);
					default:
						return null;
				}
			} ) }
		</div>
	);
};
