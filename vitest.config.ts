import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'url'
import { defineConfig } from 'vitest/config'

const stub = fileURLToPath(new URL('./tests/js/stubs/LaraviltComponentRenderer.vue', import.meta.url))

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@laravilt/forms/components/LaraviltComponentRenderer.vue': stub,
        },
    },
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.ts'],
    },
})
