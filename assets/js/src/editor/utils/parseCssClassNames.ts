export type ParsedCssClass = {
	name: string;
	properties: string;
};

const CSS_COMMENT_PATTERN = /\/\*.*?\*\//gs;
const CSS_MEDIA_QUERY_PATTERN = /@media[^{]+\{([\s\S]*?\})\s*\}/g;
const CSS_CLASS_RULE_PATTERN = /\.([a-zA-Z_-][a-zA-Z0-9_-]*)\s*\{([^}]+)\}/g;

const extractClassRulesFromBlock = (cssBlock: string): Map<string, string> => {
	const classes = new Map<string, string>();
	let match: RegExpExecArray | null;

	const pattern = new RegExp(CSS_CLASS_RULE_PATTERN.source, 'g');
	while ((match = pattern.exec(cssBlock)) !== null) {
		const className = match[1].trim();
		const properties = match[2].trim();

		if (className && properties) {
			const existing = classes.get(className);
			if (existing) {
				classes.set(className, existing + '; ' + properties);
			} else {
				classes.set(className, properties);
			}
		}
	}

	return classes;
};

const abbreviateProperties = (properties: string): string => {
	const declarations = properties
		.split(';')
		.map((d) => d.trim())
		.filter(Boolean);

	const MAX_DISPLAY = 3;

	if (declarations.length <= MAX_DISPLAY) {
		return declarations.join('; ');
	}

	const shown = declarations.slice(0, MAX_DISPLAY).join('; ');
	const remaining = declarations.length - MAX_DISPLAY;
	return `${shown}; ... +${remaining} more`;
};

export const parseCssClassNames = (css: string): ParsedCssClass[] => {
	const withoutComments = css.replace(CSS_COMMENT_PATTERN, '');
	const allClasses = new Map<string, string>();

	const desktopClasses = extractClassRulesFromBlock(withoutComments);
	for (const [name, props] of desktopClasses) {
		allClasses.set(name, props);
	}

	let mediaMatch: RegExpExecArray | null;
	const mediaPattern = new RegExp(CSS_MEDIA_QUERY_PATTERN.source, 'g');
	while ((mediaMatch = mediaPattern.exec(withoutComments)) !== null) {
		const mediaBlock = mediaMatch[1];
		const mediaClasses = extractClassRulesFromBlock(mediaBlock);
		for (const [name, props] of mediaClasses) {
			if (!allClasses.has(name)) {
				allClasses.set(name, props);
			}
		}
	}

	const result: ParsedCssClass[] = [];
	for (const [name, properties] of allClasses) {
		result.push({
			name,
			properties: abbreviateProperties(properties),
		});
	}

	return result;
};
