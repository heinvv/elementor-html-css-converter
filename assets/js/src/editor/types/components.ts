export type ModalHeaderProps = {
	onClose: () => void;
};

export type ModalContentProps = {
	url: string;
	setUrl: (value: string) => void;
	selectors: string;
	setSelectors: (value: string) => void;
	timeout: string;
	setTimeout: (value: string) => void;
	isLoading: boolean;
	statusMessage: string | null;
	statusType: 'success' | 'error' | 'info' | null;
	onSubmit: (e: any) => void;
};

export type ModalActionsProps = {
	onClose: () => void;
	onSubmit: (e?: any) => void | Promise<void>;
	isLoading: boolean;
};

export type ImportModalProps = {
	isOpen: boolean;
	onClose: () => void;
	apiUrl: string;
	postId?: number | null;
};

export type VariablesImportExportModalProps = {
	isOpen: boolean;
	onClose: () => void;
	apiUrl: string;
};

export type ImportTabProps = {
	apiUrl: string;
	isLoading: boolean;
	setIsLoading: (loading: boolean) => void;
	statusMessage: string | null;
	setStatusMessage: (message: string | null) => void;
	statusType: 'success' | 'error' | 'info' | null;
	setStatusType: (type: 'success' | 'error' | 'info' | null) => void;
};

export type ExportTabProps = {
	statusMessage: string | null;
	setStatusMessage: (message: string | null) => void;
	statusType: 'success' | 'error' | 'info' | null;
	setStatusType: (type: 'success' | 'error' | 'info' | null) => void;
};

