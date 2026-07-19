import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
import type { ManagedUserRole, UserRoleOption } from '@/types';

type UserFormFieldsProps = {
    name: string;
    email: string;
    role: ManagedUserRole;
    roles: UserRoleOption[];
    passwordRequired?: boolean;
    onNameChange: (value: string) => void;
    onEmailChange: (value: string) => void;
    onRoleChange: (value: ManagedUserRole) => void;
    errors: {
        name?: string;
        email?: string;
        password?: string;
        password_confirmation?: string;
        role?: string;
    };
};

export default function UserFormFields({
    name,
    email,
    role,
    roles,
    passwordRequired = true,
    onNameChange,
    onEmailChange,
    onRoleChange,
    errors,
}: UserFormFieldsProps) {
    const { t } = useTranslation();

    return (
        <>
            <input type="hidden" name="role" value={role} />

            <div className="grid gap-2">
                <Label htmlFor="name">{t('users.form.name')}</Label>
                <Input
                    id="name"
                    name="name"
                    value={name}
                    onChange={(event) => onNameChange(event.target.value)}
                    required
                    autoComplete="name"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">{t('users.form.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    value={email}
                    onChange={(event) => onEmailChange(event.target.value)}
                    required
                    autoComplete="username"
                />
                <InputError message={errors.email} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password">
                    {passwordRequired
                        ? t('users.form.password')
                        : t('users.form.password_optional')}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required={passwordRequired}
                    autoComplete="new-password"
                />
                <InputError message={errors.password} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">
                    {t('users.form.password_confirmation')}
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required={passwordRequired}
                    autoComplete="new-password"
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="role">{t('users.form.role')}</Label>
                <Select
                    value={role}
                    onValueChange={(value) =>
                        onRoleChange(value as ManagedUserRole)
                    }
                >
                    <SelectTrigger id="role">
                        <SelectValue
                            placeholder={t('common.select_placeholder')}
                        />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.role} />
            </div>
        </>
    );
}
