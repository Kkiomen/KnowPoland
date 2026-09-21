<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The Commonwealth after the Union of Lublin: the Crown, the Grand Duchy of
 * Lithuania and the four lands the king moved from one to the other in 1569.
 *
 * The frontiers are drawn as outlines rather than blobs, because two
 * overlapping ellipses read as smudges and not as a country. Every point is
 * a real coordinate, coarsely traced, on an equirectangular extent of
 * 14.0-33.0 E and 47.5-57.5 N: x = (lon - 14) * 46, y = (57.5 - lat) * 73.8.
 * Today's Poland is drawn in dashes for orientation only.
 */
type Point = readonly [number, number];

const project = ([lon, lat]: Point): string =>
    `${((lon - 14) * 46).toFixed(1)},${((57.5 - lat) * 73.8).toFixed(1)}`;

const shape = (points: readonly Point[]): string =>
    `M${points.map(project).join(' L')} Z`;

/** The Crown of Poland, including what came over in 1569. */
const crown: readonly Point[] = [
    [14.9, 52.9],
    [15.4, 53.4],
    [16.5, 54.2],
    [17.6, 54.6],
    [18.9, 54.4],
    [19.6, 54.3],
    [19.4, 53.7],
    [20.6, 53.5],
    [21.8, 53.4],
    [23.2, 53.6],
    [24.3, 52.3],
    [26.0, 51.8],
    [28.0, 51.8],
    [30.2, 51.7],
    [31.8, 51.0],
    [32.3, 49.8],
    [31.0, 48.8],
    [29.0, 48.4],
    [27.0, 48.3],
    [25.3, 48.6],
    [23.3, 48.9],
    [21.8, 49.3],
    [20.2, 49.4],
    [19.0, 49.5],
    [18.3, 50.0],
    [18.0, 50.7],
    [17.8, 51.4],
    [16.3, 51.6],
    [15.9, 52.2],
];

/** The Grand Duchy of Lithuania as it stood after 1569. */
const lithuania: readonly Point[] = [
    [23.2, 53.6],
    [22.4, 54.4],
    [21.3, 55.4],
    [21.1, 56.0],
    [22.6, 56.4],
    [24.5, 56.3],
    [26.5, 55.8],
    [28.0, 56.1],
    [29.5, 55.6],
    [31.0, 54.8],
    [31.9, 53.6],
    [32.0, 52.4],
    [31.2, 51.6],
    [30.2, 51.7],
    [28.0, 51.8],
    [26.0, 51.8],
    [24.3, 52.3],
];

/** The four lands moved from the Grand Duchy to the Crown in 1569. */
const moved: readonly { key: string; points: readonly Point[] }[] = [
    {
        key: 'podlasie',
        points: [
            [22.2, 53.5],
            [23.15, 53.55],
            [23.55, 52.9],
            [23.0, 52.3],
            [22.3, 52.6],
            [21.9, 53.1],
        ],
    },
    {
        key: 'volhynia',
        points: [
            [24.3, 52.3],
            [26.6, 51.9],
            [27.6, 51.5],
            [27.0, 50.4],
            [25.6, 50.2],
            [24.2, 50.6],
            [23.7, 51.4],
        ],
    },
    {
        key: 'kyiv',
        points: [
            [27.6, 51.5],
            [30.2, 51.7],
            [31.8, 51.0],
            [32.3, 49.8],
            [31.0, 49.2],
            [29.4, 49.6],
            [27.8, 50.2],
            [27.0, 50.4],
        ],
    },
    {
        key: 'braclaw',
        points: [
            [27.0, 50.4],
            [29.4, 49.6],
            [29.6, 48.6],
            [28.2, 48.4],
            [27.0, 48.4],
            [26.2, 48.9],
            [26.6, 49.6],
        ],
    },
];

/** Real places: the two capitals, Lublin, Gdansk, Vilnius, Kyiv, Polotsk. */
const towns = [
    { key: 'krakow', lon: 19.94, lat: 50.06, dx: 0, dy: 34, anchor: 'middle' },
    { key: 'warsaw', lon: 21.01, lat: 52.23, dx: -16, dy: -12, anchor: 'end' },
    { key: 'lublin', lon: 22.57, lat: 51.25, dx: 16, dy: 8, anchor: 'start' },
    { key: 'gdansk', lon: 18.65, lat: 54.35, dx: -16, dy: -10, anchor: 'end' },
    {
        key: 'vilnius',
        lon: 25.28,
        lat: 54.69,
        dx: 0,
        dy: -20,
        anchor: 'middle',
    },
    { key: 'kyiv', lon: 30.52, lat: 50.45, dx: 16, dy: 8, anchor: 'start' },
    { key: 'polotsk', lon: 28.79, lat: 55.49, dx: 16, dy: -8, anchor: 'start' },
] as const;

const at = (lon: number, lat: number): { x: number; y: number } => ({
    x: (lon - 14) * 46,
    y: (57.5 - lat) * 73.8,
});

const labels = [
    { key: 'sea', lon: 17.2, lat: 56.4, anchor: 'middle', kind: 'out' },
    { key: 'crown', lon: 18.3, lat: 51.9, anchor: 'middle', kind: 'core' },
    { key: 'lithuania', lon: 28.6, lat: 54.2, anchor: 'middle', kind: 'out' },
    { key: 'moved', lon: 27.4, lat: 47.9, anchor: 'middle', kind: 'moved' },
] as const;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 0 874 738"
            class="w-full"
            role="img"
            :aria-label="t('faiths.map.alt')"
        >
            <!-- Today's Poland, for orientation only -->
            <path
                :d="polandOutline"
                transform="translate(5.52 196.3) scale(0.4614)"
                fill="none"
                stroke="currentColor"
                class="text-bone-muted"
                stroke-width="6"
                stroke-dasharray="14 12"
                stroke-linejoin="round"
                opacity="0.45"
            />

            <path
                :d="shape(lithuania)"
                class="fill-bone-muted stroke-bone-muted"
                fill-opacity="0.12"
                stroke-width="3"
                stroke-linejoin="round"
                opacity="0.9"
            />

            <path
                :d="shape(crown)"
                class="fill-amber stroke-amber"
                fill-opacity="0.2"
                stroke-width="3"
                stroke-linejoin="round"
            />

            <!-- What changed hands in 1569, inside the Crown and outlined -->
            <path
                v-for="land in moved"
                :key="land.key"
                :d="shape(land.points)"
                class="fill-amber stroke-amber"
                fill-opacity="0.22"
                stroke-width="3"
                stroke-dasharray="10 7"
                stroke-linejoin="round"
            />

            <circle
                v-for="town in towns"
                :key="town.key"
                :cx="at(town.lon, town.lat).x"
                :cy="at(town.lon, town.lat).y"
                :r="town.key === 'lublin' ? 10 : 7"
                :class="town.key === 'lublin' ? 'fill-amber' : 'fill-bone'"
            />

            <text
                v-for="town in towns"
                :key="`label-${town.key}`"
                :x="at(town.lon, town.lat).x + town.dx"
                :y="at(town.lon, town.lat).y + town.dy"
                :text-anchor="town.anchor"
                :class="town.key === 'lublin' ? 'fill-amber' : 'fill-bone'"
                class="font-display"
                font-size="26"
                letter-spacing="1"
            >
                {{ t(`faiths.map.labels.${town.key}`) }}
            </text>

            <text
                v-for="label in labels"
                :key="`area-${label.key}`"
                :x="at(label.lon, label.lat).x"
                :y="at(label.lon, label.lat).y"
                :text-anchor="label.anchor"
                :class="label.kind === 'out' ? 'fill-bone-muted' : 'fill-amber'"
                class="font-display"
                font-size="24"
                letter-spacing="1"
            >
                {{ t(`faiths.map.labels.${label.key}`) }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('faiths.map.caption') }}
        </figcaption>
    </figure>
</template>
