# Tools

## Adding a New Command

1. Open `commands.js` and add a new object with command, description, and action keys. Action value must be a valid function name.
2. Open `data/actions.js` and add a function. The function name must be the value of `Action` from the first step.

Sample function:
```
export function* helloWorld() {
	yield runCommand( 'Hello World', function* () {
        console.log('Hello World');
	} );
}
```

3. Run `npm start` to compile and test.
