<script setup lang="ts">
import { computed } from 'vue';

import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The Commonwealth before 1772 and the shares the three powers cut from it.
 * Built for all the partition pages: `highlight` picks the partition(s) a
 * page is about, shares from earlier partitions are drawn as already lost,
 * and every label comes from the `map.labels` branch of the page's own
 * translation group.
 *
 * Drawn on an equirectangular extent where x = (lon - 14) * 46 and
 * y = (58 - lat) * 73.8, the same as the other maps, so today's Poland can be
 * laid over it for orientation. Frontiers are simplified: the towns sit on
 * their real coordinates, the borders only roughly follow the rivers and
 * mountains they ran along.
 */
type Power = 'russia' | 'prussia' | 'austria';

type Share = {
    partition: number;
    power: Power;
    points: [number, number][];
    /** Where the share's name goes; an enclave such as Gdansk has none. */
    label?: [number, number];
};

type Town = {
    key: string;
    lon: number;
    lat: number;
    dx: number;
    dy: number;
    anchor: 'start' | 'middle' | 'end';
    /** The first partition page that needs the town on its map. */
    from?: number;
};

/**
 * A battlefield rather than a town. Marked only when a page asks for it by
 * `id`, and labelled from that page's `map.labels`, so the maps that do not
 * ask for sites need no labels for them.
 */
type Site = {
    id: string;
    lon: number;
    lat: number;
    dx: number;
    dy: number;
    anchor: 'start' | 'middle' | 'end';
};

const props = withDefaults(
    defineProps<{
        /** Translation group holding `map.alt`, `map.caption` and `map.labels`. */
        group: string;
        /** Which partitions to colour: 1 for 1772, 2 for 1793, 3 for 1795. */
        highlight?: number[];
        /** Battlefields to mark, by id, from the list below. */
        sites?: string[];
        /**
         * Draw the map of 1815 instead of the partition shares: the Kingdom
         * of Poland under the tsar, the Grand Duchy of Posen, the Free City of
         * Krakow and Galicia, with the rest of the old lands in Russia.
         */
        settlement?: boolean;
        /**
         * Draw the three partitions as they stood around 1900 instead: the
         * map of 1815 with Krakow folded into Galicia and West Prussia added
         * to the Prussian share, the border of the Russian partition traced
         * in amber and the Three Emperors' Corner marked.
         */
        empires?: boolean;
    }>(),
    {
        highlight: () => [1],
        sites: () => [],
        settlement: false,
        empires: false,
    },
);

const x = (lon: number): number => (lon - 14) * 46;
const y = (lat: number): number => (58 - lat) * 73.8;

const path = (points: [number, number][]): string =>
    points
        .map(
            ([lon, lat], index) =>
                `${index === 0 ? 'M' : 'L'}${x(lon).toFixed(1)},${y(lat).toFixed(1)}`,
        )
        .join(' ') + ' Z';

/** The Commonwealth as it stood in 1771, without Courland, a vassal duchy. */
const commonwealth: [number, number][] = [
    [17.6, 54.75],
    [18.35, 54.8],
    [18.8, 54.6],
    [18.65, 54.4],
    [19.3, 54.35],
    [19.7, 54.4],
    [20.2, 54.3],
    [20.9, 54.2],
    [21.0, 53.85],
    [20.4, 53.6],
    [19.9, 53.45],
    [20.6, 53.25],
    [21.6, 53.3],
    [22.5, 53.5],
    [22.8, 54.1],
    [22.8, 54.4],
    [22.9, 54.85],
    [22.2, 55.05],
    [21.4, 55.2],
    [21.2, 55.6],
    [21.05, 55.95],
    [21.1, 56.1],
    [22.2, 56.4],
    [23.5, 56.35],
    [24.6, 56.3],
    [25.6, 56.1],
    [26.5, 55.88],
    [26.0, 56.6],
    [26.9, 57.1],
    [27.7, 57.25],
    [28.1, 56.9],
    [28.3, 56.4],
    [29.0, 56.0],
    [30.3, 56.1],
    [31.2, 55.8],
    [31.5, 55.3],
    [31.3, 54.8],
    [31.9, 54.3],
    [32.5, 53.8],
    [32.3, 53.2],
    [31.8, 52.6],
    [31.3, 52.1],
    [30.8, 51.9],
    [30.5, 51.3],
    [30.4, 50.6],
    [31.0, 50.1],
    [32.0, 49.3],
    [31.0, 48.4],
    [29.8, 47.95],
    [29.2, 47.95],
    [28.2, 48.2],
    [27.4, 48.4],
    [26.25, 48.6],
    [25.9, 48.4],
    [25.3, 48.2],
    [24.9, 47.95],
    [24.4, 48.0],
    [23.5, 48.6],
    [22.6, 49.1],
    [21.5, 49.4],
    [20.9, 49.3],
    [20.0, 49.2],
    [19.4, 49.5],
    [19.1, 49.6],
    [18.9, 49.9],
    [19.1, 50.05],
    [19.0, 50.6],
    [18.7, 50.9],
    [18.3, 51.2],
    [17.8, 51.4],
    [17.3, 51.6],
    [16.8, 51.7],
    [16.3, 51.85],
    [15.7, 52.2],
    [15.55, 52.5],
    [15.8, 52.9],
    [16.4, 53.2],
    [16.7, 53.5],
    [17.0, 53.8],
    [17.3, 54.1],
    [17.2, 54.4],
];

/**
 * 1772: Russia beyond the Dvina, Drut and Dnieper with Polish Livonia;
 * Prussia with Royal Prussia, Warmia and the Notec district, but not Gdansk
 * or Torun; Austria with Galicia, south of the Vistula and east to the Zbruch.
 */
const shares: Share[] = [
    {
        partition: 1,
        power: 'russia',
        label: [29.4, 57.5],
        points: [
            [26.5, 55.88],
            [26.0, 56.6],
            [26.9, 57.1],
            [27.7, 57.25],
            [28.1, 56.9],
            [28.3, 56.4],
            [29.0, 56.0],
            [30.3, 56.1],
            [31.2, 55.8],
            [31.5, 55.3],
            [31.3, 54.8],
            [31.9, 54.3],
            [32.5, 53.8],
            [32.3, 53.2],
            [31.8, 52.6],
            [31.3, 52.1],
            [30.8, 51.9],
            [30.4, 52.5],
            [30.05, 53.1],
            [29.7, 53.7],
            [29.6, 54.3],
            [29.3, 55.1],
            [28.8, 55.5],
            [27.6, 55.7],
        ],
    },
    {
        partition: 1,
        power: 'prussia',
        label: [18.0, 55.45],
        points: [
            [17.6, 54.75],
            [18.35, 54.8],
            [18.8, 54.6],
            [18.65, 54.4],
            [19.3, 54.35],
            [19.7, 54.4],
            [20.2, 54.3],
            [20.9, 54.2],
            [21.0, 53.85],
            [20.4, 53.6],
            [19.9, 53.45],
            [19.4, 53.1],
            [18.8, 53.08],
            [18.3, 52.95],
            [17.5, 52.85],
            [16.6, 52.8],
            [15.8, 52.9],
            [16.4, 53.2],
            [16.7, 53.5],
            [17.0, 53.8],
            [17.3, 54.1],
            [17.2, 54.4],
        ],
    },
    {
        partition: 1,
        power: 'austria',
        label: [23.4, 49.2],
        points: [
            [19.1, 50.05],
            [20.5, 50.2],
            [21.0, 50.3],
            [21.8, 50.65],
            [22.5, 50.85],
            [23.3, 50.85],
            [24.0, 50.6],
            [24.4, 50.3],
            [25.2, 50.15],
            [25.9, 49.8],
            [26.1, 49.3],
            [26.25, 48.6],
            [25.9, 48.4],
            [25.3, 48.2],
            [24.9, 47.95],
            [24.4, 48.0],
            [23.5, 48.6],
            [22.6, 49.1],
            [21.5, 49.4],
            [20.9, 49.3],
            [20.0, 49.2],
            [19.4, 49.5],
            [19.1, 49.6],
            [18.9, 49.9],
        ],
    },
    /**
     * 1793: Russia east of the line from Druja on the Dvina through Pinsk to
     * the Zbruch and Khotyn; Prussia west of Czestochowa, Rawa and Soldau,
     * with Torun, and Gdansk, drawn as its own enclave.
     */
    {
        partition: 2,
        power: 'russia',
        label: [28.9, 52.4],
        points: [
            [27.45, 55.72],
            [27.6, 55.7],
            [28.8, 55.5],
            [29.3, 55.1],
            [29.6, 54.3],
            [29.7, 53.7],
            [30.05, 53.1],
            [30.4, 52.5],
            [30.8, 51.9],
            [30.5, 51.3],
            [30.4, 50.6],
            [31.0, 50.1],
            [32.0, 49.3],
            [31.0, 48.4],
            [29.8, 47.95],
            [29.2, 47.95],
            [28.2, 48.2],
            [27.4, 48.4],
            [26.25, 48.6],
            [26.1, 49.3],
            [26.2, 49.9],
            [26.3, 50.4],
            [26.4, 51.0],
            [26.2, 51.6],
            [26.1, 52.1],
            [26.5, 52.7],
            [26.7, 53.4],
            [26.6, 54.0],
            [26.8, 54.6],
            [27.0, 55.2],
        ],
    },
    {
        partition: 2,
        power: 'prussia',
        label: [18.7, 51.0],
        points: [
            [20.2, 53.3],
            [19.9, 53.45],
            [19.4, 53.1],
            [18.8, 53.08],
            [18.3, 52.95],
            [17.5, 52.85],
            [16.6, 52.8],
            [15.8, 52.9],
            [15.55, 52.5],
            [15.7, 52.2],
            [16.3, 51.85],
            [16.8, 51.7],
            [17.3, 51.6],
            [17.8, 51.4],
            [18.3, 51.2],
            [18.7, 50.9],
            [19.1, 50.8],
            [19.6, 51.2],
            [20.25, 51.75],
            [20.24, 52.23],
            [20.2, 52.7],
        ],
    },
    {
        partition: 2,
        power: 'prussia',
        points: [
            [18.45, 54.45],
            [18.85, 54.45],
            [18.9, 54.28],
            [18.5, 54.25],
        ],
    },
    /**
     * 1795: Russia everything east of the Bug and the Niemen, Samogitia
     * included; Austria the lands between the Pilica, the Vistula and the
     * Bug, with Krakow, reaching just over the old line to take in the city;
     * Prussia the rest, with Warsaw and the land up to the Niemen.
     */
    {
        partition: 3,
        power: 'russia',
        label: [24.6, 55.75],
        points: [
            [22.55, 54.95],
            [22.2, 55.05],
            [21.4, 55.2],
            [21.2, 55.6],
            [21.05, 55.95],
            [21.1, 56.1],
            [22.2, 56.4],
            [23.5, 56.35],
            [24.6, 56.3],
            [25.6, 56.1],
            [26.5, 55.88],
            [27.45, 55.72],
            [27.0, 55.2],
            [26.8, 54.6],
            [26.6, 54.0],
            [26.7, 53.4],
            [26.5, 52.7],
            [26.1, 52.1],
            [26.2, 51.6],
            [26.4, 51.0],
            [26.3, 50.4],
            [26.2, 49.9],
            [26.1, 49.3],
            [25.9, 49.8],
            [25.2, 50.15],
            [24.4, 50.3],
            [24.2, 50.45],
            [24.05, 50.8],
            [23.9, 51.2],
            [23.7, 51.6],
            [23.6, 52.1],
            [23.4, 52.6],
            [23.6, 53.2],
            [23.7, 53.68],
            [24.0, 54.3],
            [23.9, 54.9],
            [23.0, 55.05],
        ],
    },
    {
        partition: 3,
        power: 'austria',
        label: [22.4, 51.35],
        points: [
            [19.1, 50.05],
            [19.35, 50.35],
            [19.7, 50.55],
            [19.9, 51.0],
            [20.0, 51.35],
            [20.5, 51.6],
            [21.2, 51.85],
            [21.1, 52.1],
            [21.6, 52.45],
            [22.2, 52.6],
            [22.7, 52.4],
            [23.2, 52.3],
            [23.6, 52.1],
            [23.7, 51.6],
            [23.9, 51.2],
            [24.05, 50.8],
            [24.2, 50.45],
            [24.0, 50.6],
            [23.3, 50.85],
            [22.5, 50.85],
            [21.8, 50.65],
            [21.0, 50.3],
            [20.5, 50.2],
            [20.1, 50.02],
            [19.85, 49.97],
        ],
    },
    {
        partition: 3,
        power: 'prussia',
        label: [22.0, 52.95],
        points: [
            [20.2, 53.3],
            [20.6, 53.25],
            [21.6, 53.3],
            [22.5, 53.5],
            [22.8, 54.1],
            [22.8, 54.4],
            [22.9, 54.85],
            [22.55, 54.95],
            [23.0, 55.05],
            [23.9, 54.9],
            [24.0, 54.3],
            [23.7, 53.68],
            [23.6, 53.2],
            [23.4, 52.6],
            [23.6, 52.1],
            [23.2, 52.3],
            [22.7, 52.4],
            [22.2, 52.6],
            [21.6, 52.45],
            [21.1, 52.1],
            [21.2, 51.85],
            [20.5, 51.6],
            [20.0, 51.35],
            [19.9, 51.0],
            [19.7, 50.55],
            [19.35, 50.35],
            [19.1, 50.05],
            [19.0, 50.6],
            [18.7, 50.9],
            [19.1, 50.8],
            [19.6, 51.2],
            [20.25, 51.75],
            [20.24, 52.23],
            [20.2, 52.7],
        ],
    },
];

/**
 * Gdansk and Torun matter on every partition page, so they are always drawn;
 * towns that only matter from a later partition on say so with `from`.
 */
const towns: Town[] = [
    { key: 'warsaw', lon: 21.01, lat: 52.23, dx: 14, dy: 8, anchor: 'start' },
    { key: 'krakow', lon: 19.94, lat: 50.06, dx: -14, dy: -10, anchor: 'end' },
    { key: 'gdansk', lon: 18.65, lat: 54.35, dx: -14, dy: -10, anchor: 'end' },
    { key: 'torun', lon: 18.6, lat: 53.01, dx: 0, dy: 34, anchor: 'middle' },
    { key: 'lwow', lon: 24.03, lat: 49.84, dx: 14, dy: 8, anchor: 'start' },
    {
        key: 'vilnius',
        lon: 25.28,
        lat: 54.69,
        dx: 0,
        dy: -16,
        anchor: 'middle',
    },
    { key: 'polotsk', lon: 28.8, lat: 55.49, dx: 14, dy: -8, anchor: 'start' },
    {
        key: 'konigsberg',
        lon: 20.51,
        lat: 54.71,
        dx: 0,
        dy: -16,
        anchor: 'middle',
    },
    {
        key: 'grodno',
        lon: 23.83,
        lat: 53.68,
        dx: 14,
        dy: 8,
        anchor: 'start',
        from: 2,
    },
    {
        key: 'poznan',
        lon: 16.93,
        lat: 52.41,
        dx: 0,
        dy: 34,
        anchor: 'middle',
        from: 2,
    },
];

/**
 * The battlefields of the rising of 1794, on their real coordinates. The
 * three near Krakow sit close together, so their labels are pushed apart,
 * and Maciejowice is labelled to the west, clear of the name of the rump state.
 */
const battlefields: Site[] = [
    {
        id: 'raclawice',
        lon: 20.23,
        lat: 50.33,
        dx: 14,
        dy: 10,
        anchor: 'start',
    },
    {
        id: 'szczekociny',
        lon: 19.82,
        lat: 50.63,
        dx: 14,
        dy: -4,
        anchor: 'start',
    },
    {
        id: 'maciejowice',
        lon: 21.56,
        lat: 51.69,
        dx: -14,
        dy: 12,
        anchor: 'end',
    },
];

const visibleSites = computed(() =>
    battlefields.filter((site) => props.sites.includes(site.id)),
);

const areaLabels = [
    { key: 'sea', lon: 18.2, lat: 56.5 },
    { key: 'east_prussia', lon: 22.0, lat: 53.9 },
    { key: 'commonwealth', lon: 26.0, lat: 51.7 },
] as const;

const first = computed(() => Math.min(...props.highlight));
const last = computed(() => Math.max(...props.highlight));

/**
 * The map of 1815. Four pieces of the old Commonwealth get their own shape
 * and name; everything else east of the Kingdom stayed in the Russian Empire
 * and is drawn as one piece. Borders follow the Prosna, the Vistula, the Bug
 * and the Niemen as closely as a schematic can.
 */
type Region = {
    region: 'kingdom' | 'posen' | 'galicia' | 'free_city' | 'empire';
    power: Power | 'free';
    points: [number, number][];
    /** Where the region's name goes; the Free City is named at its town. */
    name?: [number, number];
};

const kingdomBorder: [number, number][] = [
    [18.55, 52.8],
    [18.75, 52.9],
    [19.05, 53.12],
    [19.45, 53.2],
    [20.15, 53.2],
    [20.7, 53.2],
    [21.6, 53.3],
    [22.5, 53.5],
    [22.8, 54.1],
    [22.8, 54.4],
    [22.9, 54.85],
    [22.85, 55.05],
    [23.5, 55.0],
    [23.9, 54.9],
    [24.0, 54.6],
    [24.05, 54.35],
    [23.95, 54.0],
    [23.5, 53.75],
    [23.0, 53.5],
    [22.6, 53.2],
    [22.3, 52.85],
    [22.5, 52.55],
    [23.0, 52.3],
    [23.65, 52.08],
    [23.6, 51.6],
    [23.9, 51.2],
    [24.1, 50.85],
    [24.0, 50.6],
    [23.4, 50.45],
    [22.7, 50.45],
    [22.2, 50.55],
    [21.83, 50.68],
    [21.2, 50.35],
    [20.5, 50.2],
    [20.1, 50.18],
    [19.85, 50.28],
    [19.4, 50.3],
    [19.2, 50.3],
    [19.15, 50.55],
    [19.0, 50.75],
    [18.7, 50.95],
    [18.55, 51.2],
    [18.2, 51.35],
    [18.1, 51.7],
    [17.9, 51.95],
    [17.75, 52.15],
    [17.9, 52.35],
    [18.3, 52.65],
];

const regions: Region[] = [
    {
        region: 'empire',
        power: 'russia',
        name: [28.4, 53.9],
        points: [
            [22.85, 55.05],
            [22.2, 55.1],
            [21.4, 55.2],
            [21.2, 55.6],
            [21.05, 55.95],
            [21.1, 56.1],
            [22.2, 56.4],
            [23.5, 56.35],
            [24.6, 56.3],
            [25.6, 56.1],
            [26.5, 55.88],
            [26.0, 56.6],
            [26.9, 57.1],
            [27.7, 57.25],
            [28.1, 56.9],
            [28.3, 56.4],
            [29.0, 56.0],
            [30.3, 56.1],
            [31.2, 55.8],
            [31.5, 55.3],
            [31.3, 54.8],
            [31.9, 54.3],
            [32.5, 53.8],
            [32.3, 53.2],
            [31.8, 52.6],
            [31.3, 52.1],
            [30.8, 51.9],
            [30.5, 51.3],
            [30.4, 50.6],
            [31.0, 50.1],
            [32.0, 49.3],
            [31.0, 48.4],
            [29.8, 47.95],
            [29.2, 47.95],
            [28.2, 48.2],
            [27.4, 48.4],
            [26.25, 48.6],
            [26.1, 49.3],
            [25.9, 49.8],
            [25.2, 50.15],
            [24.4, 50.3],
            [24.0, 50.6],
            [24.1, 50.85],
            [23.9, 51.2],
            [23.6, 51.6],
            [23.65, 52.08],
            [23.0, 52.3],
            [22.5, 52.55],
            [22.3, 52.85],
            [22.6, 53.2],
            [23.0, 53.5],
            [23.5, 53.75],
            [23.95, 54.0],
            [24.05, 54.35],
            [24.0, 54.6],
            [23.9, 54.9],
            [23.5, 55.0],
        ],
    },
    {
        region: 'kingdom',
        power: 'russia',
        name: [21.5, 51.35],
        points: kingdomBorder,
    },
    {
        region: 'posen',
        power: 'prussia',
        name: [17.7, 53.5],
        points: [
            [15.7, 52.2],
            [15.55, 52.5],
            [15.8, 52.9],
            [16.4, 53.2],
            [17.0, 53.3],
            [17.5, 53.3],
            [18.0, 53.2],
            [18.4, 53.05],
            [18.55, 52.8],
            [18.3, 52.65],
            [17.9, 52.35],
            [17.75, 52.15],
            [17.9, 51.95],
            [18.1, 51.7],
            [17.9, 51.4],
            [17.3, 51.6],
            [16.8, 51.7],
            [16.3, 51.85],
        ],
    },
    {
        region: 'galicia',
        power: 'austria',
        name: [24.4, 48.9],
        points: [
            [19.3, 50.05],
            [19.6, 50.0],
            [19.9, 50.02],
            [20.1, 50.07],
            [20.5, 50.2],
            [21.2, 50.35],
            [21.83, 50.68],
            [22.2, 50.55],
            [22.7, 50.45],
            [23.4, 50.45],
            [24.0, 50.6],
            [24.4, 50.3],
            [25.2, 50.15],
            [25.9, 49.8],
            [26.1, 49.3],
            [26.25, 48.6],
            [25.9, 48.4],
            [25.3, 48.2],
            [24.9, 47.95],
            [24.4, 48.0],
            [23.5, 48.6],
            [22.6, 49.1],
            [21.5, 49.4],
            [20.9, 49.3],
            [20.0, 49.2],
            [19.4, 49.5],
            [19.1, 49.6],
            [18.9, 49.9],
            [19.1, 50.05],
        ],
    },
    {
        region: 'free_city',
        power: 'free',
        points: [
            [19.3, 50.05],
            [19.2, 50.3],
            [19.4, 50.3],
            [19.85, 50.28],
            [20.1, 50.18],
            [20.1, 50.07],
            [19.9, 50.02],
            [19.6, 50.0],
        ],
    },
];

/** The towns the map of 1815 needs; Krakow is named as the Free City. */
const settlementTowns = [
    'warsaw',
    'krakow',
    'poznan',
    'gdansk',
    'vilnius',
    'lwow',
    'konigsberg',
];

/**
 * Around 1900 Krakow was part of Galicia, so on that map the Free City is
 * folded into it: its northern edge replaces the stretch of Galicia's border
 * it used to share, the first four points of the Galician outline.
 */
const galiciaWithKrakow = (points: [number, number][]): [number, number][] => [
    [19.2, 50.3],
    [19.4, 50.3],
    [19.85, 50.28],
    [20.1, 50.18],
    ...points.slice(4),
];

/**
 * West Prussia, with Gdansk and Torun, belonged to the Prussian share around
 * 1900 as well. It is drawn without a name of its own, under the one the
 * Grand Duchy's shape carries for the whole Prussian partition.
 */
const westPrussia: [number, number][] = [
    [15.8, 52.9],
    [16.4, 53.2],
    [17.0, 53.3],
    [17.5, 53.3],
    [18.0, 53.2],
    [18.4, 53.05],
    [18.55, 52.8],
    [18.75, 52.9],
    [19.05, 53.12],
    [19.45, 53.2],
    [19.7, 53.45],
    [19.6, 53.9],
    [19.4, 54.2],
    [19.3, 54.35],
    [18.65, 54.4],
    [18.8, 54.6],
    [18.35, 54.8],
    [17.6, 54.75],
    [17.2, 54.4],
    [17.3, 54.1],
    [17.0, 53.8],
    [16.7, 53.5],
    [16.4, 53.2],
];

/** The towns the map of about 1900 needs: the three cities first. */
const empireTowns = ['warsaw', 'krakow', 'poznan', 'gdansk', 'lwow'];

/** Where the borders of Prussia, Russia and Austria met, near Myslowice. */
const corner = { lon: 19.15, lat: 50.27 };

/**
 * The corner's name is too long for the narrow gap west of the marker, so it
 * is set on two lines: its last word goes underneath.
 */
const cornerLines = computed((): string[] => {
    const words = t(`${props.group}.map.labels.corner`).split(' ');

    return words.length > 1
        ? [words.slice(0, -1).join(' '), words[words.length - 1]]
        : words;
});

const visibleRegions = computed(() => {
    if (props.empires) {
        return regions
            .filter((region) => region.region !== 'free_city')
            .map((region) => ({
                ...region,
                d: path(
                    region.region === 'galicia'
                        ? galiciaWithKrakow(region.points)
                        : region.points,
                ),
            }));
    }

    return props.settlement
        ? regions.map((region) => ({ ...region, d: path(region.points) }))
        : [];
});

const westPrussiaPath = path(westPrussia);

const kingdomPath = path(kingdomBorder);

const regionFills: Record<Region['power'], string> = {
    russia: 'fill-flag-red',
    prussia: 'fill-bone',
    austria: 'fill-amber',
    free: 'fill-bone',
};

const regionLabelColours: Record<Region['power'], string> = {
    russia: 'fill-[#ff8a9c]',
    prussia: 'fill-bone',
    austria: 'fill-amber',
    free: 'fill-bone',
};

/** The Kingdom is the subject of the map, the empire around it is not. */
const regionOpacity = (region: Region['region']): number =>
    region === 'empire' ? 0.16 : region === 'free_city' ? 0.7 : 0.42;

const townLabel = (key: string): string =>
    props.settlement && key === 'krakow' ? 'krakow_city' : key;

const visibleTowns = computed(() => {
    if (props.empires) {
        return towns.filter((town) => empireTowns.includes(town.key));
    }

    return props.settlement
        ? towns.filter((town) => settlementTowns.includes(town.key))
        : towns.filter((town) => (town.from ?? 1) <= last.value);
});

/**
 * After 1795 nothing is left to name, so the label for the remainder is
 * dropped from the map of the last partition. The map of 1815 names its own
 * regions and keeps only the sea.
 */
const visibleAreaLabels = computed(() =>
    areaLabels.filter((label) =>
        props.settlement || props.empires
            ? label.key === 'sea'
            : label.key !== 'commonwealth' || last.value < 3,
    ),
);

/**
 * On the map of 1815 Krakow carries the long name of the Free City, which
 * only fits centred below the town. Around 1900 it goes there too, clear of
 * the Three Emperors' Corner just to its west.
 */
const townPlacement = (
    town: Town,
): { dx: number; dy: number; anchor: Town['anchor'] } =>
    (props.settlement || props.empires) && town.key === 'krakow'
        ? { dx: 0, dy: 34, anchor: 'middle' }
        : { dx: town.dx, dy: town.dy, anchor: town.anchor };

/**
 * The name of what remains moves with the map: after 1793 the old spot lies
 * inside Russia's new share.
 */
const areaPosition = (
    key: string,
    lon: number,
    lat: number,
): [number, number] =>
    key === 'commonwealth' && last.value >= 2 ? [24.0, 51.2] : [lon, lat];

const visibleShares = computed(() =>
    shares
        .filter(
            (share) =>
                !props.settlement &&
                !props.empires &&
                share.partition <= last.value,
        )
        .map((share) => ({
            ...share,
            d: path(share.points),
            lost: share.partition < first.value,
        })),
);

const fills: Record<Power, string> = {
    russia: 'fill-flag-red',
    prussia: 'fill-bone',
    austria: 'fill-amber',
};

const labelColours: Record<Power, string> = {
    russia: 'fill-[#ff8a9c]',
    prussia: 'fill-bone',
    austria: 'fill-amber',
};

const outline = path(commonwealth);
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
                <clipPath :id="`${group}-partition-frame`">
                    <rect x="30" y="0" width="870" height="800" />
                </clipPath>
            </defs>

            <g :clip-path="`url(#${group}-partition-frame)`">
                <path
                    v-if="!empires"
                    :d="outline"
                    class="fill-amber stroke-amber"
                    fill-opacity="0.08"
                    stroke-width="3"
                    stroke-linejoin="round"
                    stroke-opacity="0.7"
                />

                <path
                    v-for="(share, index) in visibleShares"
                    :key="`${share.partition}-${share.power}-${index}`"
                    :d="share.d"
                    :class="share.lost ? 'fill-bone-muted' : fills[share.power]"
                    :fill-opacity="share.lost ? 0.12 : 0.42"
                    stroke="currentColor"
                    class="text-ink"
                    stroke-width="2"
                    stroke-linejoin="round"
                />

                <path
                    v-for="region in visibleRegions"
                    :key="`region-${region.region}`"
                    :d="region.d"
                    :class="regionFills[region.power]"
                    :fill-opacity="regionOpacity(region.region)"
                    stroke="currentColor"
                    class="text-ink"
                    stroke-width="2"
                    stroke-linejoin="round"
                />

                <path
                    v-if="empires"
                    :d="westPrussiaPath"
                    class="fill-bone text-ink"
                    fill-opacity="0.42"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linejoin="round"
                />

                <path
                    v-if="empires"
                    :d="kingdomPath"
                    fill="none"
                    class="stroke-amber"
                    stroke-width="4"
                    stroke-linejoin="round"
                />

                <path
                    :d="polandOutline"
                    transform="translate(5.52 233.2) scale(0.4614)"
                    fill="none"
                    stroke="currentColor"
                    class="text-bone-muted"
                    stroke-width="7"
                    stroke-dasharray="18 14"
                    stroke-linejoin="round"
                    opacity="0.65"
                />
            </g>

            <text
                v-for="label in visibleAreaLabels"
                :key="`area-${label.key}`"
                :x="x(areaPosition(label.key, label.lon, label.lat)[0])"
                :y="y(areaPosition(label.key, label.lon, label.lat)[1])"
                text-anchor="middle"
                :class="
                    label.key === 'commonwealth'
                        ? 'fill-amber'
                        : 'fill-bone-muted'
                "
                class="font-display stroke-ink"
                :font-size="label.key === 'east_prussia' ? 20 : 26"
                letter-spacing="2"
                paint-order="stroke"
                stroke-width="5"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.${label.key}`) }}
            </text>

            <text
                v-for="share in visibleShares.filter((s) => !s.lost && s.label)"
                :key="`label-${share.partition}-${share.power}`"
                :x="x(share.label![0])"
                :y="y(share.label![1])"
                text-anchor="middle"
                :class="labelColours[share.power]"
                class="font-display stroke-ink"
                font-size="28"
                letter-spacing="1.5"
                paint-order="stroke"
                stroke-width="6"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.${share.power}`) }}
            </text>

            <text
                v-for="region in visibleRegions.filter((r) => r.name)"
                :key="`name-${region.region}`"
                :x="x(region.name![0])"
                :y="y(region.name![1])"
                text-anchor="middle"
                :class="regionLabelColours[region.power]"
                class="font-display stroke-ink"
                :font-size="region.region === 'kingdom' ? 24 : 22"
                letter-spacing="1.5"
                paint-order="stroke"
                stroke-width="6"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.${region.region}`) }}
            </text>

            <g v-for="town in visibleTowns" :key="town.key">
                <circle
                    :cx="x(town.lon)"
                    :cy="y(town.lat)"
                    r="7"
                    class="fill-bone"
                />
                <text
                    :x="x(town.lon) + townPlacement(town).dx"
                    :y="y(town.lat) + townPlacement(town).dy"
                    :text-anchor="townPlacement(town).anchor"
                    class="fill-bone font-display stroke-ink"
                    font-size="24"
                    letter-spacing="1"
                    paint-order="stroke"
                    stroke-width="5"
                    stroke-linejoin="round"
                >
                    {{ t(`${group}.map.labels.${townLabel(town.key)}`) }}
                </text>
            </g>

            <g v-if="empires">
                <rect
                    :x="x(corner.lon) - 8"
                    :y="y(corner.lat) - 8"
                    width="16"
                    height="16"
                    :transform="`rotate(45 ${x(corner.lon)} ${y(corner.lat)})`"
                    class="fill-amber stroke-ink"
                    stroke-width="3"
                />
                <text
                    :y="y(corner.lat) - 30"
                    text-anchor="end"
                    class="fill-amber font-display stroke-ink"
                    font-size="20"
                    letter-spacing="1"
                    paint-order="stroke"
                    stroke-width="5"
                    stroke-linejoin="round"
                >
                    <tspan
                        v-for="(line, index) in cornerLines"
                        :key="index"
                        :x="x(corner.lon) - 14"
                        :dy="index === 0 ? 0 : 22"
                    >
                        {{ line }}
                    </tspan>
                </text>
            </g>

            <g v-for="site in visibleSites" :key="`site-${site.id}`">
                <rect
                    :x="x(site.lon) - 7"
                    :y="y(site.lat) - 7"
                    width="14"
                    height="14"
                    :transform="`rotate(45 ${x(site.lon)} ${y(site.lat)})`"
                    class="fill-amber stroke-ink"
                    stroke-width="3"
                />
                <text
                    :x="x(site.lon) + site.dx"
                    :y="y(site.lat) + site.dy"
                    :text-anchor="site.anchor"
                    class="fill-amber font-display stroke-ink"
                    font-size="22"
                    letter-spacing="1"
                    paint-order="stroke"
                    stroke-width="5"
                    stroke-linejoin="round"
                >
                    {{ t(`${group}.map.labels.${site.id}`) }}
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
