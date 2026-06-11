/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import Block from './block';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		className: string;
		displayCouponForm: boolean;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const { className, displayCouponForm } = attributes;
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __(
							'Expand coupon form by default',
							'woocommerce'
						) }
						help={ __(
							'When enabled, the coupon input field is visible immediately. When disabled, customers click "Add coupons" to reveal it.',
							'woocommerce'
						) }
						checked={ displayCouponForm }
						onChange={ ( value ) =>
							setAttributes( { displayCouponForm: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<Noninteractive>
					<Block
						className={ className }
						displayCouponForm={ displayCouponForm }
					/>
				</Noninteractive>
			</div>
		</>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
