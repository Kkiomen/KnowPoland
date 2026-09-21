<script setup lang="ts">
import PolandOutline from '@/components/PolandOutline.vue';

/**
 * The layered backdrop behind a full screen section: a photograph of what the
 * section is actually about, a coloured wash, scan lines, and a gradient that
 * keeps the type readable over all of it.
 *
 * Photographs are free to use (Unsplash License, or CC0 from Wikimedia
 * Commons) and are credited in lang/<locale>/site.php under footer.photos.
 */
interface Props {
    /** CSS background for the coloured wash of this section. */
    wash: string;
    /** Optional faint photograph in public/images, without path or extension. */
    photo?: string;
    /** How strongly the faint photograph reads. 5 is mist. */
    photoOpacity?: number;
    /** Blur the photograph, so it sits behind a framed copy of itself. */
    photoBlur?: boolean;
    /** Fade the photograph into the ground from the bottom up. */
    photoMask?: 'none' | 'bottom';
    /** The border of Poland belongs to the opening screen only. */
    outline?: 'right' | 'left' | 'none';
    /** Load immediately: true for the first screen, false below the fold. */
    eager?: boolean;
}

withDefaults(defineProps<Props>(), {
    photo: undefined,
    photoOpacity: 6,
    photoBlur: true,
    photoMask: 'none' as Props['photoMask'],
    outline: 'none' as Props['outline'],
    eager: false,
});
</script>

<template>
    <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute inset-0" :style="{ background: wash }" />

        <picture v-if="photo && photoMask === 'none'">
            <source
                type="image/avif"
                :srcset="`/images/tlo-${photo}-1400.avif`"
            />
            <img
                :src="`/images/tlo-${photo}-1400.jpg`"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
                :class="photoBlur ? 'scale-110 blur-[6px]' : ''"
                :style="{ opacity: photoOpacity / 100 }"
                :loading="eager ? 'eager' : 'lazy'"
                :fetchpriority="eager ? 'high' : 'low'"
                decoding="async"
            />
        </picture>

        <PolandOutline
            v-if="outline !== 'none'"
            class="text-flag-red pointer-events-none absolute top-1/2 h-[74%] w-auto -translate-y-1/2 opacity-35 sm:h-[84%] sm:opacity-75"
            :class="
                outline === 'right'
                    ? 'right-[2%] sm:right-[5%]'
                    : 'left-[2%] sm:left-[5%]'
            "
        />

        <div
            class="absolute inset-0 opacity-50 [background:repeating-linear-gradient(0deg,rgba(0,0,0,.22)_0_1px,rgba(0,0,0,0)_1px_3px)]"
        />
        <div
            class="from-ink/45 via-ink/10 to-ink/95 absolute inset-0 bg-linear-to-b"
        />
        <picture v-if="photo && photoMask === 'bottom'">
            <source
                type="image/avif"
                :srcset="`/images/tlo-${photo}-1400.avif`"
            />
            <img
                :src="`/images/tlo-${photo}-1400.jpg`"
                alt=""
                class="pointer-events-none absolute inset-x-0 bottom-0 h-[82%] w-full object-cover object-center"
                :style="{
                    opacity: photoOpacity / 100,
                    maskImage:
                        'linear-gradient(to top, #000 0%, #000 55%, transparent 96%)',
                }"
                :loading="eager ? 'eager' : 'lazy'"
                :fetchpriority="eager ? 'high' : 'low'"
                decoding="async"
            />
        </picture>
        <div
            v-if="photo && photoMask === 'bottom'"
            class="from-ink/85 via-ink/35 absolute inset-0 bg-linear-to-r to-transparent"
        />
    </div>
</template>
