import { type BlockAttributes, type OverlayMenu } from '../../types';

export const DEFAULT_OVERLAY_MENU: OverlayMenu = 'mobile';

const OVERLAY_MENU_VALUES: OverlayMenu[] = [ 'never', 'mobile', 'always' ];

const isOverlayMenu = ( value: unknown ): value is OverlayMenu =>
	OVERLAY_MENU_VALUES.includes( value as OverlayMenu );

export const getOverlayMenu = ( attributes: BlockAttributes ): OverlayMenu => {
	if (
		isOverlayMenu( attributes.overlayMenu ) &&
		attributes.overlayMenu !== DEFAULT_OVERLAY_MENU
	) {
		return attributes.overlayMenu;
	}

	if ( attributes.showFilterDrawer === false ) {
		return 'never';
	}

	return isOverlayMenu( attributes.overlayMenu )
		? attributes.overlayMenu
		: DEFAULT_OVERLAY_MENU;
};
