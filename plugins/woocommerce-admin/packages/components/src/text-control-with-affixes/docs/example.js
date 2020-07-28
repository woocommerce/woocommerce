/**
 * External dependencies
 */
import { TextControlWithAffixes } from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

export default withState( {
	first: '',
	second: '',
	third: '',
	fourth: '',
	fifth: '',
} )( ( { first, second, third, fourth, fifth, setState } ) => (
	<div>
		<TextControlWithAffixes
			label="Text field without affixes"
			value={ first }
			placeholder="Placeholder"
			onChange={ ( value ) => setState( { first: value } ) }
		/>
		<TextControlWithAffixes
			label="Disabled text field without affixes"
			value={ first }
			placeholder="Placeholder"
			onChange={ ( value ) => setState( { first: value } ) }
			disabled
		/>
		<TextControlWithAffixes
			prefix="$"
			label="Text field with a prefix"
			value={ second }
			onChange={ ( value ) => setState( { second: value } ) }
		/>
		<TextControlWithAffixes
			prefix="$"
			label="Disabled text field with a prefix"
			value={ second }
			onChange={ ( value ) => setState( { second: value } ) }
			disabled
		/>
		<TextControlWithAffixes
			prefix="Prefix"
			suffix="Suffix"
			label="Text field with both affixes"
			value={ third }
			onChange={ ( value ) => setState( { third: value } ) }
		/>
		<TextControlWithAffixes
			prefix="Prefix"
			suffix="Suffix"
			label="Disabled text field with both affixes"
			value={ third }
			onChange={ ( value ) => setState( { third: value } ) }
			disabled
		/>
		<TextControlWithAffixes
			suffix="%"
			label="Text field with a suffix"
			value={ fourth }
			onChange={ ( value ) => setState( { fourth: value } ) }
		/>
		<TextControlWithAffixes
			suffix="%"
			label="Disabled text field with a suffix"
			value={ fourth }
			onChange={ ( value ) => setState( { fourth: value } ) }
			disabled
		/>
		<TextControlWithAffixes
			prefix="$"
			label="Text field with prefix and help text"
			value={ fifth }
			onChange={ ( value ) => setState( { fifth: value } ) }
			help="This is some help text."
		/>
		<TextControlWithAffixes
			prefix="$"
			label="Disabled text field with prefix and help text"
			value={ fifth }
			onChange={ ( value ) => setState( { fifth: value } ) }
			help="This is some help text."
			disabled
		/>
	</div>
) );
