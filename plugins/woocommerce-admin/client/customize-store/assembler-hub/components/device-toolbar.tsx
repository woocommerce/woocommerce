/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import clsx from 'clsx';
import React from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useContext } from '@wordpress/element';
// @ts-ignore No types for this exist yet.
import { store as editorStore } from '@wordpress/editor';
import { Icon, desktop, tablet, mobile } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	// @ts-ignore No types for this exist yet.
	NavigableMenu,
	Circle,
	SVG,
	Path,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ZoomOutContext } from '../context/zoom-out-context';

const zoomIn = (
	<SVG width="24" height="24" viewBox="0 0 24 24">
		<Circle cx="11" cy="11" r="7.25" fill="transparent" strokeWidth="1.5" />
		<Path d="M8 11H14M11 8V14" strokeWidth="1.5" />
		<Path d="M16 16L20 20" strokeWidth="1.5" />
	</SVG>
);

const zoomOut = (
	<SVG width="24" height="24" viewBox="0 0 24 24">
		<Circle cx="11" cy="11" r="7.25" fill="transparent" strokeWidth="1.5" />
		<Path d="M16 16L20 20" strokeWidth="1.5" />
		<Path d="M8 11H14" strokeWidth="1.5" />
	</SVG>
);

const BUTTON_CLASS_NAMES =
	'components-button has-icon woocommerce-customize-store__resize-button';
const ICON_CLASS_NAMES = 'woocommerce-customize-store__resize-icon';

export function DeviceToolbar() {
	// @ts-expect-error No types for this exist yet.
	const { setDeviceType } = useDispatch( editorStore );
	const { toggleZoomOut, isZoomedOut } = useContext( ZoomOutContext );

	const { deviceType } = useSelect( ( select ) => {
		// @ts-ignore No types for this exist yet.
		const { getDeviceType } = select( editorStore );

		return {
			deviceType: getDeviceType(),
		};
	} );

	// Zoom Out isn't available on mobile or tablet.
	// In this case, we want to switch to desktop mode when zooming out.
	const switchDeviceType = ( newDeviceType: string ) => {
		if ( isZoomedOut ) {
			toggleZoomOut();
		}
		setDeviceType( newDeviceType );
	};

	return (
		<NavigableMenu
			className="woocommerce-customize-store__resize-menu"
			orientation="horizontal"
			role="toolbar"
			aria-label={ __( 'Resize', 'woocommerce' ) }
		>
			<button
				className={ clsx( BUTTON_CLASS_NAMES, {
					'is-selected': deviceType === 'Desktop',
				} ) }
				aria-label="Desktop"
				onClick={ () => {
					switchDeviceType( 'Desktop' );
				} }
			>
				<Icon
					icon={ desktop }
					size={ 30 }
					className={ clsx( ICON_CLASS_NAMES ) }
				/>
			</button>
			<button
				className={ clsx( BUTTON_CLASS_NAMES, {
					'is-selected': deviceType === 'Tablet',
				} ) }
				aria-label="Tablet"
				onClick={ () => {
					switchDeviceType( 'Tablet' );
				} }
			>
				<Icon
					icon={ tablet }
					size={ 30 }
					className={ clsx( ICON_CLASS_NAMES ) }
				/>
			</button>
			<button
				className={ clsx( BUTTON_CLASS_NAMES, {
					'is-selected': deviceType === 'Mobile',
				} ) }
				aria-label="Mobile"
				onClick={ () => {
					switchDeviceType( 'Mobile' );
				} }
			>
				<Icon
					icon={ mobile }
					size={ 30 }
					className={ clsx( ICON_CLASS_NAMES ) }
				/>
			</button>
			<button
				className={ clsx( BUTTON_CLASS_NAMES ) }
				aria-label="Zoom out"
				onClick={ () => {
					setDeviceType( 'Desktop' );
					toggleZoomOut();
				} }
			>
				<Icon
					icon={ isZoomedOut ? zoomIn : zoomOut }
					size={ 30 }
					className={ clsx(
						ICON_CLASS_NAMES,
						`${ ICON_CLASS_NAMES }--zoom`
					) }
				/>
			</button>
		</NavigableMenu>
	);
}
