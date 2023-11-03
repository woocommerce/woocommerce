# Customer Effort Score

WooCommerce utility to measuring user satisfaction.

## Installation

Install the module

```bash
pnpm install @woocommerce/customer-effort-score --save
```

_This package assumes that your code will run in an **ES2015+** environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using [core-js](https://github.com/zloirock/core-js) or [@babel/polyfill](https://babeljs.io/docs/en/next/babel-polyfill) will add support for these methods. Learn more about it in [Babel docs](https://babeljs.io/docs/en/next/caveats)._

## Usage

### CustomerEffortScore component

`CustomerEffortScore` is a React component that can be used to implement your
own effort score survey, providing your own logging infrastructure.

This creates a wrapper component around `CustomerEffortScore` which simply logs
responses to the console:

```js
import CustomerEffortScore from '@woocommerce/customer-effort-score';

export function CustomerEffortScoreConsole( { label } ) {
    const onNoticeShown = () => console.log( 'onNoticeShown' );
    const onModalShown = () => console.log( 'onModalShown' );
    const onNoticeDismissed = () => console.log( 'onNoticeDismissed' );
    const recordScore = ( score, score2, comments ) => console.log( { score, score2, comments } );

    return (
        <CustomerEffortScore
			recordScoreCallback={ recordScore }
			title="My title" 
            firstQuestion="My first question"
            secondQuestion="My optional second question"
			onNoticeShownCallback={ onNoticeShown }
			onNoticeDismissedCallback={ onNoticeDismissed }
			onModalShownCallback={ onModalShown }
			icon={
				<span
					style={ { height: 21, width: 21 } }
					role="img"
					aria-label="Pencil icon"
				>
					✏️
				</span>
			}
        />
    );
};
```

Use this wrapper component in your code like this:

```js
const MyComponent = function() {
    const [ ceses, setCeses ] = useState( [] );
	
    const addCES = () => {
		setCeses( 
			ceses.concat( 
				<CustomerEffortScoreConsole
					title={ `survey ${ceses.length + 1}` }
                    firstQuestion="My first question"
					key={ ceses.length + 1 }
				/> 
			) 
		);
	};

    return (
        <>
            { ceses }
            <button onClick={ addCES }>Show new survey</button>
        </>
    );
};
```
