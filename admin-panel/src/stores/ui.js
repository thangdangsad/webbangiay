import { defineStore } from 'pinia'

export const useUi = defineStore('ui', {
  state: () => ({ toast: '' }),
  actions: {
    notify(msg) {
      this.toast = msg
      clearTimeout(this._t)
      this._t = setTimeout(() => (this.toast = ''), 2500)
    },
  },
})
