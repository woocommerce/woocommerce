# How to update

## Source code

This action is extracted and bundled version of the following:

Repository: https://github.com/WordPress/gutenberg/tree/trunk/packages/report-flaky-tests
Commit ID: ce803384250671d01fde6c7d6d2aa83075fcc726

## How to build

After checking out the repository, navigate to packages/report-flaky-tests and do some modifications:

### package.json file

Add the following dependency: `"ts-loader": "^9.5.1",`.

### tsconfig.json file

The file context should be updated to following state:

```
{
	"$schema": "https://json.schemastore.org/tsconfig.json",
	"extends": "../../tsconfig.base.json",
	"compilerOptions": {
		"outDir": "dist",
		"target": "es6",
		"module": "commonjs",
		"esModuleInterop": true,
		"moduleResolution": "node",
		"declarationDir": "build-types",
		"rootDir": "src",
		"emitDeclarationOnly": false,
	},
	"include": [ "src/**/*" ],
	"exclude": [ "src/__tests__/**/*", "src/__fixtures__/**/*" ]
}
```

### webpack.config.js file

The file should be added with the following content:

```
const path = require( 'path' );
const buildMode = process.env.NODE_ENV || 'production';

module.exports = {
	entry: './src/index.ts',
	target: 'node',
	mode: buildMode,
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/,
			},
		],
	},
	resolve: {
		extensions: [ '.tsx', '.ts', '.js' ],
	},
	plugins: [],
	output: {
		filename: 'index.js',
		path: path.resolve( __dirname, 'dist' ),
		clean: true,
	},
};
```

### Build

Run `webpack --config webpack.config.js` (don't forget about `npm install` before that).

Use the generated files under `packages/report-flaky-tests/dist` to update the bundled distribution in this repository.
