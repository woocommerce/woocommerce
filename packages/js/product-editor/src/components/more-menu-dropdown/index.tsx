/**
 * External dependencies
 */
import classnames from 'classnames';
import { DropdownMenu } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';

/**
 * Copied exactly from https://github.com/WordPress/gutenberg/blob/e89fceffab765902e0162a1e3359d88501e72005/packages/interface/src/components/more-menu-dropdown/index.js
 * to unblock progress as it was discovered to have been deleted while doing a monorepo WordPress dependencies upgrade.
 * It was deleted in https://github.com/WordPress/gutenberg/pull/59096
 */

export const MoreMenuDropdown = ( {
	as: DropdownComponent = DropdownMenu,
	className,
	/* translators: button label text should, if possible, be under 16 characters. */
	label = __( 'Options', 'woocommerce' ),
	popoverProps,
	toggleProps,
	children,
}: {
	as?: React.ElementType;
	className?: string;
	label?: string;
	popoverProps?: Record< string, unknown > & { className?: string };
	toggleProps?: Record< string, unknown >;
	children: ( onClose: () => void ) => React.ReactNode;
} ) => {
	return (
		<DropdownComponent
			className={ classnames(
				'interface-more-menu-dropdown',
				className
			) }
			icon={ moreVertical }
			label={ label }
			popoverProps={ {
				placement: 'bottom-end',
				...popoverProps,
				className: classnames(
					'interface-more-menu-dropdown__content',
					popoverProps?.className
				),
			} }
			toggleProps={ {
				tooltipPosition: 'bottom',
				...toggleProps,
				size: 'compact',
			} }
		>
			{ ( { onClose }: { onClose: () => void } ) => children( onClose ) }
		</DropdownComponent>
	);
};
