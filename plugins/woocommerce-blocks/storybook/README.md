# Working with Storybook <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Adding new stories](#adding-new-stories)
    - [Scaffold tour](#scaffold-tour)
        - [The `default` export](#the-default-export)
        - [Defining controls](#defining-controls)
        - [The Story template](#the-story-template)
        - [Defining Stories](#defining-stories)
    - [Snippets](#snippets)
- [FAQ](#faq)
    - [What should constitute a story?](#what-should-constitute-a-story)
    - [Can I create stories with mixed components?](#can-i-create-stories-with-mixed-components)
- [Tips](#tips)
    - [One file per component](#one-file-per-component)
    - [Custom controls](#custom-controls)
- [Common gotchas and examples](#common-gotchas-and-examples)
    - [Named exports](#named-exports)
    - [Controlled components](#controlled-components)
    - [Simulating interactions](#simulating-interactions)
    - [Context providers](#context-providers)

This document is meant to make contributing to our Storybook a bit easier by giving some tips, pointing out a few gotchas, making the editing experience smoother, and reducing the friction to adding a new story.

## Adding new stories

To easily scaffold a new story for your component you need a few steps, but most things are already configured to reduce friction.

1. Make sure you add a `stories` directory within your component directory.
2. You can name the files within the directory however you want. Usually, it's best to have one `index.tsx` file including all your stories. However, if you are having several components under the same directory (e.g. `Chip` and `RemovableChip`), it might be best to have one file per component ([see below](#one-file-per-component)).
3. You will need a `default` export which defines the metadata for the component and one named export for each story you want to create.

Let's see the code in detail.

### Scaffold tour

This is the minimal scaffold you need for your new Story:

```tsx
import type { Story, Meta } from '@storybook/react';
import MyComponent, { MyComponentProps } from '..';

export default {
	title: 'WooCommerce Blocks/${MyCategory}/${MyComponent}',
	component: MyComponent,
} as Meta< MyComponentProps >;

const Template: Story< MyComponentProps > = ( args ) => (
	<MyComponent { ...args } />
);

export const Default = Template.bind( {} );
Default.args = {};
```

The above code will already generate a story that you can tweak, but let's look at the code in-depth.

First of all, by using TypeScript and exporting your component's props, Storybook will automatically show the documentation associated with your props and also generate the controls which are more appropriate for each property.

Also, if you follow the naming convention of starting your event handlers with `on` (e.g. `onChange`, `onRemove`, etc.), Storybook will automatically make those props uncontrollable and generate events from them (this convention can be changed in `preview.js`).

Sometimes you will need to manually change those assumptions, and we'll see how in a moment. But let's go step-by-step:

#### The `default` export

The default export defines the metadata for your component. The most important things you should be aware of:

```ts
{
  /**
   * This is how your component is going to be named in the Storybook UI.
   * You can use slashed-paths to define hierarchies and categories.
   * At the time of writing, we don't have well defined categories, but we
   * are working on making that clearer.
   */
  title: string;

  /**
   * You should always pass your component here.
   */
  component: ComponentType< any >;

  /**
   * You can define here the default props for the components on all your
   * stories. This is pretty useful when you have required props.
   */
  args: Partial< MyComponentProps >;

  /**
   * Here you can define how the Storybook controls look like. More info
   * below.
   */
  argTypes: Partial< ArgTypes< MyComponentProps > >;
}
```

#### Defining controls

As mentioned, Storybook will try to infer the best control for your property type (e.g. an on/off switch for a `boolean`, etc.). However, there are times in which you want different options.

Here is a link to the official Storybook documentation: <https://storybook.js.org/docs/react/essentials/controls>

But a TL;DR of most common usecases:

-   The shape of the prop looks like so:

```ts
{
  [$myPropName]: {
    control: { type: $controlType },
    // Only applicable for selects/radios and such
    options: $controlOptions
  }
}
```

-   You can disable a control like so:

```ts
{
  [$myPropName]: { control: false }
}
```

#### The Story template

The recommended way to create a story is by creating a template function and duplicating it for each story, to avoid extra scaffolding. The simplest form of the template is as follows:

```tsx
const Template: Story< MyComponentProps > = ( args ) => (
	<MyComponent { ...args } />
);
```

In this way, you are rendering the component in the viewport and binding the component properties to the Storybook controls.

In this template function, however, you might put any extra logic that your component rendering might need. Common use-cases are [wrapping them in context providers](#context-providers), or [simulating controlled components](#controlled-components).

#### Defining Stories

If you have followed this scaffold, defining a new story is as simple as this:

```ts
export const Default = Template.bind( {} );
```

Usually, you want a story with the name `Default`, and other stories with interesting variations of your component ([see below](#what-should-constitute-a-story)). In order to create those variations, you should work with some of the properties of this function prototype. Here are the most common:

```ts
{
  /**
   * Define the properties to pass for this specific story. Often,
   * `Default` stories wouldn't even need this.
   */
  args: Partial< MyComponentProps >;

  /**
   * You will rarely need this, as story names are automatically generated
   * from your constant name. But that's good to know if you ever need.
   */
  storyName: string;
}
```

Full official docs: <https://storybook.js.org/docs/react/writing-stories/introduction>

### Snippets

If you are using [VSCode](https://code.visualstudio.com/), this repo includes some helpful snippets that will become available inside `.ts` and `.tsx` files. You can find them inside `.vscode/storybook.code-snippets`.

The `sbs` (“Storybook story”) will scaffold the entire code in the [section above](#scaffold-tour). If you have respected the naming conventions, it will also properly import your component and the properties with the correct names, saving you a bunch of time.

The `sbt` (“Storybook story template”) will create a new story by binding your default template and prompting you to provide specific arguments.

## FAQ

### What should constitute a story?

Stories should show the component in its different shapes.

The most obvious way is when a component has a few variations (such as a button with a `primary` attribute being larger and bolder, for example).

However, a common misconception is that, since full controls are enabled and allow the user to explore the component by playing around with its properties interactively, there is no need to provide stories which are just the component with different default properties.

While this is true for many cases (often you don't need to create a different story by replacing the `label` of a component, if that's just text; though it might be argued that it could be interesting to have a story showing how a very long text behaves, especially in [combined stories](#can-i-create-stories-with-mixed-components)), it is very useful to create stories for components in their loading or error states.

### Can I create stories with mixed components?

Yes, and it'd be awesome to see how our components interact with each other, especially in the context of blocks. At the time of writing, we have no such stories, however, here is a link to the official docs if you want to give a go at implementing this: <https://storybook.js.org/docs/react/writing-stories/stories-for-multiple-components>

## Tips

### One file per component

While stories for closely related components could technically be living within the same file, we advise that you keep one file per component, as this works best with the automatic organization of the components within storybook and using [`default` exports in the Story](https://storybook.js.org/docs/react/api/csf#default-export).

An exception to this, might be when there is only one mainly used component and the other defined ones are just used internally.

E.g. compare `Chip/RemovableChip` with `ProductPrice/Sale/Range`. In the latter case, `Sale` and `Range` components are actually rendered depending on the props passed to the `ProductPrice` component, so they might as well be stories of their parent component.

### Custom controls

Sometimes the inferred text input is not good enough, and you need something more complex. If those controls happen to be shared among many components, please write them in `storybook/custom-controls`.

An example there is the `currency` control. Since many of our components expect `Currency` objects in their props, we can make sure we give a few examples Storybook users can play with, without having to manually create the object themselves.

Let's take a look:

```ts
export const currencies: Record< string, Currency > = {
	EUR: {
		// ...
	},
	USD: {
		// ...
	},
} as const;

export const currencyControl = {
	control: 'select',
	defaultValue: currencies.USD,
	// This maps string keys to their values
	mapping: currencies,
	// These are the options which will appear in the <select> control
	options: Object.keys( currencies ),
};
```

Then we can just pass the `currencyControl` to our component `argTypes`.

```ts
export default {
	// ...
	argTypes: {
		currency: currencyControl,
	},
};
```

## Common gotchas and examples

### Named exports

Your component files can have `default` exports, but in order to play well with the automatic doc generation, they should **always** have named exports as well.

See: <https://github.com/strothj/react-docgen-typescript-loader/issues/75>

### Controlled components

Your component is not managing its own state and expects it to be passed as a prop, but you want to create a self-contained story. You can then edit your main `Template` function to manage the state, for example through hooks.

```tsx
const Template: Story< MyControlledComponentProps > = ( args ) => {
	const [ myState, setMyState ] = useState( 0 );

	const onChange = ( newVal ) => {
		args.onChange?.( newVal );
		setMyState( newVal );
	};

	return (
		<MyControlledComponent
			{ ...args }
			onChange={ onChange }
			state={ myState }
		/>
	);
};
```

You will notice that when you do this, your controls will not be in sync anymore: when you change the state through your story interaction, the control for `state` will not equal `myState`.

Often, it is enough to disable the control for `state` as it's not required.

If you want to keep them in sync, you'll have to use `useArgs` from the Storybook client API.

```tsx
import { useArgs } from '@storybook/client-api';

const Template: Story< MyControlledComponentProps > = ( args ) => {
	const [ _, setArgs ] = useArgs();

	const onChange = ( newVal ) => {
		args.onChange?.( newVal );
		setArgs( { state: newVal } );
	};

	return <MyControlledComponent { ...args } onChange={ onChange } />;
};
```

Note that this makes things a bit more complex, so it is recommended to do only when it makes sense for a controlled value to still have a Storybook control.

At the time of writing, there is a known bug that doesn't keep number inputs in sync: <https://github.com/storybookjs/storybook/issues/15924>

### Simulating interactions

First of all, note that all the props starting with `on` as described above trigger “actions” in Storybook, which is basically just a way to see how data gets passed to a handler via the _Actions_ tab.

You can also manually mark props as actions in the `argTypes`, like so:

```ts
export default {
	// ...
	argTypes: {
		myHandler: {
			// <- this doesn't start with `on` so it needs manual config
			action: 'This text will show in the panel along with the data',
		},
	},
};
```

Full action docs: <https://storybook.js.org/docs/react/essentials/actions>

**However**, you might want to simulate some sort of behavior from your component, for example show how a `Retry` button triggers a loading state. In this case you can use `useArgs`:

```tsx
const Template: Story< MyComponentProps > = ( args ) => {
	const [ { isLoading }, setArgs ] = useArgs();

	const onRetry = () => {
		args.onRetry?.();
		setArgs( { isLoading: true } );

		setTimeout(
			() => setArgs( { isLoading: false } ),
			INTERACTION_TIMEOUT
		);
	};

	return (
		<MyComponent { ...args } onRetry={ onRetry } isLoading={ isLoading } />
	);
};
```

We expose the `INTERACTION_TIMEOUT` constant from `@woocommerce/storybook-controls'` as a simple way to set our timeouts across our stories.

### Context providers

See example: `assets/js/base/components/country-input/stories/index.tsx`

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/block-assets.md)

<!-- /FEEDBACK -->
