/**
 * External dependencies
 */
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { termsConsentDefaultText, termsCheckboxDefaultText } from './constants';
import './style.scss';

const FrontendBlock = ( {
	text,
	checkbox,
}: {
	text: string;
	checkbox: boolean;
} ): JSX.Element => {
	const [ checked, setChecked ] = useState( false );
	return (
		<div className="wc-block-checkout__terms">
			{ checkbox ? (
				<>
					<CheckboxControl
						id="terms-condition"
						checked={ checked }
						onChange={ () => setChecked( ( value ) => ! value ) }
					>
						<span
							dangerouslySetInnerHTML={ {
								__html: text || termsCheckboxDefaultText,
							} }
						/>
					</CheckboxControl>
				</>
			) : (
				<span
					dangerouslySetInnerHTML={ {
						__html: text || termsConsentDefaultText,
					} }
				/>
			) }
		</div>
	);
};

export default FrontendBlock;
