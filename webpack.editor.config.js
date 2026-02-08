const path = require('path');

module.exports = {
	entry: {
		'import-modal-react': './assets/js/src/editor/index.tsx',
	},
	output: {
		path: path.resolve(__dirname, 'assets/js/editor/compiled'),
		filename: '[name].js',
		library: {
			type: 'window',
			name: 'ehccImportModalReact',
		},
	},
	resolve: {
		extensions: ['.tsx', '.ts', '.jsx', '.js'],
	},
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				exclude: /node_modules/,
				use: [
					{
						loader: 'babel-loader',
						options: {
							presets: [
								'@babel/preset-env',
								['@babel/preset-react', {
									runtime: 'classic',
								}],
								'@babel/preset-typescript',
							],
						},
					},
				],
			},
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: [
					{
						loader: 'babel-loader',
						options: {
							presets: [
								'@babel/preset-env',
								['@babel/preset-react', {
									runtime: 'classic',
								}],
							],
						},
					},
				],
			},
		],
	},
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
	},
	mode: 'development',
	devtool: 'eval-source-map',
};
