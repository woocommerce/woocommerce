/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import {
	useBlockProps,
	withColors,
	InspectorControls,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import { EditProps } from './types';
import { getColorClasses, getColorVars } from './utils';

const DropdownEdit = ( props: EditProps ): JSX.Element => {
	const {
		clientId,
		context,
		attributes,
		setAttributes,
		containerBackground,
		setContainerBackground,
		containerBorder,
		setContainerBorder,
		badgeBackground,
		setBadgeBackground,
		badgeText,
		setBadgeText,
		placeholderText,
		setPlaceholderText,
	} = props;

	const {
		customContainerBackground,
		customContainerBorder,
		customBadgeBackground,
		customBadgeText,
		customPlaceholderText,
	} = attributes;
	const { filterData } = context;
	const { isLoading, items, label } = filterData;

	const colorGradientSettings = useMultipleOriginColorsAndGradients();
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-dropdown', {
			'is-loading': isLoading,
			...getColorClasses( attributes ),
		} ),
		style: getColorVars( attributes ),
	} );

	if ( ! items ) {
		return <></>;
	}

	return (
		<>
			<div { ...blockProps }>
				<Disabled>
					<div className="wc-block-product-filter-dropdown__dropdown">
						<div
							className="wc-block-product-filter-dropdown__dropdown-selection"
							tabIndex={ 0 }
						>
							{ ! items.some( ( item ) => item.selected ) && (
								<span className="wc-block-product-filter-dropdown__placeholder">
									{ sprintf(
										/* translators: %s filter name. */
										__( 'Select %s', 'woocommerce' ),
										label
									) }
								</span>
							) }

							<div className="selected-options">
								{ items
									.filter( ( item ) => item.selected )
									.map( ( item ) => (
										<div
											key={ item.value }
											className="wc-block-product-filter-dropdown__selected-badge"
										>
											<span className="wc-block-product-filter-dropdown__badge-text">
												{ item.label }
											</span>
											<svg
												className="wc-block-product-filter-dropdown__badge-remove"
												width="24"
												height="24"
												xmlns="http://www.w3.org/2000/svg"
												viewBox="0 0 24 24"
												aria-hidden="true"
											>
												<path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path>
											</svg>
										</div>
									) ) }
							</div>

							<span className="wc-block-product-filter-dropdown__svg-container">
								<svg
									viewBox="0 0 24 24"
									xmlns="http://www.w3.org/2000/svg"
									width="30"
									height="30"
								>
									<path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"></path>
								</svg>
							</span>
						</div>
					</div>
				</Disabled>
			</div>
			<InspectorControls group="color">
				{ colorGradientSettings.hasColorsOrGradients && (
					<ColorGradientSettingsDropdown
						__experimentalIsRenderedInSidebar
						settings={ [
							{
								label: __(
									'Dropdown Container Background',
									'woocommerce'
								),
								colorValue:
									containerBackground.color ||
									customContainerBackground,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setContainerBackground( colorValue );
									setAttributes( {
										customContainerBackground: colorValue,
									} );
								},
								resetAllFilter: () => {
									setContainerBackground( '' );
									setAttributes( {
										customContainerBackground: '',
									} );
								},
							},
							{
								label: __(
									'Dropdown Container Border',
									'woocommerce'
								),
								colorValue:
									containerBorder.color ||
									customContainerBorder,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setContainerBorder( colorValue );
									setAttributes( {
										customContainerBorder: colorValue,
									} );
								},
								resetAllFilter: () => {
									setContainerBorder( '' );
									setAttributes( {
										customContainerBorder: '',
									} );
								},
							},
							{
								label: __(
									'Selected Badge Background',
									'woocommerce'
								),
								colorValue:
									badgeBackground.color ||
									customBadgeBackground,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setBadgeBackground( colorValue );
									setAttributes( {
										customBadgeBackground: colorValue,
									} );
								},
								resetAllFilter: () => {
									setBadgeBackground( '' );
									setAttributes( {
										customBadgeBackground: '',
									} );
								},
							},
							{
								label: __(
									'Selected Badge Text',
									'woocommerce'
								),
								colorValue: badgeText.color || customBadgeText,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setBadgeText( colorValue );
									setAttributes( {
										customBadgeText: colorValue,
									} );
								},
								resetAllFilter: () => {
									setBadgeText( '' );
									setAttributes( {
										customBadgeText: '',
									} );
								},
							},
							{
								label: __(
									'Placeholder Text Color',
									'woocommerce'
								),
								colorValue:
									placeholderText.color ||
									customPlaceholderText,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setPlaceholderText( colorValue );
									setAttributes( {
										customPlaceholderText: colorValue,
									} );
								},
								resetAllFilter: () => {
									setPlaceholderText( '' );
									setAttributes( {
										customPlaceholderText: '',
									} );
								},
							},
						] }
						panelId={ clientId }
						{ ...colorGradientSettings }
					/>
				) }
			</InspectorControls>
		</>
	);
};

export default withColors( {
	containerBackground: 'container-background',
	containerBorder: 'container-border',
	badgeBackground: 'badge-background',
	badgeText: 'badge-text',
	placeholderText: 'placeholder-text',
} )( DropdownEdit );
