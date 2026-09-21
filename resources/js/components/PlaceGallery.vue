<script setup lang="ts">
import ExhibitImage from '@/components/ExhibitImage.vue';

/**
 * A wall of photographs of one city.
 *
 * Cities give you whatever shape the photographer had: a tower is portrait, a
 * waterfront is landscape. Forcing both into one tile crops the tower's top
 * off, so the grid flows in columns instead and every picture keeps its own
 * proportions. Each one carries its real pixel size, so the column does not
 * jump about while the photographs load.
 */
export type Shot = {
    /** Image slug: public/images/<prefix>-<photo>-{sm,lg}.jpg */
    photo: string;
    /** Natural size of the large file, so the browser can reserve the space. */
    w: number;
    h: number;
    caption: string;
    credit: string;
};

defineProps<{
    prefix: string;
    shots: Shot[];
}>();
</script>

<template>
    <div class="gap-4 [column-count:1] sm:[column-count:2] lg:[column-count:3]">
        <figure
            v-for="shot in shots"
            :key="shot.photo"
            class="mb-4 break-inside-avoid"
        >
            <div
                class="border-hairline overflow-hidden rounded-sm border transition-colors duration-300 hover:border-[color:var(--color-amber-deep)]"
            >
                <ExhibitImage
                    :src="`/images/${prefix}-${shot.photo}-sm.jpg`"
                    :srcset="`/images/${prefix}-${shot.photo}-sm.jpg 960w, /images/${prefix}-${shot.photo}-lg.jpg 1280w`"
                    sizes="(min-width: 1024px) 33vw, (min-width: 640px) 48vw, 92vw"
                    :full="`/images/${prefix}-${shot.photo}-lg.jpg`"
                    :alt="shot.caption"
                    :caption="shot.caption"
                    :credit="shot.credit"
                    :image-class="'block h-auto w-full object-cover'"
                    :style="{ aspectRatio: `${shot.w} / ${shot.h}` }"
                />
            </div>
            <figcaption
                class="text-bone-muted mt-2 px-0.5 text-[0.8125rem] leading-snug"
            >
                {{ shot.caption }}
            </figcaption>
        </figure>
    </div>
</template>
