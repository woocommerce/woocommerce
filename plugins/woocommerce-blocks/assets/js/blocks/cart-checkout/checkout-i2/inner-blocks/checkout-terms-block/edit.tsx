/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { PanelBody, ToggleControl, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';
import { termsConsentDefaultText, termsCheckboxDefaultText } from './constants';

export const Edit = ( {
	attributes: { checkbox, text },
	setAttributes,
}: {
	attributes: { text: string; checkbox: boolean };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const currentText = text || termsCheckboxDefaultText;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Display options',
						'woo-gutenberg-products-block'
					) }
				>
					<ToggleControl
						label={ __(
							'Require checkbox',
							'woo-gutenberg-products-block'
						) }
						checked={ checkbox }
						onChange={ () =>
							setAttributes( {
								checkbox: ! checkbox,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div className="wc-block-checkout__terms">
				{ checkbox ? (
					<>
						<CheckboxControl
							id="terms-condition"
							checked={ false }
						/>
						<RichText
							value={ currentText }
							onChange={ ( value ) =>
								setAttributes( { text: value } )
							}
						/>
					</>
				) : (
					<RichText
						tagName="span"
						value={ text || termsConsentDefaultText }
						onChange={ ( value ) =>
							setAttributes( { text: value } )
						}
					/>
				) }
			</div>
			{ ! currentText.includes( '<a ' ) && (
				<Notice
					className="wc-block-checkout__terms_notice"
					status="warning"
					isDismissible={ false }
					actions={
						termsConsentDefaultText !== text
							? [
									{
										label: __(
											'Restore default text',
											'woo-gutenberg-products-block'
										),
										onClick: () =>
											setAttributes( { text: '' } ),
									},
							  ]
							: []
					}
				>
					<p>
						{ __(
							'Ensure you add links to your policy pages in this section.',
							'woo-gutenberg-products-block'
						) }
					</p>
				</Notice>
			) }
		</>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
