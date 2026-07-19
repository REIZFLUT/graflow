import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import UserFormFields from '@/components/users/user-form-fields';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { create, index } from '@/routes/users';
import type { ManagedUserRole, UserRoleOption } from '@/types';

type PageProps = {
    roles: UserRoleOption[];
};

export default function UsersCreate({ roles }: PageProps) {
    const { t } = useTranslation();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [role, setRole] = useState<ManagedUserRole>(
        roles[0]?.value ?? 'author',
    );

    return (
        <>
            <Head title={t('users.create.head_title')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={t('users.create.title')}
                    description={t('users.create.description')}
                />

                <Form
                    {...UserController.store.form()}
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
                                onNameChange={setName}
                                onEmailChange={setEmail}
                                onRoleChange={setRole}
                                errors={errors}
                            />

                            <div className="flex flex-wrap gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t('users.create.submit')}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index()} prefetch>
                                        {t('common.cancel')}
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

UsersCreate.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.users'),
            href: index(),
        },
        {
            title: translate(props.translations, 'common.new'),
            href: create(),
        },
    ],
});
