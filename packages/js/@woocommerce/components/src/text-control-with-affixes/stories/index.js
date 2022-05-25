/**
 * External dependencies
 */
import { TextControlWithAffixes } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const Examples = () => {
	const [ state, setState ] = useState( {
		first: '',
		second: '',
		third: '',
		fourth: '',
		fifth: '',
	} );
	const { first, second, third, fourth, fifth } = state;
	const partialUpdate = ( partial ) => {
		setState( { ...state, ...partial } );
	};

	return (
		<div>
			<TextControlWithAffixes
				label="Text field without affixes"
				value={ first }
				placeholder="Placeholder"
				onChange={ ( value ) => partialUpdate( { first: value } ) }
			/>
			<TextControlWithAffixes
				label="Disabled text field without affixes"
				value={ first }
				placeholder="Placeholder"
				onChange={ ( value ) => partialUpdate( { first: value } ) }
				disabled
			/>
			<TextControlWithAffixes
				prefix="$"
				label="Text field with a prefix"
				value={ second }
				onChange={ ( value ) => partialUpdate( { second: value } ) }
			/>
			<TextControlWithAffixes
				prefix="$"
				label="Disabled text field with a prefix"
				value={ second }
				onChange={ ( value ) => partialUpdate( { second: value } ) }
				disabled
			/>
			<TextControlWithAffixes
				prefix="Prefix"
				suffix="Suffix"
				label="Text field with both affixes"
				value={ third }
				onChange={ ( value ) => partialUpdate( { third: value } ) }
			/>
			<TextControlWithAffixes
				prefix="Prefix"
				suffix="Suffix"
				label="Disabled text field with both affixes"
				value={ third }
				onChange={ ( value ) => partialUpdate( { third: value } ) }
				disabled
			/>
			<TextControlWithAffixes
				suffix="%"
				label="Text field with a suffix"
				value={ fourth }
				onChange={ ( value ) => partialUpdate( { fourth: value } ) }
			/>
			<TextControlWithAffixes
				suffix="%"
				label="Disabled text field with a suffix"
				value={ fourth }
				onChange={ ( value ) => partialUpdate( { fourth: value } ) }
				disabled
			/>
			<TextControlWithAffixes
				prefix="$"
				label="Text field with prefix and help text"
				value={ fifth }
				onChange={ ( value ) => partialUpdate( { fifth: value } ) }
				help="This is some help text."
			/>
			<TextControlWithAffixes
				prefix="$"
				label="Disabled text field with prefix and help text"
				value={ fifth }
				onChange={ ( value ) => partialUpdate( { fifth: value } ) }
				help="This is some help text."
				disabled
			/>
		</div>
	);
};

export const Basic = () => <Examples />;

export default {
	title: 'WooCommerce Admin/components/TextControlWithAffixes',
	component: TextControlWithAffixes,
};
