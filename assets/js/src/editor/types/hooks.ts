export type ScrapeDiagnostics = {
	pageTitle?: string;
	finalUrl?: string;
	htmlPreview?: string;
	screenshotBase64?: string;
	consoleMessages?: string[];
	requestedUrl?: string;
	selectors?: string[];
	timing?: Record<string, number>;
	botDetection?: {
		detected: boolean;
		type?: 'captcha' | 'redirect' | 'access-denied' | 'rate-limit' | 'unknown';
		confidence: 'high' | 'medium' | 'low';
		evidence: string[];
		suggestion: string;
	};
};

export type UseImportPollingParams = {
	apiUrl: string;
	setStatusMessage: (message: string | null) => void;
	setStatusType: (type: 'success' | 'error' | 'info' | null) => void;
	setIsLoading: (loading: boolean) => void;
	setDiagnostics: (d: ScrapeDiagnostics | null) => void;
	onClose: () => void;
	isOpen: boolean;
	postId: number | null | undefined;
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
	startPolling: (
		jobId: string,
		scraperEndpoint: string,
		url?: string,
		selectors?: string,
		timings?: Record<string, number>,
	) => void;
};
