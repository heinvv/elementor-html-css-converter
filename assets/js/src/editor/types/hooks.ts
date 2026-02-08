export type UseImportPollingParams = {
	apiUrl: string;
	setStatusMessage: (message: string | null) => void;
	setStatusType: (type: 'success' | 'error' | 'info' | null) => void;
	setIsLoading: (loading: boolean) => void;
	onClose: () => void;
	isOpen: boolean;
};

export type UseImportSubmitParams = {
	url: string;
	selectors: string;
	timeout: string;
	apiUrl: string;
	postId: number | null | undefined;
	setIsLoading: (loading: boolean) => void;
	setStatusMessage: (message: string | null) => void;
	setStatusType: (type: 'success' | 'error' | 'info' | null) => void;
	startPolling: (jobId: string) => void;
};
