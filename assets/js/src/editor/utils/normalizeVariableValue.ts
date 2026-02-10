const GENERIC_FONT_FAMILIES = [
	'serif',
	'sans-serif',
	'monospace',
	'cursive',
	'fantasy',
];

const NAMED_COLORS_TO_HEX: Record<string, string> = {
	aliceblue: '#f0f8ff',
	antiquewhite: '#faebd7',
	aqua: '#00ffff',
	aquamarine: '#7fffd4',
	azure: '#f0ffff',
	beige: '#f5f5dc',
	bisque: '#ffe4c4',
	black: '#000000',
	blanchedalmond: '#ffebcd',
	blue: '#0000ff',
	blueviolet: '#8a2be2',
	brown: '#a52a2a',
	burlywood: '#deb887',
	cadetblue: '#5f9ea0',
	chartreuse: '#7fff00',
	chocolate: '#d2691e',
	coral: '#ff7f50',
	cornflowerblue: '#6495ed',
	cornsilk: '#fff8dc',
	crimson: '#dc143c',
	cyan: '#00ffff',
	darkblue: '#00008b',
	darkcyan: '#008b8b',
	darkgoldenrod: '#b8860b',
	darkgray: '#a9a9a9',
	darkgreen: '#006400',
	darkgrey: '#a9a9a9',
	darkkhaki: '#bdb76b',
	darkmagenta: '#8b008b',
	darkolivegreen: '#556b2f',
	darkorange: '#ff8c00',
	darkorchid: '#9932cc',
	darkred: '#8b0000',
	darksalmon: '#e9967a',
	darkseagreen: '#8fbc8f',
	darkslateblue: '#483d8b',
	darkslategray: '#2f4f4f',
	darkslategrey: '#2f4f4f',
	darkturquoise: '#00ced1',
	darkviolet: '#9400d3',
	deeppink: '#ff1493',
	deepskyblue: '#00bfff',
	dimgray: '#696969',
	dimgrey: '#696969',
	dodgerblue: '#1e90ff',
	firebrick: '#b22222',
	floralwhite: '#fffaf0',
	forestgreen: '#228b22',
	fuchsia: '#ff00ff',
	gainsboro: '#dcdcdc',
	ghostwhite: '#f8f8ff',
	gold: '#ffd700',
	goldenrod: '#daa520',
	gray: '#808080',
	green: '#008000',
	greenyellow: '#adff2f',
	grey: '#808080',
	honeydew: '#f0fff0',
	hotpink: '#ff69b4',
	indianred: '#cd5c5c',
	indigo: '#4b0082',
	ivory: '#fffff0',
	khaki: '#f0e68c',
	lavender: '#e6e6fa',
	lavenderblush: '#fff0f5',
	lawngreen: '#7cfc00',
	lemonchiffon: '#fffacd',
	lightblue: '#add8e6',
	lightcoral: '#f08080',
	lightcyan: '#e0ffff',
	lightgoldenrodyellow: '#fafad2',
	lightgray: '#d3d3d3',
	lightgreen: '#90ee90',
	lightgrey: '#d3d3d3',
	lightpink: '#ffb6c1',
	lightsalmon: '#ffa07a',
	lightseagreen: '#20b2aa',
	lightskyblue: '#87cefa',
	lightslategray: '#778899',
	lightslategrey: '#778899',
	lightsteelblue: '#b0c4de',
	lightyellow: '#ffffe0',
	lime: '#00ff00',
	limegreen: '#32cd32',
	linen: '#faf0e6',
	magenta: '#ff00ff',
	maroon: '#800000',
	mediumaquamarine: '#66cdaa',
	mediumblue: '#0000cd',
	mediumorchid: '#ba55d3',
	mediumpurple: '#9370db',
	mediumseagreen: '#3cb371',
	mediumslateblue: '#7b68ee',
	mediumspringgreen: '#00fa9a',
	mediumturquoise: '#48d1cc',
	mediumvioletred: '#c71585',
	midnightblue: '#191970',
	mintcream: '#f5fffa',
	mistyrose: '#ffe4e1',
	moccasin: '#ffe4b5',
	navajowhite: '#ffdead',
	navy: '#000080',
	oldlace: '#fdf5e6',
	olive: '#808000',
	olivedrab: '#6b8e23',
	orange: '#ffa500',
	orangered: '#ff4500',
	orchid: '#da70d6',
	palegoldenrod: '#eee8aa',
	palegreen: '#98fb98',
	paleturquoise: '#afeeee',
	palevioletred: '#db7093',
	papayawhip: '#ffefd5',
	peachpuff: '#ffdab9',
	peru: '#cd853f',
	pink: '#ffc0cb',
	plum: '#dda0dd',
	powderblue: '#b0e0e6',
	purple: '#800080',
	rebeccapurple: '#663399',
	red: '#ff0000',
	rosybrown: '#bc8f8f',
	royalblue: '#4169e1',
	saddlebrown: '#8b4513',
	salmon: '#fa8072',
	sandybrown: '#f4a460',
	seagreen: '#2e8b57',
	seashell: '#fff5ee',
	sienna: '#a0522d',
	silver: '#c0c0c0',
	skyblue: '#87ceeb',
	slateblue: '#6a5acd',
	slategray: '#708090',
	slategrey: '#708090',
	snow: '#fffafa',
	springgreen: '#00ff7f',
	steelblue: '#4682b4',
	tan: '#d2b48c',
	teal: '#008080',
	thistle: '#d8bfd8',
	tomato: '#ff6347',
	turquoise: '#40e0d0',
	violet: '#ee82ee',
	wheat: '#f5deb3',
	white: '#ffffff',
	whitesmoke: '#f5f5f5',
	yellow: '#ffff00',
	yellowgreen: '#9acd32',
	transparent: '#00000000',
	currentcolor: '#000000',
};

const stripQuotes = (value: string): string => {
	const trimmed = value.trim();
	if (
		(trimmed.startsWith('"') && trimmed.endsWith('"')) ||
		(trimmed.startsWith("'") && trimmed.endsWith("'"))
	) {
		return trimmed.slice(1, -1).trim().replace(/\s+/g, ' ');
	}
	return trimmed.replace(/\s+/g, ' ');
};

const splitFontStack = (value: string): string[] => {
	const fonts: string[] = [];
	let current = '';
	let inQuotes = false;
	let quoteChar = '';

	for (let i = 0; i < value.length; i++) {
		const char = value[i];

		if ((char === '"' || char === "'") && (i === 0 || value[i - 1] !== '\\')) {
			if (!inQuotes) {
				inQuotes = true;
				quoteChar = char;
			} else if (char === quoteChar) {
				inQuotes = false;
				quoteChar = '';
			}
			current += char;
		} else if (char === ',' && !inQuotes) {
			const trimmed = current.trim();
			if (trimmed) {
				fonts.push(trimmed);
			}
			current = '';
		} else {
			current += char;
		}
	}

	const trimmed = current.trim();
	if (trimmed) {
		fonts.push(trimmed);
	}

	return fonts;
};

const normalizeFontValue = (value: string): string => {
	const fonts = splitFontStack(value);

	for (const font of fonts) {
		const stripped = stripQuotes(font);
		if (!GENERIC_FONT_FAMILIES.includes(stripped.toLowerCase())) {
			return stripped;
		}
	}

	if (fonts.length > 0) {
		return stripQuotes(fonts[0]);
	}

	return value.trim();
};

const HEX3_PATTERN = /^#([A-Fa-f0-9]{3})$/;
const HEX6_PATTERN = /^#([A-Fa-f0-9]{6})$/;
const HEXA_PATTERN = /^#([A-Fa-f0-9]{8})$/;
const HSL_COMMA_PATTERN = /^hsl\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*(\d+(?:\.\d+)?)%\s*\)$/i;
const HSL_SPACE_PATTERN = /^hsl\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s+(\d+(?:\.\d+)?)%\s+(\d+(?:\.\d+)?)%\s*\)$/i;
const HSLA_COMMA_PATTERN = /^hsla\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*([\d.]+)\s*\)$/i;
const HSL_SLASH_PATTERN = /^hsl\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s+(\d+(?:\.\d+)?)%\s+(\d+(?:\.\d+)?)%\s*\/\s*([\d.]+)\s*\)$/i;
const RGB_PATTERN = /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/;
const RGBA_PATTERN = /^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([\d.]+)\s*\)$/;
const OPACITY_NAME_PATTERN = /opacity/i;
const UNITLESS_ZERO_TO_ONE_PATTERN = /^(?:0(?:\.\d+)?|1(?:\.0+)?)$/;
const LINE_HEIGHT_NAME_PATTERN = /line-?height/i;
const UNITLESS_NUMBER_PATTERN = /^(\d*\.)?\d+$/;

const hueToChannel = (p: number, q: number, t: number): number => {
	let adjusted = t;
	if (adjusted < 0) adjusted += 1;
	if (adjusted > 1) adjusted -= 1;
	if (adjusted < 1 / 6) return p + (q - p) * 6 * adjusted;
	if (adjusted < 1 / 2) return q;
	if (adjusted < 2 / 3) return p + (q - p) * (2 / 3 - adjusted) * 6;
	return p;
};

const hslToRgb = (hue: number, saturation: number, lightness: number): [number, number, number] => {
	const hNorm = (hue % 360) / 360;
	const sNorm = Math.max(0, Math.min(100, saturation)) / 100;
	const lNorm = Math.max(0, Math.min(100, lightness)) / 100;

	if (sNorm === 0) {
		const channel = Math.round(lNorm * 255);
		return [channel, channel, channel];
	}

	const q = lNorm < 0.5
		? lNorm * (1 + sNorm)
		: lNorm + sNorm - lNorm * sNorm;
	const p = 2 * lNorm - q;

	return [
		Math.round(hueToChannel(p, q, hNorm + 1 / 3) * 255),
		Math.round(hueToChannel(p, q, hNorm) * 255),
		Math.round(hueToChannel(p, q, hNorm - 1 / 3) * 255),
	];
};

const toHex = (n: number): string => n.toString(16).padStart(2, '0');

const hslToHex = (hue: number, saturation: number, lightness: number): string => {
	const [r, g, b] = hslToRgb(hue, saturation, lightness);
	return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
};

const expandShortHex = (hex: string): string => {
	const lower = hex.toLowerCase();

	if (HEXA_PATTERN.test(lower) || HEX6_PATTERN.test(lower)) {
		return lower;
	}

	const match = lower.match(HEX3_PATTERN);
	if (match) {
		const digits = match[1];
		return `#${digits[0]}${digits[0]}${digits[1]}${digits[1]}${digits[2]}${digits[2]}`;
	}

	return lower;
};

const normalizeColorValue = (value: string): string => {
	const trimmed = value.trim();

	const namedHex = NAMED_COLORS_TO_HEX[trimmed.toLowerCase()];
	if (namedHex) {
		return namedHex;
	}

	if (HEX3_PATTERN.test(trimmed) || HEX6_PATTERN.test(trimmed) || HEXA_PATTERN.test(trimmed)) {
		return expandShortHex(trimmed);
	}

	const hslCommaMatch = trimmed.match(HSL_COMMA_PATTERN) || trimmed.match(HSL_SPACE_PATTERN);
	if (hslCommaMatch) {
		return hslToHex(parseFloat(hslCommaMatch[1]), parseFloat(hslCommaMatch[2]), parseFloat(hslCommaMatch[3]));
	}

	const hslaMatch = trimmed.match(HSLA_COMMA_PATTERN) || trimmed.match(HSL_SLASH_PATTERN);
	if (hslaMatch) {
		const [r, g, b] = hslToRgb(parseFloat(hslaMatch[1]), parseFloat(hslaMatch[2]), parseFloat(hslaMatch[3]));
		const alpha = parseFloat(hslaMatch[4]);
		return `rgba(${r}, ${g}, ${b}, ${alpha})`;
	}

	const rgbMatch = trimmed.match(RGB_PATTERN);
	if (rgbMatch) {
		return `rgb(${parseInt(rgbMatch[1])}, ${parseInt(rgbMatch[2])}, ${parseInt(rgbMatch[3])})`;
	}

	const rgbaMatch = trimmed.match(RGBA_PATTERN);
	if (rgbaMatch) {
		return `rgba(${parseInt(rgbaMatch[1])}, ${parseInt(rgbaMatch[2])}, ${parseInt(rgbaMatch[3])}, ${parseFloat(rgbaMatch[4])})`;
	}

	return trimmed;
};

const normalizeSizeValue = (value: string, variableName: string): string => {
	const trimmed = value.trim();
	const strippedName = variableName.replace(/^-+/, '');

	if (OPACITY_NAME_PATTERN.test(strippedName) && UNITLESS_ZERO_TO_ONE_PATTERN.test(trimmed)) {
		const percentage = Math.round(parseFloat(trimmed) * 100);
		return `${percentage}%`;
	}

	if (LINE_HEIGHT_NAME_PATTERN.test(strippedName) && UNITLESS_NUMBER_PATTERN.test(trimmed)) {
		return `${parseFloat(trimmed)}em`;
	}

	return trimmed;
};

export const normalizeVariableValue = (rawValue: string, existingType: string, variableName: string = ''): string => {
	if (existingType === 'global-font-variable') {
		return normalizeFontValue(rawValue);
	}

	if (existingType === 'global-color-variable') {
		return normalizeColorValue(rawValue);
	}

	if (existingType === 'global-custom-size-variable') {
		return normalizeSizeValue(rawValue, variableName);
	}

	return rawValue.trim();
};
