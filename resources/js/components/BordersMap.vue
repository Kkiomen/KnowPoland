<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The one thing this page cannot say in words alone: the whole country slid
 * west. Two outlines on one sheet, the republic of 1921 to 1939 drawn in
 * dashes and the Poland of 1945 drawn solid, with the cities that changed
 * hands marked on both sides of the move.
 *
 * A sibling of RepublicMap and War1920Map: the same equirectangular
 * projection, where x = (lon - 14) * 46 and y = (58 - lat) * 73.8, so the
 * three maps can be read against each other. The 1945 outline is today's
 * Poland, which is what that border became. Towns sit on their real
 * coordinates; the frontiers are simplified and the arrows are schematic.
 * Every label comes from the `map.labels` branch of the page's group.
 */
type Town = {
    key: string;
    lon: number;
    lat: number;
    dx: number;
    dy: number;
    anchor: 'start' | 'middle' | 'end';
    /** Lost in the east, or taken in the west. */
    side: 'east' | 'west' | 'kept';
};

type Label = {
    key: string;
    lon: number;
    lat: number;
    anchor?: 'start' | 'middle' | 'end';
    size?: number;
    /** Overrides the muted fill, for the legend entry that names the new border. */
    colour?: string;
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

const shape = (points: [number, number][]): string =>
    points
        .map(
            ([lon, lat], index) =>
                `${index === 0 ? 'M' : 'L'}${x(lon).toFixed(1)},${y(lat).toFixed(1)}`,
        )
        .join(' ') + ' Z';

const line = (points: [number, number][]): string =>
    points
        .map(
            ([lon, lat], index) =>
                `${index === 0 ? 'M' : 'L'}${x(lon).toFixed(1)},${y(lat).toFixed(1)}`,
        )
        .join(' ');

/**
 * Poland between the wars, after the Silesian division of 1922 and the Peace
 * of Riga, the same outline RepublicMap draws.
 */
const republic: [number, number][] = [
    [17.95, 54.83],
    [18.35, 54.82],
    [18.55, 54.6],
    [18.5, 54.45],
    [18.4, 54.32],
    [18.55, 54.15],
    [18.8, 54.02],
    [18.85, 53.6],
    [19.2, 53.45],
    [19.7, 53.3],
    [20.2, 53.2],
    [20.7, 53.2],
    [21.6, 53.3],
    [22.5, 53.5],
    [22.8, 54.1],
    [22.8, 54.4],
    [23.4, 54.25],
    [23.95, 54.0],
    [24.5, 54.2],
    [24.85, 54.55],
    [25.0, 54.95],
    [25.6, 55.3],
    [26.2, 55.65],
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
    [25.9, 48.55],
    [25.35, 48.45],
    [25.05, 48.2],
    [24.6, 47.95],
    [24.1, 48.25],
    [23.5, 48.75],
    [22.6, 49.1],
    [21.5, 49.4],
    [20.9, 49.3],
    [20.0, 49.2],
    [19.4, 49.5],
    [19.0, 49.45],
    [18.6, 49.72],
    [18.3, 49.95],
    [18.2, 50.05],
    [18.45, 50.3],
    [18.7, 50.45],
    [19.0, 50.55],
    [19.15, 50.55],
    [19.0, 50.75],
    [18.7, 50.95],
    [18.55, 51.2],
    [18.2, 51.35],
    [17.5, 51.35],
    [17.3, 51.6],
    [16.8, 51.65],
    [16.3, 51.8],
    [16.0, 52.1],
    [15.85, 52.3],
    [16.0, 52.6],
    [16.3, 52.8],
    [16.6, 52.95],
    [17.2, 53.05],
    [17.3, 53.25],
    [17.45, 53.5],
    [17.55, 53.9],
    [17.7, 54.1],
    [17.75, 54.4],
    [17.85, 54.6],
];

const arrows: Arrow[] = [
    {
        key: 'poles_west',
        colour: 'text-amber',
        points: [
            [25.2, 50.6],
            [22.6, 50.95],
            [20.0, 51.2],
            [17.4, 51.35],
        ],
        label: { lon: 21.4, lat: 50.15, anchor: 'middle' },
    },
    {
        key: 'germans_west',
        colour: 'text-flag-red',
        points: [
            [16.4, 52.0],
            [15.4, 51.95],
            [14.55, 51.9],
        ],
        label: { lon: 14.45, lat: 52.45, anchor: 'start' },
    },
];

const areaLabels: Label[] = [
    { key: 'sea', lon: 17.4, lat: 56.4 },
    { key: 'germany', lon: 14.35, lat: 50.4, anchor: 'start' },
    { key: 'soviet', lon: 29.8, lat: 52.6 },
    { key: 'czechoslovakia', lon: 19.8, lat: 48.5 },
    { key: 'koenigsberg', lon: 21.1, lat: 55.0, size: 18 },
    { key: 'before', lon: 29.9, lat: 55.8, anchor: 'end', size: 21 },
    {
        key: 'after',
        lon: 14.35,
        lat: 49.4,
        anchor: 'start',
        size: 21,
        colour: 'fill-amber',
    },
];

const towns: Town[] = [
    {
        key: 'warsaw',
        lon: 21.01,
        lat: 52.23,
        dx: -14,
        dy: 6,
        anchor: 'end',
        side: 'kept',
    },
    {
        key: 'wroclaw',
        lon: 17.03,
        lat: 51.11,
        dx: 0,
        dy: -16,
        anchor: 'middle',
        side: 'west',
    },
    {
        key: 'szczecin',
        lon: 14.55,
        lat: 53.43,
        dx: 12,
        dy: -10,
        anchor: 'start',
        side: 'west',
    },
    {
        key: 'gdansk',
        lon: 18.65,
        lat: 54.35,
        dx: 0,
        dy: -16,
        anchor: 'middle',
        side: 'west',
    },
    {
        key: 'olsztyn',
        lon: 20.49,
        lat: 53.78,
        dx: 12,
        dy: 22,
        anchor: 'start',
        side: 'west',
    },
    {
        key: 'vilnius',
        lon: 25.28,
        lat: 54.69,
        dx: 14,
        dy: 8,
        anchor: 'start',
        side: 'east',
    },
    {
        key: 'grodno',
        lon: 23.83,
        lat: 53.68,
        dx: 14,
        dy: 8,
        anchor: 'start',
        side: 'east',
    },
    {
        key: 'lwow',
        lon: 24.03,
        lat: 49.84,
        dx: 14,
        dy: 8,
        anchor: 'start',
        side: 'east',
    },
];

const republicPath = shape(republic);
const arrowPaths = arrows.map((arrow) => ({ ...arrow, d: line(arrow.points) }));
const headId = (key: string): string => `${props.group}-head-${key}`;
const frameId = `${props.group}-map-frame`;

const dotClass: Record<Town['side'], string> = {
    east: 'fill-bone-muted',
    west: 'fill-amber',
    kept: 'fill-bone',
};
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="10 0 890 800"
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
                <clipPath :id="frameId">
                    <rect x="10" y="0" width="890" height="800" />
                </clipPath>
            </defs>

            <g :clip-path="`url(#${frameId})`">
                <!-- Poland after 1945, which is Poland today -->
                <path
                    :d="polandOutline"
                    transform="translate(5.52 233.2) scale(0.4614)"
                    class="fill-amber"
                    fill-opacity="0.16"
                />

                <!-- The republic of 1921 to 1939 -->
                <path
                    :d="republicPath"
                    fill="none"
                    stroke="currentColor"
                    class="text-bone-muted"
                    stroke-width="6"
                    stroke-dasharray="18 14"
                    stroke-linejoin="round"
                    opacity="0.8"
                />

                <path
                    :d="polandOutline"
                    transform="translate(5.52 233.2) scale(0.4614)"
                    fill="none"
                    stroke="currentColor"
                    class="text-amber"
                    stroke-width="8"
                    stroke-linejoin="round"
                />

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
            </g>

            <text
                v-for="label in areaLabels"
                :key="`area-${label.key}`"
                :x="x(label.lon)"
                :y="y(label.lat)"
                :text-anchor="label.anchor ?? 'middle'"
                class="font-display stroke-ink"
                :class="label.colour ?? 'fill-bone-muted'"
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
                    :class="dotClass[town.side]"
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
