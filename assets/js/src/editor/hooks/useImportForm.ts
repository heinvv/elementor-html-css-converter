import { getReact } from '../utils/getReact';

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

	const resetForm = () => {
		setUrl('');
		setSelectors('');
		setTimeout('60');
		setStatusMessage(null);
		setStatusType(null);
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
		resetForm,
	};
};
