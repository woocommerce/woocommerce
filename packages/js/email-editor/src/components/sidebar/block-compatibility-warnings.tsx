/**
 * External dependencies
 */
import { hasBlockSupport, getBlockSupport } from '@wordpress/blocks';
import { Fill, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export const hasBackgroundImageSupport = ( nameOrType: string ) => {
	const backgroundSupport = getBlockSupport(
		nameOrType, // @ts-expect-error not yet supported in the types
		'background'
	) as Record< string, boolean >;

	return backgroundSupport && backgroundSupport?.backgroundImage !== false;
};

export function BlockCompatibilityWarnings(): JSX.Element {
	// Select the currently selected block
	const selectedBlock = useSelect(
		( sel ) => sel( 'core/block-editor' ).getSelectedBlock(),
		[]
	);

	// Check if the selected block has enabled border configuration
	const hasBorderSupport =
		hasBlockSupport(
			selectedBlock?.name,
			// @ts-expect-error Border is not yet supported in the types
			'border',
			false
		) ||
		// We can remove the check for __experimentalBorder after we support WordPress 6.8+.
		hasBlockSupport(
			selectedBlock?.name,
			// @ts-expect-error Border is not yet supported in the types
			'__experimentalBorder',
			false
		);

	return (
		<>
			{ hasBorderSupport && (
				<Fill name="InspectorControlsBorder">
					<Notice
						className="woocommerce-grid-full-width"
						status="warning"
						isDismissible={ false }
					>
						{ __(
							'Border display may vary or be unsupported in some email clients.',
							'woocommerce'
						) }
					</Notice>
				</Fill>
			) }
			{ hasBackgroundImageSupport( selectedBlock?.name ) && (
				<Fill name="InspectorControlsBackground">
					<Notice
						className="woocommerce-grid-full-width"
						status="warning"
						isDismissible={ false }
					>
						{ __(
							'Select a background color for email clients that do not support background images.',
							'woocommerce'
						) }
					</Notice>
				</Fill>
			) }
		</>
	);
}
