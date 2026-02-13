import { getReact } from '../utils/getReact';
import type { ScrapeDiagnostics } from '../types/hooks';


export const useImportForm = () => {
	const ReactLib = getReact();
	if (!ReactLib) {
		return null;
	}

	const { useState } = ReactLib;
	const [url, setUrl] = useState('');
	const [selectors, setSelectors] = useState('');
	const [timeout, setTimeout] = useState('60');
	const [isLoading, setIsLoading] = useState(false);
	const [statusMessage, setStatusMessage] = useState<string | null>(null);
	const [statusType, setStatusType] = useState<'success' | 'error' | 'info' | null>(null);
	const [diagnostics, setDiagnostics] = useState<ScrapeDiagnostics | null>(null);

	const resetForm = () => {
		setUrl('');
		setSelectors('');
		setTimeout('60');
		setStatusMessage(null);
		setStatusType(null);
		setDiagnostics(null);
		setIsLoading(false);
	};

	return {
		url,
		setUrl,
		selectors,
		setSelectors,
		timeout,
		setTimeout,
		isLoading,
		setIsLoading,
		statusMessage,
		setStatusMessage,
		statusType,
		setStatusType,
		diagnostics,
		setDiagnostics,
		resetForm,
	};
};
