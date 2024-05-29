/**
 * Internal dependencies
 */
import { SettingsCheckbox } from './components';

export const Content = ( { data } ) => {
	const { settings } = data;

	return (
		<div>
			{ settings.map( ( setting ) => {
				switch ( setting.type ) {
					case 'title':
						return null;
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
