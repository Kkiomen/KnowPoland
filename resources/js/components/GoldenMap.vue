<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The grain route of the 16th century: down the Vistula from Krakow to
 * Gdansk, and from Gdansk by sea to Amsterdam. Drawn on the same projection
 * as the Poland outline, x = (lon - 14.12) * 99.7, y = (54.84 - lat) * 160,
 * so the river and every town sit in their real places. The viewBox starts
 * above zero to leave the Baltic room for the sea route.
 */
const river =
    'M580,765 L611,762 L661,738 L693,726 L731,694 L761,666 L772,632 L781,563 L771,525 L741,486 L707,458 L689,414 L656,386 L606,382 L556,366 L494,349 L462,315 L447,293 L430,238 L462,218 L470,174 L466,120 L452,78';

/** Real places on the river. */
const towns = [
    { key: 'krakow', x: 580, y: 765, lx: 564, ly: 800, anchor: 'end' },
    { key: 'sandomierz', x: 761, y: 666, lx: 781, ly: 676, anchor: 'start' },
    { key: 'kazimierz', x: 781, y: 563, lx: 762, ly: 588, anchor: 'end' },
    { key: 'warsaw', x: 689, y: 414, lx: 709, ly: 424, anchor: 'start' },
    { key: 'wloclawek', x: 494, y: 349, lx: 478, ly: 380, anchor: 'end' },
    { key: 'torun', x: 447, y: 293, lx: 431, ly: 303, anchor: 'end' },
    { key: 'gdansk', x: 452, y: 78, lx: 474, ly: 88, anchor: 'start' },
] as const;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 -70 1000 1005"
            class="w-full"
            role="img"
            :aria-label="t('golden.map.alt')"
        >
            <defs>
                <marker
                    id="golden-arrow"
                    viewBox="0 0 10 10"
                    refX="8"
                    refY="5"
                    markerWidth="6"
                    markerHeight="6"
                    orient="auto-start-reverse"
                >
                    <path d="M0,0 L10,5 L0,10 z" fill="context-stroke" />
                </marker>
            </defs>

            <path
                :d="polandOutline"
                fill="none"
                stroke="currentColor"
                class="text-bone-muted"
                stroke-width="4"
                stroke-dasharray="10 8"
                stroke-linejoin="round"
                opacity="0.6"
            />

            <text
                x="760"
                y="-18"
                text-anchor="middle"
                class="fill-bone-muted font-display"
                font-size="30"
                letter-spacing="2"
            >
                {{ t('golden.map.labels.sea') }}
            </text>

            <!-- Boats and rafts, downstream to Gdansk -->
            <path
                :d="river"
                fill="none"
                class="stroke-amber"
                stroke-width="7"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path
                d="M500,250 L506,160"
                fill="none"
                class="stroke-amber"
                stroke-width="4"
                marker-end="url(#golden-arrow)"
            />
            <text
                x="650"
                y="790"
                text-anchor="start"
                class="fill-amber font-display"
                font-size="28"
                letter-spacing="2"
            >
                {{ t('golden.map.labels.river') }}
            </text>

            <!-- On by sea, simplified -->
            <path
                d="M440,64 Q260,-40 40,-10"
                fill="none"
                class="stroke-bone-muted"
                stroke-width="4"
                stroke-dasharray="12 8"
                marker-end="url(#golden-arrow)"
            />
            <text
                x="40"
                y="-36"
                class="fill-bone-muted font-display"
                font-size="26"
                letter-spacing="1"
            >
                {{ t('golden.map.labels.amsterdam') }}
            </text>

            <circle
                v-for="town in towns"
                :key="town.key"
                :cx="town.x"
                :cy="town.y"
                :r="town.key === 'gdansk' ? 11 : 7"
                :class="town.key === 'gdansk' ? 'fill-amber' : 'fill-bone'"
            />

            <text
                v-for="town in towns"
                :key="`label-${town.key}`"
                :x="town.lx"
                :y="town.ly"
                :text-anchor="town.anchor"
                :class="town.key === 'gdansk' ? 'fill-amber' : 'fill-bone'"
                class="font-display"
                font-size="28"
                letter-spacing="1"
            >
                {{ t(`golden.map.labels.${town.key}`) }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('golden.map.caption') }}
        </figcaption>
    </figure>
</template>
