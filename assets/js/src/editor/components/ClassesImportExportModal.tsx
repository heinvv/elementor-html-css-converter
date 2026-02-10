import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { useClassesImportExport } from '../hooks/useClassesImportExport';
import { ClassesImportTab } from './classes-import-export/ImportTab';
import { ClassesExportTab } from './classes-import-export/ExportTab';
import { VariablesImportExportModalProps } from '../types/components';

export const ClassesImportExportModal = ({ isOpen, onClose, apiUrl }: VariablesImportExportModalProps) => {
	const React = getReact();
	if (!React) {
		return null;
	}

	const ui = getElementorUI();
	if (!ui) {
		return null;
	}

	const state = useClassesImportExport();
	if (!state) {
		return null;
	}

	const {
		activeTab,
		setActiveTab,
		isLoading,
		setIsLoading,
		statusMessage,
		setStatusMessage,
		statusType,
		setStatusType,
		resetState,
	} = state;

	const handleClose = () => {
		resetState();
		onClose();
	};

	if (!isOpen) {
		return null;
	}

	const {
		Dialog: DialogComponent,
		Box: BoxComponent,
		Tab: TabComponent,
		Tabs: TabsComponent,
	} = ui;

	if (!DialogComponent || !BoxComponent) {
		console.error('[EHCC] Classes Import/Export: Required UI components not available');
		return null;
	}

	const handleTabChange = (_event: any, newValue: number) => {
		setStatusMessage(null);
		setStatusType(null);
		setActiveTab(newValue);
	};

	return (
		<DialogComponent
			open={isOpen}
			onClose={handleClose}
			maxWidth="sm"
			fullWidth
			sx={{
				'& .MuiDialog-paper': {
					fontFamily: 'var(--e-a-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif)',
					backgroundColor: 'var(--e-a-bg-default, #fff)',
				},
			}}
		>
			<BoxComponent
				sx={{
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'space-between',
					height: '50px',
					minHeight: '50px',
					borderBottom: 'var(--e-a-border, 1px solid #d5dade)',
				}}
			>
				<BoxComponent
					className="elementor-templates-modal__header__logo-area"
					sx={{
						paddingInlineStart: '15px',
					}}
				>
					<BoxComponent
						className="elementor-templates-modal__header__logo"
						sx={{
							display: 'flex',
							alignItems: 'center',
							lineHeight: 1,
						}}
					>
						<BoxComponent
							component="span"
							className="elementor-templates-modal__header__logo__icon-wrapper e-logo-wrapper"
							sx={{
								display: 'inline-block',
								lineHeight: 1,
								marginInlineEnd: '8px',
							}}
						>
							<i
								className="eicon-elementor-circle"
								style={{
									color: 'var(--e-a-color-circle-logo)',
									fontSize: '2.5em',
								}}
							/>
						</BoxComponent>
						<BoxComponent
							component="span"
							className="elementor-templates-modal__header__logo__title"
							sx={{
								color: 'var(--e-a-color-txt-active, #515962)',
								fontSize: '12px',
								textTransform: 'uppercase',
								fontWeight: 'bold',
							}}
						>
							Import / Export Classes
						</BoxComponent>
					</BoxComponent>
				</BoxComponent>
				<BoxComponent
					sx={{
						display: 'flex',
						flexDirection: 'row-reverse',
					}}
				>
					<BoxComponent
						onClick={handleClose}
						sx={{
							cursor: 'pointer',
							width: '47px',
							borderInlineStart: 'var(--e-a-border, 1px solid #d5dade)',
							position: 'relative',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							boxSizing: 'content-box',
						}}
					>
						<i
							className="eicon-close"
							aria-hidden={true}
							style={{
								fontSize: '18px',
								transition: 'var(--e-a-transition-hover, all 0.3s)',
								cursor: 'pointer',
							}}
							onMouseEnter={(e: any) => {
								e.currentTarget.style.color = 'var(--e-a-color-txt-hover, #5e72e4)';
							}}
							onMouseLeave={(e: any) => {
								e.currentTarget.style.color = '';
							}}
						/>
						<span className="elementor-screen-only">Close</span>
					</BoxComponent>
				</BoxComponent>
			</BoxComponent>

			{TabsComponent && TabComponent ? (
				<BoxComponent sx={{ borderBottom: 1, borderColor: 'divider' }}>
					<TabsComponent
						value={activeTab}
						onChange={handleTabChange}
						variant="fullWidth"
					>
						<TabComponent label="Import" />
						<TabComponent label="Export" />
					</TabsComponent>
				</BoxComponent>
			) : (
				<BoxComponent
					sx={{
						display: 'flex',
						borderBottom: 'var(--e-a-border, 1px solid #d5dade)',
					}}
				>
					<BoxComponent
						onClick={() => handleTabChange(null, 0)}
						sx={{
							flex: 1,
							padding: '10px',
							textAlign: 'center',
							cursor: 'pointer',
							fontWeight: activeTab === 0 ? 'bold' : 'normal',
							borderBottom: activeTab === 0 ? '2px solid #F3BAFD' : 'none',
						}}
					>
						Import
					</BoxComponent>
					<BoxComponent
						onClick={() => handleTabChange(null, 1)}
						sx={{
							flex: 1,
							padding: '10px',
							textAlign: 'center',
							cursor: 'pointer',
							fontWeight: activeTab === 1 ? 'bold' : 'normal',
							borderBottom: activeTab === 1 ? '2px solid #F3BAFD' : 'none',
						}}
					>
						Export
					</BoxComponent>
				</BoxComponent>
			)}

			{activeTab === 0 && (
				<ClassesImportTab
					apiUrl={apiUrl}
					isLoading={isLoading}
					setIsLoading={setIsLoading}
					statusMessage={statusMessage}
					setStatusMessage={setStatusMessage}
					statusType={statusType}
					setStatusType={setStatusType}
					onClose={handleClose}
				/>
			)}

		{activeTab === 1 && (
			<ClassesExportTab
				apiUrl={apiUrl}
				statusMessage={statusMessage}
				setStatusMessage={setStatusMessage}
				statusType={statusType}
				setStatusType={setStatusType}
			/>
		)}
		</DialogComponent>
	);
};
