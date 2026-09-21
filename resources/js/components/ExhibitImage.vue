<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';

import { t } from '@/i18n';

/**
 * An exhibit shown beside the text - a seal, a manuscript page, a painting -
 * that opens full screen when clicked. The panel is too small to read a
 * charter or a coin inscription, so the picture itself is the button, and
 * the viewer is a native dialog: it traps focus, closes on Escape and gives
 * focus back to the thumbnail without any extra code.
 */
const props = withDefaults(
    defineProps<{
        src: string;
        srcset: string;
        sizes: string;
        full: string;
        alt: string;
        caption: string;
        credit: string;
        /**
         * A backdrop cannot be a thumbnail, because the text sits on top of
         * it. Frames whose picture is the backdrop - a period map, above all -
         * get a line of type under the caption instead, opening the same
         * viewer. A map nobody can read is not a map.
         */
        asLink?: boolean;
        /**
         * A map is a source, so it says on the page who made it and when,
         * without the reader having to open it first. Exhibits beside a text
         * that already describes them leave this off.
         */
        withCaption?: boolean;
        /**
         * How the thumbnail itself is sized. The default suits an exhibit in
         * a narrow panel, where the picture must not push the text off the
         * frame. A gallery wants the opposite: every photograph at its own
         * proportions, filling the column.
         */
        imageClass?: string;
    }>(),
    {
        asLink: false,
        withCaption: false,
        imageClass:
            'mx-auto max-h-[38dvh] w-full object-contain lg:max-h-[52dvh]',
    },
);

/**
 * The same pictures in AVIF, which is roughly a third smaller than the JPEG.
 *
 * Every photograph on the site is written twice to disk, once as a JPEG and
 * once as an AVIF beside it, so the address of one gives the address of the
 * other. A browser too old for the format never sees the source element and
 * takes the JPEG, which is why the img below is left exactly as it was.
 */
const asAvif = (paths: string): string => paths.replaceAll('.jpg', '.avif');

const avifSrcset = computed(() => asAvif(props.srcset));
const avifFull = computed(() => asAvif(props.full));

const dialog = ref<HTMLDialogElement | null>(null);

/**
 * The full size picture is only fetched once somebody asks for it.
 *
 * A closed dialog is still part of the document, so an `src` written into it
 * up front is downloaded with the page: every photograph arrived twice, the
 * second time in the largest file there is. On a city page that was several
 * megabytes on a phone before the reader had scrolled at all.
 */
const wanted = ref(false);

/** The filmstrip is driven by page scroll, so the page must not move under the viewer. */
function lockScroll(locked: boolean): void {
    document.documentElement.style.overflow = locked ? 'hidden' : '';
}

function open(): void {
    wanted.value = true;
    dialog.value?.showModal();
    lockScroll(true);
}

function close(): void {
    dialog.value?.close();
}

/** A click on the dark surround closes the viewer; a click on the picture does not. */
function onDialogClick(event: MouseEvent): void {
    if ((event.target as HTMLElement).closest('[data-keep-open]') === null) {
        close();
    }
}

onBeforeUnmount(() => lockScroll(false));
</script>

<template>
    <button
        v-if="props.asLink"
        type="button"
        class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber text-bone-muted mt-3 inline-flex items-center gap-2 border-b pb-1 text-[0.625rem] tracking-[0.18em] uppercase transition-colors focus-visible:outline-2 focus-visible:outline-offset-4"
        @click="open"
    >
        <svg
            viewBox="0 0 24 24"
            class="h-3.5 w-3.5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
        </svg>
        {{ t('history.enlarge') }}
    </button>

    <button
        v-else
        type="button"
        class="group focus-visible:outline-amber relative block w-full cursor-zoom-in focus-visible:outline-2 focus-visible:outline-offset-4"
        :aria-label="`${t('history.enlarge')}: ${props.caption}`"
        @click="open"
    >
        <picture>
            <source
                type="image/avif"
                :srcset="avifSrcset"
                :sizes="props.sizes"
            />
            <img
                :src="props.src"
                :srcset="props.srcset"
                :sizes="props.sizes"
                :alt="props.alt"
                :class="props.imageClass"
                loading="lazy"
                decoding="async"
            />
        </picture>
        <span
            class="border-hairline bg-ink/80 text-bone group-hover:text-amber absolute right-2 bottom-2 flex h-10 w-10 items-center justify-center border transition-colors"
            aria-hidden="true"
        >
            <svg
                viewBox="0 0 24 24"
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
            </svg>
        </span>
    </button>

    <p
        v-if="props.withCaption && !props.asLink"
        class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
    >
        {{ props.caption }} · {{ props.credit }}
    </p>

    <dialog
        ref="dialog"
        class="bg-ink/95 text-bone backdrop:bg-ink/90 m-0 h-dvh max-h-none w-screen max-w-none p-0"
        :aria-label="props.caption"
        @close="lockScroll(false)"
        @click="onDialogClick"
    >
        <div
            class="flex h-full flex-col items-center justify-center gap-4 px-4 py-16 sm:px-10"
        >
            <button
                type="button"
                class="border-hairline text-bone hover:text-amber hover:border-amber focus-visible:outline-amber absolute top-4 right-4 flex h-11 w-11 items-center justify-center border transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
                :aria-label="t('history.close')"
                @click="close"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    aria-hidden="true"
                >
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <figure
                data-keep-open
                class="flex max-h-full min-h-0 flex-col items-center gap-4"
            >
                <picture v-if="wanted" class="flex min-h-0 flex-1">
                    <source type="image/avif" :srcset="avifFull" />
                    <img
                        :src="props.full"
                        :alt="props.alt"
                        class="min-h-0 max-w-full flex-1 object-contain"
                        decoding="async"
                    />
                </picture>
                <figcaption
                    class="text-bone-muted max-w-[70ch] text-center text-[0.6875rem] tracking-[0.16em] uppercase"
                >
                    {{ props.caption }} · {{ props.credit }}
                </figcaption>
            </figure>
        </div>
    </dialog>
</template>
