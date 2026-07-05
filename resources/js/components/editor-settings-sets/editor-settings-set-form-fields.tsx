import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import type { PublicationEditorFont } from '@/types';

type EditorSettingsSetFormFieldsProps = {
    name: string;
    font: PublicationEditorFont;
    hasMarginalColumn: boolean;
    onNameChange: (value: string) => void;
    onFontChange: (value: PublicationEditorFont) => void;
    onHasMarginalColumnChange: (value: boolean) => void;
    errors: {
        name?: string;
        font?: string;
        has_marginal_column?: string;
    };
};

export default function EditorSettingsSetFormFields({
    name,
    font,
    hasMarginalColumn,
    onNameChange,
    onFontChange,
    onHasMarginalColumnChange,
    errors,
}: EditorSettingsSetFormFieldsProps) {
    const { t } = useTranslation();

    return (
        <>
            <input type="hidden" name="font" value={font} />
            <input
                type="hidden"
                name="has_marginal_column"
                value={hasMarginalColumn ? '1' : '0'}
            />

            <div className="grid gap-2">
                <Label htmlFor="name">{t('common.name')}</Label>
                <Input
                    id="name"
                    name="name"
                    value={name}
                    onChange={(event) => onNameChange(event.target.value)}
                    placeholder={t('editor.form.name_placeholder')}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="font">{t('editor.form.font_label')}</Label>
                <Select
                    value={font}
                    onValueChange={(value) =>
                        onFontChange(value as PublicationEditorFont)
                    }
                >
                    <SelectTrigger id="font">
                        <SelectValue
                            placeholder={t('editor.form.font_placeholder')}
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="spectral">
                            {t('editor.form.font.spectral')}
                        </SelectItem>
                        <SelectItem value="roboto">
                            {t('editor.form.font.roboto')}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.font} />
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    id="has_marginal_column"
                    checked={hasMarginalColumn}
                    onCheckedChange={(checked) =>
                        onHasMarginalColumnChange(checked === true)
                    }
                />
                <Label htmlFor="has_marginal_column">
                    {t('editor.form.marginal_column')}
                </Label>
            </div>
            <InputError message={errors.has_marginal_column} />
        </>
    );
}
