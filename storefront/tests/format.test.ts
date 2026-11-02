import { describe, expect, it } from 'vitest'
import { money, statusClass, statusLabel, payLabel } from '../composables/useFormat'

describe('useFormat', () => {
  it('formats VND', () => expect(money(1650000)).toBe('1.650.000đ'))
  it('labels order status', () => { expect(statusLabel('shipping')).toBe('Đang giao'); expect(statusClass('cancelled')).toBe('st-cancel') })
  it('labels payment method', () => expect(payLabel('momo')).toBe('Ví MoMo'))
})
