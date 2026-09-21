<script setup lang="ts">
import { computed } from 'vue';

import { group, t } from '@/i18n';

/**
 * The address list.
 *
 * A reader who has just been told to install jakdojade or to buy the ticket
 * from the operator should not then have to work out which of five search
 * results is the real one, so every app and office the page names is repeated
 * here with its address. The copy lives in the shared `useful` group, because
 * more than one page ends with this block and the list is maintained once.
 */
type Link = { k: string; v: string; web: string };
type Group = { title: string; items: Link[] };

/** The page decides the shade, so the block never repeats the one above it. */
const props = withDefaults(defineProps<{ tone?: 'cream' | 'plain' }>(), {
    tone: 'cream',
});

const surface = computed(() =>
    props.tone === 'cream' ? 'bg-[#f3eadb]' : 'bg-[#fbf7f0]',
);

const text = (path: string): string => t(`useful.${path}`);

const groups = computed<Group[]>(() => group<Group[]>('useful.groups') ?? []);

/** Beetroot, crust, dill, honey, wine, cycled so a block keeps its colour. */
const CHIPS = ['#b32347', '#a8520f', '#457036', '#8a5a10', '#7c1d32'] as const;
const chip = (index: number): string => CHIPS[index % CHIPS.length];
</script>

<template>
    <section :class="surface">
        <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
            <p
                class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
            >
                {{ text('label') }}
            </p>
            <h2
                class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
            >
                {{ text('title') }}
            </h2>
            <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                {{ text('note') }}
            </p>

            <div
                v-for="(block, blockIndex) in groups"
                :key="block.title"
                class="mt-10"
            >
                <h3
                    class="font-display text-sm tracking-[0.18em] uppercase"
                    :style="{ color: chip(blockIndex) }"
                >
                    {{ block.title }}
                </h3>

                <dl class="mt-4 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="item in block.items"
                        :key="item.web"
                        class="border-t border-[#e0d3bd] pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ item.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ item.v }}
                        </dd>
                        <dd class="m-0">
                            <a
                                :href="`https://${item.web}`"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex min-h-[44px] items-center text-[0.8125rem] break-all text-[#b32347] transition hover:text-[#221e19] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#b32347]"
                            >
                                {{ item.web }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
