```jsx
import { TextControlWithAffixes } from '@woocommerce/components';

const MyTextControlWithAffixes = () => (
    <div>
        <TextControlWithAffixes
            prefix="Prefix"
            placeholder="Placeholder text..."
        />
        <TextControlWithAffixes
            prefix="Prefix"
            suffix="Suffix"
            placeholder="Placeholder text..."
        />
        <TextControlWithAffixes
            suffix="Suffix"
            placeholder="Placeholder text..."
        />
    </div>
);
```