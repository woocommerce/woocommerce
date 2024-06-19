/**
 * External dependencies
 */
import { Dropdown } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from '../variation-actions-menus';

export type ImageActionsMenuProps = Omit<
	Dropdown.Props,
	'renderToggle' | 'renderContent'
> &
	VariationActionsMenuProps & {
		renderToggle(
			props: Dropdown.RenderProps & { isBusy?: boolean }
		): JSX.Element;
	};
