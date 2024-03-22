/**
 * External dependencies
 */
import { Button } from '@wordpress/components';

export type PublishButtonProps = Omit<
	Button.ButtonProps,
	'aria-disabled' | 'variant' | 'children'
> & {
	productType?: string;
	isMenuButton?: boolean;
	isPrePublishPanelVisible?: boolean;
};
