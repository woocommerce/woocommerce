/**
 * Internal dependencies
 */
import { SettingsCheckbox } from './components';

export const Content = ( { data } ) => {
	const { settings } = data;

	console.log( settings );

	return (
		<div>
			{ settings.map( ( setting ) => {
				switch ( setting.type ) {
					case 'title':
						return (
							<div
								key={ setting.id }
								className="woocommerce-settings-element"
							>
								<h3>{ setting.title }</h3>
							</div>
						);
					case 'checkbox':
						return (
							<SettingsCheckbox
								setting={ setting }
								key={ setting.id }
							/>
						);
					default:
						return null;
				}
			} ) }
		</div>
	);
};
