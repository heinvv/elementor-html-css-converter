import { ParsedCssVariable } from '../types/components';

const CSS_COMMENT_PATTERN = /\/\*.*?\*\//gs;
const CSS_VARIABLE_PATTERN = /(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g;

export const parseCssVariables = (css: string): ParsedCssVariable[] => {
	const withoutComments = css.replace(CSS_COMMENT_PATTERN, '');
	const variables: ParsedCssVariable[] = [];
	let match: RegExpExecArray | null;

	while ((match = CSS_VARIABLE_PATTERN.exec(withoutComments)) !== null) {
		const name = match[1].trim();
		const value = match[2].trim();

		if (name && value) {
			variables.push({ name, value });
		}
	}

	return variables;
};
