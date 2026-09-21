<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The relief march of 1683, from Krakow through Tarnowskie Gory, Silesia and
 * Moravia to Tulln on the Danube and over the Kahlenberg to Vienna. Drawn on
 * an equirectangular extent of 13.8-23.8 E and 47.7-53.2 N, so every dot sits
 * on a real place: x = (lon - 13.8) * 90, y = (53.2 - lat) * 144.4. The line
 * of march only passes through real waypoints (Gliwice, Olomouc, Hollabrunn);
 * between them it is simplified. Today's Poland is drawn in for orientation.
 */
const route =
    'M553,453 L455,398 L438,420 L310,521 L205,670 L203,703 L228,711 L232,721';

/** Real places, with their labels set off where the dots crowd together. */
const towns = [
    { key: 'warsaw', x: 649, y: 140, lx: 667, ly: 149, anchor: 'start' },
    { key: 'krakow', x: 553, y: 453, lx: 571, ly: 462, anchor: 'start' },
    { key: 'tarnowskie', x: 455, y: 398, lx: 455, ly: 372, anchor: 'middle' },
    { key: 'tulln', x: 203, y: 703, lx: 186, ly: 700, anchor: 'end' },
    { key: 'kahlenberg', x: 228, y: 711, lx: 262, ly: 690, anchor: 'start' },
    { key: 'vienna', x: 232, y: 721, lx: 262, ly: 760, anchor: 'start' },
] as const;

const lead = (key: string): boolean => key === 'kahlenberg' || key === 'vienna';
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 0 900 794"
            class="w-full"
            role="img"
            :aria-label="t('vienna.map.alt')"
        >
            <defs>
                <clipPath id="vienna-frame">
                    <rect x="0" y="0" width="900" height="794" />
                </clipPath>
                <marker
                    id="vienna-arrow"
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

            <g clip-path="url(#vienna-frame)">
                <path
                    :d="polandOutline"
                    transform="translate(28.8 -236.8) scale(0.9027)"
                    fill="currentColor"
                    fill-opacity="0.06"
                    stroke="currentColor"
                    class="text-bone-muted"
                    stroke-width="5"
                    stroke-dasharray="14 10"
                    stroke-linejoin="round"
                    opacity="0.7"
                />
            </g>

            <!-- The line of march -->
            <path
                :d="route"
                fill="none"
                class="stroke-amber"
                stroke-width="5"
                stroke-linejoin="round"
                stroke-dasharray="16 9"
                marker-end="url(#vienna-arrow)"
            />

            <!-- Leader lines for the two places that sit almost on top of each other -->
            <line
                x1="232"
                y1="706"
                x2="256"
                y2="683"
                class="stroke-amber"
                stroke-width="2"
            />
            <line
                x1="238"
                y1="726"
                x2="256"
                y2="748"
                class="stroke-bone"
                stroke-width="2"
            />

            <circle
                v-for="town in towns"
                :key="town.key"
                :cx="town.x"
                :cy="town.y"
                :r="town.key === 'vienna' ? 9 : 7"
                :class="lead(town.key) ? 'fill-amber' : 'fill-bone'"
            />

            <text
                v-for="town in towns"
                :key="`label-${town.key}`"
                :x="town.lx"
                :y="town.ly"
                :text-anchor="town.anchor"
                :class="lead(town.key) ? 'fill-amber' : 'fill-bone'"
                class="font-display"
                font-size="28"
                letter-spacing="1"
            >
                {{ t(`vienna.map.labels.${town.key}`) }}
            </text>

            <text
                x="330"
                y="615"
                class="fill-amber font-display"
                font-size="28"
                letter-spacing="2"
            >
                {{ t('vienna.map.labels.route') }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('vienna.map.caption') }}
        </figcaption>
    </figure>
</template>
