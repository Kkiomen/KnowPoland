<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The campaign of 1920 on one sheet: the Polish thrust to Kyiv in the
 * spring, the Red Army rolling west on Warsaw and south-west on Lviv in the
 * summer, the counter-attack from the river Wieprz on 16 August, and the
 * eastern border agreed at Riga in March 1921.
 *
 * A sibling of RepublicMap: the same equirectangular projection, where
 * x = (lon - 14) * 46 and y = (58 - lat) * 73.8, so today's Poland, drawn in
 * dashes, lies over the same ground. Towns sit on their real coordinates;
 * the arrows are schematic, not traced from the operational maps. Every
 * label comes from the `map.labels` branch of the page's translation group.
 */
type Town = {
    key: string;
    lon: number;
    lat: number;
    dx: number;
    dy: number;
    anchor: 'start' | 'middle' | 'end';
};

type Label = {
    key: string;
    lon: number;
    lat: number;
    anchor?: 'start' | 'middle' | 'end';
    size?: number;
};

type Arrow = {
    key: string;
    colour: string;
    points: [number, number][];
    label: { lon: number; lat: number; anchor?: 'start' | 'middle' | 'end' };
};

const props = defineProps<{
    /** Translation group holding `map.alt`, `map.caption` and `map.labels`. */
    group: string;
}>();

const x = (lon: number): number => (lon - 14) * 46;
const y = (lat: number): number => (58 - lat) * 73.8;

const line = (points: [number, number][]): string =>
    points
        .map(
            ([lon, lat], index) =>
                `${index === 0 ? 'M' : 'L'}${x(lon).toFixed(1)},${y(lat).toFixed(1)}`,
        )
        .join(' ');

/**
 * The eastern border as the Peace of Riga drew it, from the Latvian corner
 * down to the Dniester, simplified to the points the other maps use.
 */
const rigaBorder: [number, number][] = [
    [26.7, 55.75],
    [27.3, 55.85],
    [28.2, 55.6],
    [27.8, 55.1],
    [27.3, 54.6],
    [27.15, 54.0],
    [26.95, 53.4],
    [27.15, 52.8],
    [27.3, 52.2],
    [27.4, 51.6],
    [27.2, 51.0],
    [27.3, 50.6],
    [26.9, 50.2],
    [26.3, 49.8],
    [26.15, 49.2],
    [26.35, 48.55],
];

const arrows: Arrow[] = [
    {
        key: 'kyiv_thrust',
        colour: 'text-amber',
        points: [
            [25.6, 50.6],
            [27.4, 50.3],
            [29.3, 50.3],
            [30.2, 50.45],
        ],
        label: { lon: 28.6, lat: 51.2, anchor: 'middle' },
    },
    {
        key: 'soviet_advance',
        colour: 'text-flag-red',
        points: [
            [29.2, 54.5],
            [27.6, 54.2],
            [25.6, 53.7],
            [23.6, 53.1],
            [22.0, 52.6],
            [21.4, 52.4],
        ],
        label: { lon: 25.4, lat: 54.95, anchor: 'middle' },
    },
    {
        key: 'budyonny',
        colour: 'text-flag-red',
        points: [
            [28.6, 49.6],
            [27.0, 49.8],
            [25.6, 49.9],
            [24.5, 49.87],
        ],
        label: { lon: 27.2, lat: 48.95 },
    },
    {
        key: 'counter',
        colour: 'text-bone',
        points: [
            [21.9, 51.4],
            [22.5, 51.9],
            [23.1, 52.5],
            [23.4, 52.9],
        ],
        label: { lon: 18.1, lat: 50.55, anchor: 'start' },
    },
];

const areaLabels: Label[] = [
    { key: 'sea', lon: 17.6, lat: 56.3 },
    { key: 'germany', lon: 14.9, lat: 51.4, anchor: 'start' },
    { key: 'east_prussia', lon: 21.2, lat: 54.75, size: 20 },
    { key: 'lithuania', lon: 23.4, lat: 55.6 },
    { key: 'soviet', lon: 30.2, lat: 52.6 },
    { key: 'czechoslovakia', lon: 19.2, lat: 48.75 },
    { key: 'romania', lon: 27.6, lat: 47.6 },
];

const towns: Town[] = [
    { key: 'warsaw', lon: 21.01, lat: 52.23, dx: -14, dy: 6, anchor: 'end' },
    {
        key: 'radzymin',
        lon: 21.19,
        lat: 52.42,
        dx: 12,
        dy: -6,
        anchor: 'start',
    },
    { key: 'ossow', lon: 21.24, lat: 52.31, dx: 14, dy: 22, anchor: 'start' },
    { key: 'lublin', lon: 22.57, lat: 51.25, dx: -14, dy: 8, anchor: 'end' },
    { key: 'brest', lon: 23.7, lat: 52.1, dx: 14, dy: 8, anchor: 'start' },
    { key: 'grodno', lon: 23.83, lat: 53.68, dx: 0, dy: -16, anchor: 'middle' },
    { key: 'vilnius', lon: 25.28, lat: 54.69, dx: 14, dy: 8, anchor: 'start' },
    { key: 'minsk', lon: 27.57, lat: 53.9, dx: 14, dy: 8, anchor: 'start' },
    { key: 'lwow', lon: 24.03, lat: 49.84, dx: -14, dy: 22, anchor: 'end' },
    { key: 'kyiv', lon: 30.52, lat: 50.45, dx: -14, dy: 24, anchor: 'end' },
];

const borderPath = line(rigaBorder);
const arrowPaths = arrows.map((arrow) => ({ ...arrow, d: line(arrow.points) }));
const headId = (key: string): string => `${props.group}-head-${key}`;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="30 0 870 800"
            class="w-full"
            role="img"
            :aria-label="t(`${group}.map.alt`)"
        >
            <defs>
                <marker
                    v-for="arrow in arrowPaths"
                    :id="headId(arrow.key)"
                    :key="`head-${arrow.key}`"
                    viewBox="0 0 10 10"
                    refX="8"
                    refY="5"
                    markerWidth="5"
                    markerHeight="5"
                    orient="auto-start-reverse"
                >
                    <path
                        d="M0,0 L10,5 L0,10 z"
                        fill="currentColor"
                        :class="arrow.colour"
                    />
                </marker>
            </defs>

            <path
                :d="polandOutline"
                transform="translate(5.52 233.2) scale(0.4614)"
                fill="none"
                stroke="currentColor"
                class="text-bone-muted"
                stroke-width="7"
                stroke-dasharray="18 14"
                stroke-linejoin="round"
                opacity="0.55"
            />

            <path
                :d="borderPath"
                fill="none"
                stroke="currentColor"
                class="text-amber"
                stroke-width="5"
                stroke-linejoin="round"
                opacity="0.9"
            />

            <text
                :x="x(25.4)"
                :y="y(56.3)"
                text-anchor="middle"
                class="fill-amber font-display stroke-ink"
                font-size="20"
                letter-spacing="1.5"
                paint-order="stroke"
                stroke-width="6"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.border`) }}
            </text>

            <g v-for="arrow in arrowPaths" :key="arrow.key">
                <path
                    :d="arrow.d"
                    fill="none"
                    stroke="currentColor"
                    :class="arrow.colour"
                    stroke-width="9"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    :marker-end="`url(#${headId(arrow.key)})`"
                />
                <text
                    :x="x(arrow.label.lon)"
                    :y="y(arrow.label.lat)"
                    :text-anchor="arrow.label.anchor ?? 'start'"
                    :class="[arrow.colour, 'font-display stroke-ink']"
                    fill="currentColor"
                    font-size="20"
                    letter-spacing="1.5"
                    paint-order="stroke"
                    stroke-width="6"
                    stroke-linejoin="round"
                >
                    {{ t(`${group}.map.labels.${arrow.key}`) }}
                </text>
            </g>

            <text
                v-for="label in areaLabels"
                :key="`area-${label.key}`"
                :x="x(label.lon)"
                :y="y(label.lat)"
                :text-anchor="label.anchor ?? 'middle'"
                class="fill-bone-muted font-display stroke-ink"
                :font-size="label.size ?? 24"
                letter-spacing="2"
                paint-order="stroke"
                stroke-width="5"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.${label.key}`) }}
            </text>

            <g v-for="town in towns" :key="town.key">
                <circle
                    :cx="x(town.lon)"
                    :cy="y(town.lat)"
                    r="7"
                    class="fill-bone"
                />
                <text
                    :x="x(town.lon) + town.dx"
                    :y="y(town.lat) + town.dy"
                    :text-anchor="town.anchor"
                    class="fill-bone font-display stroke-ink"
                    font-size="22"
                    letter-spacing="1"
                    paint-order="stroke"
                    stroke-width="5"
                    stroke-linejoin="round"
                >
                    {{ t(`${group}.map.labels.${town.key}`) }}
                </text>
            </g>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t(`${group}.map.caption`) }}
        </figcaption>
    </figure>
</template>
