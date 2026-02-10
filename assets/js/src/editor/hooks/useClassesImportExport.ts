import { getReact } from '../utils/getReact';

export const useClassesImportExport = () => {
	const ReactLib = getReact();
	if (!ReactLib) {
		return null;
	}

	const { useState } = ReactLib;
	const [activeTab, setActiveTab] = useState(0);
	const [isLoading, setIsLoading] = useState(false);
	const [statusMessage, setStatusMessage] = useState<string | null>(null);
	const [statusType, setStatusType] = useState<'success' | 'error' | 'info' | null>(null);

	const resetState = () => {
		setActiveTab(0);
		setIsLoading(false);
		setStatusMessage(null);
		setStatusType(null);
	};

	return {
		activeTab,
		setActiveTab,
		isLoading,
		setIsLoading,
		statusMessage,
		setStatusMessage,
		statusType,
		setStatusType,
		resetState,
	};
};
