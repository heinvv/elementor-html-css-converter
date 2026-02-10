import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { ConflictAssessmentProps, ResolutionAction } from '../../types/components';

const OVERWRITE_ACTION: ResolutionAction = 'overwrite';
const RENAME_ACTION: ResolutionAction = 'rename';
const SKIP_ACTION: ResolutionAction = 'skip';

const ACTIVE_BUTTON_STYLE = {
	backgroundColor: '#F3BAFD',
	color: 'rgb(12, 13, 14)',
	'&:hover': {
		backgroundColor: '#e0a0ee',
		color: 'rgb(12, 13, 14)',
	},
};

export const ClassConflictAssessment = ({
	assessment,
	onConfirm,
	onCancel,
	isLoading,
}: ConflictAssessmentProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { useState } = React;

	const defaultChoices: Record<string, ResolutionAction> = {};
	for (const conflict of assessment.conflicts) {
		defaultChoices[conflict.name] = OVERWRITE_ACTION;
	}

	const [choices, setChoices] = useState<Record<string, ResolutionAction>>(defaultChoices);
	const [renameValues, setRenameValues] = useState<Record<string, string>>({});

	const {
		Stack: StackComponent,
		Box: BoxComponent,
		Button: ButtonComponent,
		Typography: TypographyComponent,
		Alert: AlertComponent,
		TextField: TextFieldComponent,
		Radio: RadioComponent,
		RadioGroup: RadioGroupComponent,
		FormControlLabel: FormControlLabelComponent,
	} = ui;

	const handleChoiceChange = (className: string, action: ResolutionAction) => {
		setChoices((prev: Record<string, ResolutionAction>) => ({
			...prev,
			[className]: action,
		}));

		if (action === RENAME_ACTION && !renameValues[className]) {
			const conflict = assessment.conflicts.find((c) => c.name === className);
			if (conflict) {
				setRenameValues((prev: Record<string, string>) => ({
					...prev,
					[className]: conflict.label + '-copy',
				}));
			}
		}
	};

	const handleRenameValueChange = (className: string, newLabel: string) => {
		setRenameValues((prev: Record<string, string>) => ({
			...prev,
			[className]: newLabel,
		}));
	};

	const handleConfirm = () => {
		const allResolutions: Record<string, ResolutionAction> = {
			...assessment.autoResolutions,
			...choices,
		};

		const filteredRenameMap: Record<string, string> = {};
		for (const [className, action] of Object.entries(allResolutions)) {
			if (action === RENAME_ACTION && renameValues[className]) {
				filteredRenameMap[className] = renameValues[className];
			}
		}

		onConfirm(allResolutions, filteredRenameMap);
	};

	const summaryParts: string[] = [];
	if (assessment.newCount > 0) {
		summaryParts.push(`${assessment.newCount} new`);
	}
	if (assessment.skipCount > 0) {
		summaryParts.push(`${assessment.skipCount} unchanged`);
	}

	return (
		<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
			{summaryParts.length > 0 && TypographyComponent && (
				<TypographyComponent variant="body2" color="text.secondary">
					Auto-handled: {summaryParts.join(', ')}
				</TypographyComponent>
			)}

			{AlertComponent && (
				<AlertComponent severity="warning">
					{assessment.conflicts.length} class{assessment.conflicts.length !== 1 ? 'es' : ''} already exist with different styles.
				</AlertComponent>
			)}

			<BoxComponent sx={{ maxHeight: '300px', overflowY: 'auto' }}>
				<StackComponent direction="column" spacing={1.5}>
					{assessment.conflicts.map((conflict) => (
						<BoxComponent
							key={conflict.name}
							sx={{
								border: '1px solid',
								borderColor: 'divider',
								borderRadius: '4px',
								padding: '12px',
							}}
						>
							{TypographyComponent && (
								<TypographyComponent
									variant="subtitle2"
									sx={{ fontFamily: 'monospace', marginBottom: '8px' }}
								>
									.{conflict.name}
								</TypographyComponent>
							)}

							<StackComponent direction="row" spacing={2} sx={{ marginBottom: '8px' }}>
								<BoxComponent sx={{ flex: 1 }}>
									{TypographyComponent && (
										<TypographyComponent variant="caption" color="text.secondary">
											Current
										</TypographyComponent>
									)}
									{TypographyComponent && (
										<TypographyComponent
											variant="body2"
											sx={{ fontFamily: 'monospace', fontSize: '12px' }}
										>
											{conflict.currentValue}
										</TypographyComponent>
									)}
								</BoxComponent>
								<BoxComponent sx={{ flex: 1 }}>
									{TypographyComponent && (
										<TypographyComponent variant="caption" color="text.secondary">
											New
										</TypographyComponent>
									)}
									{TypographyComponent && (
										<TypographyComponent
											variant="body2"
											sx={{ fontFamily: 'monospace', fontSize: '12px' }}
										>
											{conflict.newValue}
										</TypographyComponent>
									)}
								</BoxComponent>
							</StackComponent>

						{RadioGroupComponent && FormControlLabelComponent && RadioComponent && (
							<RadioGroupComponent
								value={choices[conflict.name]}
								onChange={(e: any) => handleChoiceChange(conflict.name, e.target.value as ResolutionAction)}
								sx={{ flexDirection: 'row' }}
							>
								<FormControlLabelComponent
									value={OVERWRITE_ACTION}
									control={<RadioComponent />}
									label="Overwrite"
								/>
								<FormControlLabelComponent
									value={RENAME_ACTION}
									control={<RadioComponent />}
									label="Rename"
								/>
								<FormControlLabelComponent
									value={SKIP_ACTION}
									control={<RadioComponent />}
									label="Skip"
								/>
							</RadioGroupComponent>
						)}

						{choices[conflict.name] === RENAME_ACTION && TextFieldComponent && (
							<StackComponent direction="row" spacing={0.5} alignItems="center" sx={{ marginTop: '8px' }}>
								{TypographyComponent && (
									<TypographyComponent
										variant="body2"
										sx={{ fontFamily: 'monospace', fontSize: '13px', color: 'text.secondary' }}
									>
										.
									</TypographyComponent>
								)}
								<TextFieldComponent
									size="small"
									fullWidth
									value={renameValues[conflict.name] || ''}
									onChange={(e: any) => handleRenameValueChange(conflict.name, e.target.value)}
									placeholder={conflict.label}
									sx={{
										'& .MuiInputBase-input': {
											fontFamily: 'monospace',
											fontSize: '13px',
											padding: '4px 8px',
										},
									}}
								/>
							</StackComponent>
						)}
						</BoxComponent>
					))}
				</StackComponent>
			</BoxComponent>

			<StackComponent direction="row" spacing={1} justifyContent="flex-end">
				<ButtonComponent
					size="small"
					variant="outlined"
					onClick={onCancel}
					disabled={isLoading}
				>
					Cancel
				</ButtonComponent>
				<ButtonComponent
					size="small"
					variant="contained"
					onClick={handleConfirm}
					disabled={isLoading}
					sx={ACTIVE_BUTTON_STYLE}
				>
					{isLoading ? 'Importing...' : 'Confirm Import'}
				</ButtonComponent>
			</StackComponent>
		</StackComponent>
	);
};
