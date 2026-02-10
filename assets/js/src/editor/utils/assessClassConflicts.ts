import { AssessmentResult, ConflictVariable, ResolutionAction } from '../types/components';
import { ParsedCssClass } from './parseCssClassNames';

const CLASSES_STORAGE_KEY = 'elementor-global-classes';

type StoredClass = {
	id: string;
	type: string;
	label: string;
	variants: any[];
};

const loadExistingClasses = (): StoredClass[] => {
	const raw = localStorage.getItem(CLASSES_STORAGE_KEY);
	if (!raw) {
		return [];
	}

	try {
		const parsed = JSON.parse(raw);
		if (Array.isArray(parsed)) {
			return parsed;
		}
		return [];
	} catch {
		return [];
	}
};

const findByLabel = (classes: StoredClass[], label: string): StoredClass | null => {
	const labelLower = label.toLowerCase();
	return classes.find((cls) => cls.label && cls.label.toLowerCase() === labelLower) || null;
};

const summarizeVariantProperties = (variants: any[]): string => {
	if (!variants || variants.length === 0) {
		return 'No styles';
	}

	const desktopVariant = variants.find(
		(v: any) => v?.meta?.breakpoint === 'desktop' && v?.meta?.state === null
	);

	const variant = desktopVariant || variants[0];
	const props = variant?.props || {};
	const propKeys = Object.keys(props);

	if (propKeys.length === 0) {
		const customCss = variant?.custom_css?.raw;
		if (customCss) {
			return 'Custom CSS styles';
		}
		return 'No styles';
	}

	const MAX_DISPLAY = 3;
	const shown = propKeys.slice(0, MAX_DISPLAY).join(', ');

	if (propKeys.length > MAX_DISPLAY) {
		return `${shown}, ... +${propKeys.length - MAX_DISPLAY} more`;
	}

	return shown;
};

export const assessClassConflicts = (parsed: ParsedCssClass[]): AssessmentResult => {
	const existing = loadExistingClasses();
	const conflicts: ConflictVariable[] = [];
	const autoResolutions: Record<string, ResolutionAction> = {};
	let newCount = 0;
	let skipCount = 0;
	const reactivateCount = 0;

	for (const parsedClass of parsed) {
		const match = findByLabel(existing, parsedClass.name);

		if (!match) {
			autoResolutions[parsedClass.name] = 'create';
			newCount++;
			continue;
		}

		conflicts.push({
			name: parsedClass.name,
			label: parsedClass.name,
			currentValue: summarizeVariantProperties(match.variants),
			newValue: parsedClass.properties,
		});
	}

	return {
		conflicts,
		autoResolutions,
		newCount,
		skipCount,
		reactivateCount,
		hasConflicts: conflicts.length > 0,
	};
};
