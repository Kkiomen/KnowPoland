<script setup lang="ts">
import { onMounted } from 'vue';

import { t } from '@/i18n';
import { askIfUnanswered, choose, consentOpen } from '@/lib/consent';

onMounted(askIfUnanswered);
</script>

<template>
    <section
        v-if="consentOpen"
        role="region"
        :aria-label="t('footer.consent.label')"
        class="border-hairline bg-ink-raised text-bone fixed inset-x-4 bottom-4 z-50 mx-auto max-w-xl rounded-lg border p-5 shadow-2xl"
    >
        <p class="text-sm leading-relaxed">{{ t('footer.consent.body') }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <button
                type="button"
                class="border-bone hover:bg-bone hover:text-ink min-h-[44px] flex-1 rounded-full border px-5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber"
                @click="choose('granted')"
            >
                {{ t('footer.consent.accept') }}
            </button>
            <button
                type="button"
                class="border-bone hover:bg-bone hover:text-ink min-h-[44px] flex-1 rounded-full border px-5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber"
                @click="choose('denied')"
            >
                {{ t('footer.consent.reject') }}
            </button>
        </div>
    </section>
</template>
