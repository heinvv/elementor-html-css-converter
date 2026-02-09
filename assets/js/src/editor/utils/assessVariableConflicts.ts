import {
	AssessmentResult,
	ConflictVariable,
	ParsedCssVariable,
	ResolutionAction,
} from '../types/components';

const VARIABLES_STORAGE_KEY = 'elementor-global-variables';

type StoredVariable = {
	type: string;
	label: string;
	value: string;
	order?: number;
	deleted?: boolean;
};

type StoredVariablesMap = Record<string, StoredVariable>;

const loadExistingVariables = (): StoredVariablesMap => {
	const raw = localStorage.getItem(VARIABLES_STORAGE_KEY);
	if (!raw) {
		return {};
	}

	try {
		return JSON.parse(raw) as StoredVariablesMap;
	} catch {
		return {};
	}
};

const stripPrefix = (name: string): string => {
	return name.replace(/^--/, '');
};

const findByLabel = (
	variables: StoredVariablesMap,
	label: string,
): { id: string; variable: StoredVariable } | null => {
	const labelLower = label.toLowerCase();

	for (const [id, variable] of Object.entries(variables)) {
		if (variable.label && variable.label.toLowerCase() === labelLower) {
			return { id, variable };
		}
	}

	return null;
};

export const assessVariableConflicts = (parsed: ParsedCssVariable[]): AssessmentResult => {
	const existing = loadExistingVariables();
	const conflicts: ConflictVariable[] = [];
	const autoResolutions: Record<string, ResolutionAction> = {};
	let newCount = 0;
	let skipCount = 0;
	let reactivateCount = 0;

	for (const parsedVar of parsed) {
		const label = stripPrefix(parsedVar.name);
		const match = findByLabel(existing, label);

		if (!match) {
			autoResolutions[parsedVar.name] = 'create';
			newCount++;
			continue;
		}

		if (match.variable.deleted) {
			autoResolutions[parsedVar.name] = 'reactivate';
			reactivateCount++;
			continue;
		}

		if (match.variable.value === parsedVar.value) {
			autoResolutions[parsedVar.name] = 'skip';
			skipCount++;
			continue;
		}

		conflicts.push({
			name: parsedVar.name,
			label,
			currentValue: match.variable.value,
			newValue: parsedVar.value,
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
