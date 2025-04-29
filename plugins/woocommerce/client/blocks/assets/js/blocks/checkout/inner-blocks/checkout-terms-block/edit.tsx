/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { CheckboxControl } from '@woocommerce/blocks-components';
import {
	PanelBody,
	ToggleControl,
	Notice,
	ExternalLink,
} from '@wordpress/components';
import { PRIVACY_URL, TERMS_URL } from '@woocommerce/block-settings';
import { ADMIN_URL } from '@woocommerce/settings';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './editor.scss';
import { termsConsentDefaultText, termsCheckboxDefaultText } from './constants';

export const Edit = ( {
	attributes: { checkbox, text, showSeparator },
	setAttributes,
}: {
	attributes: { text: string; checkbox: boolean; showSeparator: boolean };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();

	const defaultText = checkbox
		? termsCheckboxDefaultText
		: termsConsentDefaultText;

	const currentText = text || defaultText;
	return (
		<div { ...blockProps }>
			<InspectorControls>
				{ /* Show this notice if a terms page or a privacy page is not setup. */ }
				{ ( ! TERMS_URL || ! PRIVACY_URL ) && (
					<Notice
						className="wc-block-checkout__terms_notice"
						status="warning"
						isDismissible={ false }
					>
						{ __(
							"Link to your store's Terms and Conditions and Privacy Policy pages by creating pages for them.",
							'woocommerce'
						) }
						<br />
						{ ! TERMS_URL && (
							<>
								<br />
								<ExternalLink
									href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=advanced` }
								>
									{ __(
										'Setup a Terms and Conditions page',
										'woocommerce'
									) }
								</ExternalLink>
							</>
						) }
						{ ! PRIVACY_URL && (
							<>
								<br />
								<ExternalLink
									href={ `${ ADMIN_URL }options-privacy.php` }
								>
									{ __(
										'Setup a Privacy Policy page',
										'woocommerce'
									) }
								</ExternalLink>
							</>
						) }
					</Notice>
				) }
				{ /* Show this notice if we have both a terms and privacy pages, but they're not present in the text. */ }
				{ TERMS_URL &&
					PRIVACY_URL &&
					! (
						currentText.includes( TERMS_URL ) &&
						currentText.includes( PRIVACY_URL )
					) && (
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
													'woocommerce'
												),
												onClick: () =>
													setAttributes( {
														text: '',
													} ),
											},
									  ]
									: []
							}
						>
							<p>
								{ __(
									'Ensure you add links to your policy pages in this section.',
									'woocommerce'
								) }
							</p>
						</Notice>
					) }
				<PanelBody title={ __( 'Display options', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Require checkbox', 'woocommerce' ) }
						checked={ checkbox }
						onChange={ () =>
							setAttributes( {
								checkbox: ! checkbox,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Show separator', 'woocommerce' ) }
						checked={ showSeparator }
						onChange={ () =>
							setAttributes( {
								showSeparator: ! showSeparator,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				className={ clsx( 'wc-block-checkout__terms', {
					'wc-block-checkout__terms--with-separator': showSeparator,
				} ) }
			>
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
						value={ currentText }
						onChange={ ( value ) =>
							setAttributes( { text: value } )
						}
					/>
				) }
			</div>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
