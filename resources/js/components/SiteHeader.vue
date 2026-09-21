<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

import { availableLocales, locale, t } from '@/i18n';

/**
 * The masthead every page carries: the name, the tagline, the navigation and
 * the language switcher. Switching language is a plain request, so SetLocale
 * can read ?lang= and remember the choice for the session.
 */
defineProps<{
    /** Anchor links only make sense on the page that has those sections. */
    withSectionNav?: boolean;
}>();

const localeUrl = (code: string): string => `?lang=${code}`;

/**
 * At the top of a page the masthead floats over the opening image, which is how
 * the design is meant to read. Once the reader scrolls, it would sit on top of
 * body text - on a phone that leaves both unreadable - so it takes on a
 * translucent ink background and a blur as soon as the page moves at all.
 */
const isScrolled = ref(false);

const readScrollPosition = (): void => {
    isScrolled.value = window.scrollY > 8;
};

onMounted(() => {
    readScrollPosition();
    window.addEventListener('scroll', readScrollPosition, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', readScrollPosition);
});
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-40 px-6 pt-[env(safe-area-inset-top,0px)] pb-6 transition-colors duration-300"
        :class="
            isScrolled
                ? 'bg-ink/85 shadow-[0_1px_0_0_var(--color-hairline)] backdrop-blur-md'
                : 'from-ink/90 bg-linear-to-b to-transparent'
        "
    >
        <div
            class="mx-auto flex max-w-[1200px] flex-wrap items-baseline gap-x-6 gap-y-1 pt-4"
        >
            <Link
                href="/"
                class="font-display text-bone hover:text-amber -my-[13px] inline-flex min-h-[44px] items-center text-lg leading-none tracking-tight lowercase transition-colors"
            >
                {{ t('home.masthead.wordmark')
                }}<span class="text-amber">{{
                    t('home.masthead.wordmark_suffix')
                }}</span>
            </Link>
            <p class="text-bone-muted text-sm">
                {{ t('home.masthead.tagline') }}
            </p>

            <nav
                v-if="withSectionNav"
                class="text-bone-muted ml-auto hidden items-center gap-5 text-xs tracking-[0.14em] uppercase md:flex"
            >
                <a
                    class="hover:text-bone -my-[14px] inline-flex min-h-[44px] items-center"
                    href="#history"
                    >{{ t('nav.chapters') }}</a
                >
                <a
                    class="hover:text-bone -my-[14px] inline-flex min-h-[44px] items-center"
                    href="#now"
                    >{{ t('nav.today') }}</a
                >
                <a
                    class="hover:text-bone -my-[14px] inline-flex min-h-[44px] items-center"
                    href="#closing"
                    >{{ t('nav.read') }}</a
                >
            </nav>

            <nav
                v-if="availableLocales().length > 1"
                class="ml-auto flex items-center gap-1 text-xs tracking-[0.14em] uppercase md:ml-5"
                :aria-label="t('a11y.language')"
            >
                <a
                    v-for="code in availableLocales()"
                    :key="code"
                    :href="localeUrl(code)"
                    :aria-current="code === locale() ? 'true' : undefined"
                    class="group -my-[14px] inline-flex min-h-[44px] items-center focus-visible:outline-none"
                >
                    <span
                        class="group-focus-visible:outline-amber rounded-full px-2.5 py-1 transition group-focus-visible:outline-2 group-focus-visible:outline-offset-2"
                        :class="
                            code === locale()
                                ? 'text-ink bg-bone'
                                : 'text-bone-muted group-hover:text-bone'
                        "
                    >
                        {{ code }}
                    </span>
                </a>
            </nav>
        </div>
    </header>
</template>
