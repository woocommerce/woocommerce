// Reference: https://github.com/WordPress/gutenberg/blob/d5ab7238e53d0947d4bb0853464b1c58325b6130/packages/edit-site/src/components/global-styles/style-variations-container.js
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useMemo, useContext } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import { __, sprintf } from '@wordpress/i18n';
import {
	privateApis as blockEditorPrivateApis,
	BlockEditorProvider,
} from '@wordpress/block-editor';
import { mergeBaseAndUserConfigs } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { isEqual, noop } from 'lodash';

const { GlobalStylesContext } = unlock( blockEditorPrivateApis );

// Removes the typography settings from the styles when the user is changing
// to a new typography variation. Otherwise, some of the user's old
// typography settings will persist making new typography settings
// depend on old ones
const resetTypographySettings = ( variation, userStyles ) => {
	if ( variation.settings.typography ) {
		delete userStyles.typography;
		for ( const elementKey in userStyles.elements ) {
			if ( userStyles.elements[ elementKey ].typography ) {
				delete userStyles.elements[ elementKey ].typography;
			}
		}
	}

	return userStyles;
};

// mergeBaseAndUserConfigs is just a wrapper around deepmerge library: https://github.com/WordPress/gutenberg/blob/237865fad0864c209a7c3e771e23fe66f4fbca25/packages/edit-site/src/components/global-styles/global-styles-provider.js/#L24-L31
// Deepmerge library merges two objects x and y deeply, returning a new merged object with the elements from both x and y.
// In the case of the variation.title === 'New - Neutral', the core/button is an empty object, because we don't want that the classes for the core/button are created.
// Deepmerge merges the userStyles.blocks[ 'core/button' ] with the variation.styles.blocks[ 'core/button' ] and the result is an object with values that doesn't match with the variation. For this reason it is necessary remove the userStyles.blocks[ 'core/button' ].
const resetStyleSettings = ( variation, userStyles ) => {
	if ( variation.title === 'New - Neutral' ) {
		delete userStyles.blocks[ 'core/button' ];
	}
	return userStyles;
};

export const VariationContainer = ( { variation, children } ) => {
	const { base, user, setUserConfig } = useContext( GlobalStylesContext );
	const context = useMemo( () => {
		return {
			user: {
				settings: variation.settings ?? {},
				styles: variation.styles ?? {},
			},
			base,
			merged: mergeBaseAndUserConfigs( base, variation ),
			setUserConfig: () => {},
		};
	}, [ variation, base ] );

	const selectVariation = () => {
		// Remove the hasCreatedOwnColors flag if the user is switching to a color palette
		// hasCreatedOwnColors flag is used for visually deselecting preset color palettes if user has created their own
		if (
			variation.settings.color &&
			user.settings.color &&
			user.settings.color.palette.hasCreatedOwnColors
		) {
			delete user.settings.color.palette.hasCreatedOwnColors;
			// some color palettes don't define all the possible color options, e.g headings and captions
			// if the user selects a pre-defined color palette with some own colors defined for these,
			// we need to delete these user customizations as the below merge will persist them since
			// the incoming variation won't have these properties defined
			delete user.styles.color;
			for ( const elementKey in user.styles.elements ) {
				if ( user.styles.elements[ elementKey ].color ) {
					delete user.styles.elements[ elementKey ].color;
				}
			}
		}

		const resetTypographySettingsStyles = resetTypographySettings(
			variation,
			user.styles
		);
		const resetStyleSettingsStyles = resetStyleSettings(
			variation,
			resetTypographySettingsStyles
		);

		setUserConfig( () => {
			return {
				settings: mergeBaseAndUserConfigs(
					user.settings,
					variation.settings
				),
				styles: mergeBaseAndUserConfigs(
					resetStyleSettingsStyles,
					variation.styles
				),
			};
		} );
	};

	const selectOnEnter = ( event ) => {
		if ( event.keyCode === ENTER ) {
			event.preventDefault();
			selectVariation();
		}
	};
	const isActive = useMemo( () => {
		if ( variation.settings.color ) {
			return isEqual( variation.settings.color, user.settings.color );
		}
		// With the Font Library, the fontFamilies object contains an array of font families installed with the Font Library under the key 'custom'.
		// We need to compare only the active theme font families, so we compare the theme font families with the current variation.
		const { theme } = user.settings.typography.fontFamilies;
		return (
			variation.settings.typography?.fontFamilies.theme.every(
				( { slug } ) =>
					theme.some( ( { slug: themeSlug } ) => themeSlug === slug )
			) &&
			theme.length ===
				variation.settings.typography?.fontFamilies.theme.length
		);
	}, [ user, variation ] );

	let label = variation?.title;
	if ( variation?.description ) {
		label = sprintf(
			/* translators: %1$s: variation title. %2$s variation description. */
			__( '%1$s (%2$s)', 'woocommerce' ),
			variation?.title,
			variation?.description
		);
	}

	return (
		<BlockEditorProvider
			onChange={ noop }
			onInput={ noop }
			settings={ {} }
			useSubRegistry={ true }
		>
			<GlobalStylesContext.Provider value={ context }>
				<div
					className={ classnames(
						'woocommerce-customize-store_global-styles-variations_item',
						{
							'is-active': isActive,
						}
					) }
					role="button"
					onClick={ selectVariation }
					onKeyDown={ selectOnEnter }
					tabIndex="0"
					aria-label={ label }
					aria-current={ isActive }
				>
					<div className="woocommerce-customize-store_global-styles-variations_item-preview">
						{ children }
					</div>
				</div>
			</GlobalStylesContext.Provider>
		</BlockEditorProvider>
	);
};
