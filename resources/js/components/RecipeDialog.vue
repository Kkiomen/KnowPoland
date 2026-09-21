<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';

import { t } from '@/i18n';

/**
 * The recipe behind a dish.
 *
 * A visitor who has just seen a photograph of bigos wants to know how it is
 * made, so the photograph is the way in and this panel is what it opens. It is
 * a native dialog, which traps focus, closes on Escape and hands focus back to
 * the tile that opened it without any code of ours.
 *
 * One dialog serves all seventeen dishes: the page passes whichever dish is
 * open, so there are not seventeen copies of this markup in the document.
 */
export type Recipe = {
    time: string;
    serves: string;
    ingredients: string[];
    steps: string[];
};

export type RecipeDish = {
    photo: string;
    name: string;
    kind: string;
    said: string;
    note: string;
    credit: string;
    recipe: Recipe;
};

const props = defineProps<{
    /** The dish to show, or null when nothing is open. */
    dish: RecipeDish | null;
    /** Accent colour for this dish, so the panel matches the tile. */
    accent: string;
}>();

const emit = defineEmits<{ (event: 'close'): void }>();

const dialog = ref<HTMLDialogElement | null>(null);

/** The page must not scroll under the panel. */
function lockScroll(locked: boolean): void {
    document.documentElement.style.overflow = locked ? 'hidden' : '';
}

/**
 * Closing always goes the same way: tell the page, let the page clear its
 * selection, and let the watcher below shut the dialog. Escape is caught on
 * the way in rather than through the dialog's own close event, which some
 * browsers do not fire when the element is closed from script.
 */
function requestClose(): void {
    emit('close');
}

watch(
    () => props.dish,
    (dish) => {
        if (dish) {
            dialog.value?.showModal();
            dialog.value?.scrollTo(0, 0);
            lockScroll(true);
            return;
        }

        if (dialog.value?.open) {
            dialog.value.close();
        }
        lockScroll(false);
    },
);

/** A click on the backdrop rather than on the panel closes it. */
function onClick(event: MouseEvent): void {
    if (event.target === dialog.value) {
        requestClose();
    }
}

onBeforeUnmount(() => lockScroll(false));
</script>

<template>
    <dialog
        ref="dialog"
        class="m-auto w-[min(56rem,92vw)] max-w-none rounded-xl bg-[#fbf7f0] p-0 text-[#221e19] backdrop:bg-black/80 open:max-h-[88dvh]"
        :aria-label="dish?.name"
        @click="onClick"
        @keydown.esc.prevent="requestClose"
    >
        <div v-if="dish">
            <div class="relative">
                <picture>
                    <source
                        type="image/avif"
                        :srcset="`/images/food-${dish.photo}-lg.avif`"
                    />
                    <img
                        :src="`/images/food-${dish.photo}-lg.jpg`"
                        :alt="dish.name"
                        class="block max-h-[38dvh] w-full object-cover"
                    />
                </picture>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 bg-[linear-gradient(0deg,rgba(0,0,0,.88)_0%,rgba(0,0,0,.45)_55%,rgba(0,0,0,0)_100%)] p-5 pt-16"
                >
                    <p
                        class="text-[0.625rem] font-semibold tracking-[0.2em] text-[#ffd08a] uppercase"
                    >
                        {{ dish.kind }}
                    </p>
                    <h2
                        class="font-display mt-1.5 text-[clamp(1.3rem,3.2vw,2rem)] leading-tight tracking-wide text-white uppercase"
                    >
                        {{ dish.name }}
                    </h2>
                    <p
                        class="mt-1 text-[0.6875rem] tracking-[0.14em] text-white/60"
                    >
                        {{ dish.said }}
                    </p>
                </div>

                <button
                    type="button"
                    class="focus-visible:outline-amber absolute top-3 right-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-black/55 text-lg text-white transition hover:bg-black/80 focus-visible:outline-2 focus-visible:outline-offset-2"
                    :aria-label="t('food.recipe_ui.close')"
                    @click="requestClose"
                >
                    &times;
                </button>
            </div>

            <div class="p-5 sm:p-7">
                <p class="max-w-[68ch] leading-relaxed text-[#4a443c]">
                    {{ dish.note }}
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span
                        class="rounded-full px-3 py-1 text-[0.6875rem] font-semibold tracking-[0.12em] text-white uppercase"
                        :style="{ backgroundColor: accent }"
                    >
                        {{ t('food.recipe_ui.time') }}:
                        {{ dish.recipe.time }}
                    </span>
                    <span
                        class="rounded-full px-3 py-1 text-[0.6875rem] font-semibold tracking-[0.12em] text-white uppercase"
                        :style="{ backgroundColor: accent }"
                    >
                        {{ t('food.recipe_ui.serves') }}:
                        {{ dish.recipe.serves }}
                    </span>
                </div>

                <div
                    class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)] lg:gap-12"
                >
                    <div>
                        <h3
                            class="font-display text-sm tracking-[0.06em] uppercase"
                            :style="{ color: accent }"
                        >
                            {{ t('food.recipe_ui.ingredients') }}
                        </h3>
                        <ul class="mt-3 grid gap-0">
                            <li
                                v-for="item in dish.recipe.ingredients"
                                :key="item"
                                class="border-b border-[#e3d8c6] py-2 text-[0.9375rem] leading-snug text-[#4a443c] last:border-b-0"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3
                            class="font-display text-sm tracking-[0.06em] uppercase"
                            :style="{ color: accent }"
                        >
                            {{ t('food.recipe_ui.steps') }}
                        </h3>
                        <ol class="mt-3 grid gap-4">
                            <li
                                v-for="(step, index) in dish.recipe.steps"
                                :key="step"
                                class="grid grid-cols-[2rem_minmax(0,1fr)] gap-3"
                            >
                                <span
                                    class="font-display mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-[0.6875rem] text-white tabular-nums"
                                    :style="{ backgroundColor: accent }"
                                    aria-hidden="true"
                                >
                                    {{ index + 1 }}
                                </span>
                                <p
                                    class="max-w-[62ch] leading-relaxed text-[#3b352d]"
                                >
                                    {{ step }}
                                </p>
                            </li>
                        </ol>
                    </div>
                </div>

                <p class="mt-8 text-[0.75rem] text-[#6e6459]">
                    {{ dish.credit }}
                </p>
            </div>
        </div>
    </dialog>
</template>
