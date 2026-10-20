import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// Admin-panel dev server port: NOT 5173 (reserved by api/compose.yaml for
// Laravel Sail's own, unused built-in Vite assets), NOT 5174 (already bound
// on this host by an unrelated project, kyn-workspace-frontend), and NOT
// 5180 (storefront's port). 5175 is this admin-panel app's port. Keep in
// sync with api/.env SANCTUM_STATEFUL_DOMAINS / FRONTEND_ADMIN_URL and
// api/config/cors.php allowed_origins.
const PORT = 5175

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
