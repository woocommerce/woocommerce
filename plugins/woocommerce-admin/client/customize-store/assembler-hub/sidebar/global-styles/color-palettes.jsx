// Reference: https://github.com/WordPress/gutenberg/blob/d5ab7238e53d0947d4bb0853464b1c58325b6130/packages/edit-site/src/components/global-styles/style-variations-container.js
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useMemo, useContext } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import { __experimentalGrid as Grid } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { privateApis as blockEditorPrivateApis } from '@wordpress/block-editor';
import { mergeBaseAndUserConfigs } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { ColorPaletteVariationPreview } from './color-palette-variations-preview';
import { COLOR_PALETTES } from './constants';

const { GlobalStylesContext, areGlobalStyleConfigsEqual } = unlock(
	blockEditorPrivateApis
);

const Variation = ( { variation } ) => {
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
		setUserConfig( () => {
			return {
				settings: variation.settings,
				styles: variation.styles,
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
		return areGlobalStyleConfigsEqual( user, variation );
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
					<ColorPaletteVariationPreview title={ variation?.title } />
				</div>
			</div>
		</GlobalStylesContext.Provider>
	);
};

export const ColorPalettes = () => {
	return (
		<Grid
			columns={ 3 }
			gap={ 4 }
			className="woocommerce-customize-store_global-styles-style-variations-container"
		>
			{ COLOR_PALETTES.map( ( variation, index ) => (
				<Variation key={ index } variation={ variation } />
			) ) }
		</Grid>
	);
};
