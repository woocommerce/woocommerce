/**
 * External dependencies
 */
import { Dropdown } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from '../variation-actions-menus';

export type ImageActionsMenuProps = Omit<
	React.ComponentProps< typeof Dropdown >,
	'renderToggle' | 'renderContent'
> &
	VariationActionsMenuProps & {
		renderToggle(
			props: Parameters<
				React.ComponentProps< typeof Dropdown >[ 'renderToggle' ]
			>[ 0 ] & {
				isBusy?: boolean;
			}
		): JSX.Element;
	};
