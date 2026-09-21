import { usePage } from '@inertiajs/vue3';
import type { App, Plugin } from 'vue';

type TranslationValue = string | number | TranslationTree | TranslationValue[];

interface TranslationTree {
    [key: string]: TranslationValue;
}

/**
 * Translations are shared with every Inertia response by HandleInertiaRequests
 * and come from lang/<locale>/site.php. Components never hold copy of their
 * own - they only ever reference keys, so a new language is one PHP file.
 */
function translations(): TranslationTree {
    return (usePage().props.translations as TranslationTree | undefined) ?? {};
}

function lookup(key: string): TranslationValue | undefined {
    return key
        .split('.')
        .reduce<TranslationValue | undefined>((value, segment) => {
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                return (value as TranslationTree)[segment];
            }

            return undefined;
        }, translations());
}

/**
 * Translate a key, replacing :placeholders with the given values.
 * Returns the key itself when a translation is missing, so gaps are visible.
 */
export function t(
    key: string,
    replacements: Record<string, string | number> = {},
): string {
    const value = lookup(key);

    if (typeof value !== 'string' && typeof value !== 'number') {
        return key;
    }

    return Object.entries(replacements).reduce<string>(
        (line, [placeholder, replacement]) =>
            line.replaceAll(`:${placeholder}`, String(replacement)),
        String(value),
    );
}

/**
 * Read a whole translation branch - used for lists the copy owns, such as the
 * chapters on the home page.
 */
export function group<T = TranslationTree>(key: string): T | undefined {
    return lookup(key) as T | undefined;
}

/** The locale currently being rendered, e.g. "en". */
export function locale(): string {
    return (usePage().props.locale as string | undefined) ?? 'en';
}

/** Every locale that has a translation directory on the server. */
export function availableLocales(): string[] {
    return (
        (usePage().props.availableLocales as string[] | undefined) ?? [locale()]
    );
}

export function useTranslations() {
    return { t, group, locale, availableLocales };
}

export const i18n: Plugin = {
    install(app: App): void {
        app.config.globalProperties.$t = t;
        app.config.globalProperties.$tGroup = group;
    },
};
