import { describe, expect, it } from 'vitest'
import { dt, fromInputDt, money, toInputDt, ORDER_STATUS, payMethod } from '../src/format'

describe('format', () => {
  it('formats VND with dot separators', () => expect(money(2890000)).toBe('2.890.000đ'))
  it('returns empty string for null money', () => expect(money(null)).toBe(''))
  it('converts datetime-local input in Asia/Ho_Chi_Minh to UTC ISO and back', () => {
    const iso = fromInputDt('2026-11-02T08:30')
    expect(iso).toBe('2026-11-02T01:30:00.000Z')
    expect(toInputDt(iso)).toBe('2026-11-02T08:30')
  })
  it('handles empty dates', () => { expect(fromInputDt('')).toBeNull(); expect(dt(null)).toBe('—') })
  it('maps every order status to a label and pill class', () => {
    for (const s of ['pending', 'confirmed', 'packing', 'shipping', 'completed', 'cancelled', 'returned']) expect(ORDER_STATUS[s]).toHaveLength(2)
    expect(payMethod('momo')).toBe('MoMo'); expect(payMethod('cod')).toBe('COD')
  })
})
