/**
 * External dependencies
 */
import { Button } from '@wordpress/components';

export type PublishButtonProps = Omit<
	Button.ButtonProps,
	'aria-disabled' | 'variant' | 'children'
> & {
	productType?: string;
	isPrePublishButton?: boolean;
	isPrePublishPanelVisible?: boolean;
};
