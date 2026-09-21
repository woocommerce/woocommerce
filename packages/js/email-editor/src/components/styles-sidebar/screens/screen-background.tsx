/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';
import { useEmailStyles } from '../../../hooks';
import { useBackgroundScreenSettings } from '../hooks';
import { recordEvent, recordEventOnce } from '../../../events';
import { StylesBackgroundPanel } from '../../../private-apis';

export function ScreenBackground(): JSX.Element {
	recordEventOnce( 'styles_sidebar_screen_background_opened' );
	const { userStyles, styles, updateStyles } = useEmailStyles();
	const settings = useBackgroundScreenSettings();

	const handleOnChange = ( newStyles ) => {
		updateStyles( newStyles );
		recordEvent( 'styles_sidebar_screen_background_styles_updated' ); // We can't log the updated color here because the onChange function returns the complete object.
	};

	return (
		<>
			<ScreenHeader
				title={ __( 'Background', __i18n_text_domain__ ) }
				description={ __(
					'Manage the background color of the email.',
					__i18n_text_domain__
				) }
			/>
			<StylesBackgroundPanel
				value={ userStyles }
				inheritedValue={ styles }
				onChange={ handleOnChange }
				settings={ settings }
				panelId="background"
			/>
		</>
	);
}
