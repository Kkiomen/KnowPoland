<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import CookieConsent from '@/components/CookieConsent.vue';
import { availableLocales, locale, t } from '@/i18n';
import { consentOpen } from '@/lib/consent';

/** The closing line every page ends on, with the language switcher repeated. */
const localeUrl = (code: string): string => `?lang=${code}`;

/** Both land on the page that backs the claims the home page makes. */
const method = (anchor: string): string => `/how-this-site-works#${anchor}`;

/** The donation address is shared by the server, so it is written once. */
const supportUrl = computed(
    () => (usePage().props.supportUrl as string | undefined) ?? '#',
);

/** The cookie banner exists only where Google Analytics is configured. */
const asksConsent = computed(() => usePage().props.analyticsConsent === true);
</script>

<template>
    <footer
        class="border-hairline text-bone-muted flex flex-wrap justify-between gap-5 border-t pt-5 text-sm"
    >
        <span>{{ t('footer.statement') }}</span>
        <span class="flex flex-wrap items-center gap-2">
            <template v-if="availableLocales().length > 1">
                <a
                    v-for="code in availableLocales()"
                    :key="code"
                    :href="localeUrl(code)"
                    :aria-current="code === locale() ? 'true' : undefined"
                    class="group -my-3 inline-flex min-h-[44px] items-center"
                >
                    <span
                        class="rounded-full px-2 py-0.5 text-xs tracking-[0.14em] uppercase"
                        :class="
                            code === locale()
                                ? 'text-ink bg-bone'
                                : 'group-hover:text-amber'
                        "
                    >
                        {{ code }}
                    </span>
                </a>
                <span class="text-bone-muted/40">·</span>
            </template>
            <Link
                class="hover:text-amber -my-3 inline-flex min-h-[44px] items-center"
                :href="method('sources')"
                >{{ t('footer.sources') }}</Link
            >
            ·
            <Link
                class="hover:text-amber -my-3 inline-flex min-h-[44px] items-center"
                :href="method('corrections')"
                >{{ t('footer.corrections') }}</Link
            >
            ·
            <a
                class="hover:text-amber -my-3 inline-flex min-h-[44px] items-center"
                :href="supportUrl"
                target="_blank"
                rel="noopener noreferrer"
                >{{ t('footer.support') }}</a
            >
            <template v-if="asksConsent">
                ·
                <button
                    type="button"
                    class="hover:text-amber -my-3 inline-flex min-h-[44px] cursor-pointer items-center"
                    @click="consentOpen = true"
                >
                    {{ t('footer.cookies') }}
                </button>
            </template>
        </span>
        <CookieConsent v-if="asksConsent" />
    </footer>
</template>
