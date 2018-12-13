```jsx
import { TextControlWithAffixes } from '@woocommerce/components';

const MyTextControlWithAffixes = withState( {
	first: '',
    second: '',
    third: '',
} )( ( { first, second, third, setState } ) => (
    <div>
        <TextControlWithAffixes
            prefix="Prefix"
            placeholder="Placeholder text..."
            value={ first }
            onChange={ value => setState( { first: value } ) }
        />
        <TextControlWithAffixes
            prefix="Prefix"
            suffix="Suffix"
            placeholder="Placeholder text..."
            value={ second }
            onChange={ value => setState( { second: value } ) }
        />
        <TextControlWithAffixes
            suffix="Suffix"
            placeholder="Placeholder text..."
            value={ third }
            onChange={ value => setState( { third: value } ) }
        />
    </div>
) );
```