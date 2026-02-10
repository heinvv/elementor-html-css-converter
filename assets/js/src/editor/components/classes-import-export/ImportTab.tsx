import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { AssessmentResult, ImportTabProps, ResolutionAction } from '../../types/components';
import { syncElementorClassesToStore } from '../../utils/syncElementorClassesToStore';
import { parseCssClassNames } from '../../utils/parseCssClassNames';
import { assessClassConflicts } from '../../utils/assessClassConflicts';
import { ClassConflictAssessment } from './ConflictAssessment';

type ImportPhase = 'input' | 'assessing' | 'importing';

export const ClassesImportTab = ({
	apiUrl,
	isLoading,
	setIsLoading,
	statusMessage,
	setStatusMessage,
	statusType,
	setStatusType,
	onClose,
}: ImportTabProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { useState } = React;
	const [importText, setImportText] = useState('');
	const [phase, setPhase] = useState<ImportPhase>('input');
	const [assessment, setAssessment] = useState<AssessmentResult | null>(null);
	const [importStats, setImportStats] = useState(null as {
		detected: number;
		converted: number;
		registered: number;
		skipped: number;
		updated: number;
	} | null);

	const {
		Stack: StackComponent,
		TextField: TextFieldComponent,
		Button: ButtonComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Typography: TypographyComponent,
	} = ui;

	const executeImport = async (resolutions: Record<string, ResolutionAction>, renameMap: Record<string, string> = {}) => {
		setIsLoading(true);
		setStatusMessage(null);
		setStatusType(null);
		setImportStats(null);
		setPhase('importing');

		try {
			const fullUrl = apiUrl + 'import-classes';
			const payload = {
				css: importText.trim(),
				update_mode: 'create_new',
				context: 'preview',
				resolutions,
				rename_map: renameMap,
			};

			const response = await fetch(fullUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(payload),
			});

			const data = await response.json();

			if (data.success) {
				const stats = data.statistics || {};
				setImportStats(stats);

				const parts: string[] = [];
				if (stats.registered > 0) parts.push(`${stats.registered} created`);
				if (stats.skipped > 0) parts.push(`${stats.skipped} reused`);
				if (stats.updated > 0) parts.push(`${stats.updated} updated`);

				const overflowCount = data.overflow?.length || 0;
				if (overflowCount > 0) {
					parts.push(`${overflowCount} skipped (limit reached)`);
				}

				setStatusMessage(`Import complete: ${parts.join(', ')}.`);
				setStatusType('success');
				setImportText('');

				await syncElementorClassesToStore(apiUrl);
				window.dispatchEvent(new Event('classes:updated'));
			} else {
				setStatusMessage(data.error || data.message || 'Import failed.');
				setStatusType('error');
			}
		} catch (error) {
			setStatusMessage(
				'Import failed: ' + (error instanceof Error ? error.message : 'Unknown error')
			);
			setStatusType('error');
		} finally {
			setIsLoading(false);
			setPhase('input');
			setAssessment(null);
		}
	};

	const handleImportClick = () => {
		if (!importText.trim()) {
			setStatusMessage('Please paste CSS classes to import.');
			setStatusType('error');
			return;
		}

		setStatusMessage(null);
		setStatusType(null);
		setImportStats(null);

		const parsed = parseCssClassNames(importText);

		if (parsed.length === 0) {
			setStatusMessage('No CSS classes found in the input.');
			setStatusType('error');
			return;
		}

		const result = assessClassConflicts(parsed);

		if (result.hasConflicts) {
			setAssessment(result);
			setPhase('assessing');
			return;
		}

		executeImport(result.autoResolutions);
	};

	const handleAssessmentConfirm = (resolutions: Record<string, ResolutionAction>, renameMap: Record<string, string>) => {
		executeImport(resolutions, renameMap);
	};

	const handleAssessmentCancel = () => {
		setPhase('input');
		setAssessment(null);
	};

	if (phase === 'assessing' && assessment) {
		return (
			<ClassConflictAssessment
				assessment={assessment}
				onConfirm={handleAssessmentConfirm}
				onCancel={handleAssessmentCancel}
				isLoading={isLoading}
			/>
		);
	}

	const importSucceeded = statusType === 'success' && importStats;

	return (
		<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
			{!importSucceeded && TypographyComponent && (
				<TypographyComponent variant="body2" color="text.secondary">
					Paste CSS class definitions below to import them as global classes.
				</TypographyComponent>
			)}
			{!importSucceeded && (
				<TextFieldComponent
					fullWidth
					multiline
					rows={10}
					value={importText}
					onChange={(e: any) => setImportText(e.target.value)}
					placeholder={`.btn-primary {\n\tbackground-color: #6200ee;\n\tcolor: #ffffff;\n\tpadding: 12px 24px;\n\tborder-radius: 4px;\n}`}
					disabled={isLoading}
					sx={{
						'& .MuiInputBase-input': {
							fontFamily: 'monospace',
							fontSize: '13px',
						},
					}}
				/>
			)}
			{!importSucceeded && (
				<ButtonComponent
					variant="contained"
					color="primary"
					disabled={isLoading || !importText.trim()}
					onClick={handleImportClick}
					sx={{
						backgroundColor: '#F3BAFD',
						color: 'rgb(12, 13, 14)',
						'&:hover': {
							backgroundColor: '#e0a0ee',
							color: 'rgb(12, 13, 14)',
						},
					}}
				>
					{isLoading ? 'Importing...' : 'Import Classes'}
				</ButtonComponent>
			)}
			{statusMessage && (
				<BoxComponent>
					<AlertComponent
						severity={statusType || 'info'}
						sx={importSucceeded ? { fontSize: '14px', padding: '16px 20px' } : {}}
					>
						<StackComponent direction="column" spacing={1}>
							{TypographyComponent && (
								<TypographyComponent variant="subtitle2" sx={{ fontWeight: 600 }}>
									{statusMessage}
								</TypographyComponent>
							)}
							{importSucceeded && importStats && (
								<BoxComponent
									component="ul"
									sx={{
										margin: 0,
										paddingLeft: '20px',
										listStyleType: 'disc',
									}}
								>
									<BoxComponent
										component="li"
										sx={{ fontFamily: 'monospace', fontSize: '13px', paddingBlock: '2px' }}
									>
										Detected: {importStats.detected} classes
									</BoxComponent>
									<BoxComponent
										component="li"
										sx={{ fontFamily: 'monospace', fontSize: '13px', paddingBlock: '2px' }}
									>
										Converted: {importStats.converted} classes
									</BoxComponent>
								</BoxComponent>
							)}
						</StackComponent>
					</AlertComponent>
				</BoxComponent>
			)}
			{importSucceeded && (
				<StackComponent direction="row" spacing={1}>
					<ButtonComponent
						variant="outlined"
						size="small"
						onClick={() => {
							setStatusMessage(null);
							setStatusType(null);
							setImportStats(null);
						}}
						sx={{ width: '50%' }}
					>
						Import More
					</ButtonComponent>
					<ButtonComponent
						variant="contained"
						size="small"
						onClick={onClose}
						sx={{
							width: '50%',
							backgroundColor: '#F3BAFD',
							color: 'rgb(12, 13, 14)',
							'&:hover': {
								backgroundColor: '#e0a0ee',
								color: 'rgb(12, 13, 14)',
							},
						}}
					>
						Close modal
					</ButtonComponent>
				</StackComponent>
			)}
		</StackComponent>
	);
};
