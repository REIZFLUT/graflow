import type { Auth } from '@/types/auth';

export type AppTranslations = Record<string, unknown>;

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            locale: string;
            translations: AppTranslations;
            auth: Auth;
            can: {
                manageEditorSettingsSets: boolean;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
