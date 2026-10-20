import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// Storefront dev server port: NOT 5173 (reserved by api/compose.yaml for
// Laravel Sail's own, unused built-in Vite assets) and NOT 5174 (already
// bound on this host by an unrelated project, kyn-workspace-frontend).
// 5180 is this storefront's port; a future admin-panel app should use yet
// another port (e.g. 5175) rather than reusing this one. Keep in sync with
// api/.env SANCTUM_STATEFUL_DOMAINS / FRONTEND_STOREFRONT_URL and
// api/config/cors.php allowed_origins.
const PORT = 5180

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: PORT,
    strictPort: true,
  },
})
