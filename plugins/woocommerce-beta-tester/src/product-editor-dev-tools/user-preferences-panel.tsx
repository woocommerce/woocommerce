/**
 * External dependencies
 */
import { useUserPreferences, UserPreferences } from '@woocommerce/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TabPanel } from './tab-panel';

declare global {
	interface Window {
		__wcbt: object;
	}
}

type UserPreferenceProp = keyof UserPreferences;

export function UserPreferencesTabPanel( {
	isSelected,
}: {
	isSelected: boolean;
} ) {
	const { updateUserPreferences, ...userPreferences } = useUserPreferences();

	const update = useCallback(
		(
			preferences: UserPreferences | UserPreferenceProp,
			value?: unknown,
			force = false
		) => {
			const dataToUpdate =
				typeof preferences === 'string'
					? { [ preferences ]: value }
					: preferences;

			/*
			 * When force is not True,
			 * only update the preferences already defined
			 * @todo: consider to implement straight in the data layer.
			 */
			if ( ! force ) {
				Object.keys( dataToUpdate ).forEach( ( key: string ) => {
					const userPreferenceKey = key as UserPreferenceProp;
					if (
						! userPreferences.hasOwnProperty( userPreferenceKey )
					) {
						delete dataToUpdate[ userPreferenceKey ];
					}
				} );
			}

			updateUserPreferences( dataToUpdate );
		},
		[ updateUserPreferences, userPreferences ]
	);

	// Expose updateUserPreferences globaly for debugging purposes.
	window.__wcbt = {
		...window.__wcbt,
		updateUserPreferences: update,
		userPreferences,
	};

	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-user-preferences">
				<div className="woocommerce-product-editor-dev-tools-user-preferences-entity">
					{ JSON.stringify( userPreferences, null, 4 ) }
				</div>
			</div>
		</TabPanel>
	);
}
