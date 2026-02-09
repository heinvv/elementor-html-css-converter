import { getReact } from '../utils/getReact';

const VARIABLES_STORAGE_KEY = 'elementor-global-variables';

type VariableEntry = {
	type: string;
	label: string;
	value: string;
	order?: number;
	deleted?: boolean;
};

type VariablesMap = Record<string, VariableEntry>;

export const formatVariablesAsCss = (variables: VariablesMap): string => {
	const entries = Object.values(variables)
		.filter((variable) => !variable.deleted)
		.sort((a, b) => (a.order ?? Infinity) - (b.order ?? Infinity))
		.map((variable) => `\t--${variable.label}: ${variable.value};`);

	if (entries.length === 0) {
		return '';
	}

	return `:root {\n${entries.join('\n')}\n}\n`;
};

export const loadVariablesFromStorage = (): VariablesMap => {
	const raw = localStorage.getItem(VARIABLES_STORAGE_KEY);
	if (!raw) {
		return {};
	}

	try {
		return JSON.parse(raw) as VariablesMap;
	} catch {
		return {};
	}
};

export const useVariablesImportExport = () => {
	const ReactLib = getReact();
	if (!ReactLib) {
		return null;
	}

	const { useState } = ReactLib;
	const [activeTab, setActiveTab] = useState(0);
	const [importText, setImportText] = useState('');
	const [isLoading, setIsLoading] = useState(false);
	const [statusMessage, setStatusMessage] = useState<string | null>(null);
	const [statusType, setStatusType] = useState<'success' | 'error' | 'info' | null>(null);

	const resetState = () => {
		setActiveTab(0);
		setImportText('');
		setIsLoading(false);
		setStatusMessage(null);
		setStatusType(null);
	};

	return {
		activeTab,
		setActiveTab,
		importText,
		setImportText,
		isLoading,
		setIsLoading,
		statusMessage,
		setStatusMessage,
		statusType,
		setStatusType,
		resetState,
	};
};
