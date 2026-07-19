import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import UserFormFields from '@/components/users/user-form-fields';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { edit, index } from '@/routes/users';
import type { ManagedUser, ManagedUserRole, UserRoleOption } from '@/types';

type PageProps = {
    user: ManagedUser;
    roles: UserRoleOption[];
    can_delete: boolean;
};

export default function UsersEdit({ user, roles, can_delete }: PageProps) {
    const { t } = useTranslation();
    const [name, setName] = useState(user.name);
    const [email, setEmail] = useState(user.email);
    const [role, setRole] = useState<ManagedUserRole>(user.role);

    return (
        <>
            <Head title={user.name} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={user.name}
                    description={t('users.edit.description')}
                />

                <Form
                    {...UserController.update.form({ user: user.id })}
                    options={{ preserveScroll: true }}
                    className="max-w-lg space-y-6"
                    resetOnSuccess={['password', 'password_confirmation']}
                >
                    {({ processing, errors }) => (
                        <>
                            <UserFormFields
                                name={name}
                                email={email}
                                role={role}
                                roles={roles}
                                passwordRequired={false}
                                onNameChange={setName}
                                onEmailChange={setEmail}
                                onRoleChange={setRole}
                                errors={errors}
                            />

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner className="size-4" />}
                                {t('common.save')}
                            </Button>
                        </>
                    )}
                </Form>

                <div className="border-t border-sidebar-border/70 pt-8 dark:border-sidebar-border">
                    <Form
                        {...UserController.destroy.form({ user: user.id })}
                    >
                        {({ processing }) => (
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p className="font-medium">
                                        {t('users.edit.delete_heading')}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {can_delete
                                            ? t('users.edit.delete_description')
                                            : t('users.edit.delete_blocked')}
                                    </p>
                                </div>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing || !can_delete}
                                >
                                    {t('common.delete')}
                                </Button>
                            </div>
                        )}
                    </Form>
                </div>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()} prefetch>
                        {t('common.back_to_overview')}
                    </Link>
                </Button>
            </div>
        </>
    );
}

UsersEdit.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.users'),
            href: index(),
        },
        {
            title: translate(props.translations, 'users.edit.breadcrumb'),
            href: edit({ user: 0 }),
        },
    ],
});
