/**
 * Internal dependencies
 */
import { SettingsCheckbox } from './components';

export const Content = ( { data } ) => {
	const { settings } = data;

	console.log( settings );

	if ( ! settings ) {
		return null;
	}

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
					case 'slotfill_placeholder':
						return (
							<div
								key={ key }
								id={ setting.id }
								className={ setting.class }
							></div>
						);
					default:
						return null;
				}
			} ) }
		</div>
	);
};
