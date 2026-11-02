import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
  state: () => ({ toast: '' as string, timer: null as any }),
  actions: {
    notify(msg: string) {
      this.toast = msg
      if (import.meta.client) {
        clearTimeout(this.timer)
        this.timer = setTimeout(() => (this.toast = ''), 2500)
      }
    },
  },
})
