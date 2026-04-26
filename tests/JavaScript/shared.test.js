import { describe, expect, it } from 'vitest';

import {
  formatDateTime,
  getContrastColor,
  hexToRgb,
  normalizeHexColor,
} from '../../public/assets/js/modules/shared.js';

describe('normalizeHexColor', ()=>{
  it('normalizes valid six-digit hex colors', ()=>{
    expect(normalizeHexColor(' #A1B2C3 ')).toBe('#a1b2c3');
    expect(normalizeHexColor('#39ff14')).toBe('#39ff14');
  });

  it('rejects invalid or unsupported color values', ()=>{
    expect(normalizeHexColor('#abc')).toBeNull();
    expect(normalizeHexColor('39ff14')).toBeNull();
    expect(normalizeHexColor('#zzzzzz')).toBeNull();
    expect(normalizeHexColor(null)).toBeNull();
  });
});

describe('hexToRgb', ()=>{
  it('converts normalized hex colors to RGB channels', ()=>{
    expect(hexToRgb('#39ff14')).toEqual({ r: 57, g: 255, b: 20 });
    expect(hexToRgb('#000000')).toEqual({ r: 0, g: 0, b: 0 });
    expect(hexToRgb('#FFFFFF')).toEqual({ r: 255, g: 255, b: 255 });
  });

  it('returns null for invalid hex colors', ()=>{
    expect(hexToRgb('#fff')).toBeNull();
    expect(hexToRgb('not-a-color')).toBeNull();
  });
});

describe('getContrastColor', ()=>{
  it('returns dark text for light colors', ()=>{
    expect(getContrastColor({ r: 255, g: 255, b: 255 })).toBe('#0d1520');
  });

  it('returns light text for dark colors and invalid RGB values', ()=>{
    expect(getContrastColor({ r: 0, g: 0, b: 0 })).toBe('#f6f8fb');
    expect(getContrastColor(null)).toBe('#0d1520');
  });
});

describe('formatDateTime', ()=>{
  it('formats date-like values with the provided locale and options', ()=>{
    expect(formatDateTime('2024-06-15T10:30:00Z', {
      locale: 'en-US',
      options: {
        timeZone: 'UTC',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      },
    })).toBe('06/15/2024, 10:30');
  });

  it('returns an empty string for missing or invalid dates', ()=>{
    expect(formatDateTime(null)).toBe('');
    expect(formatDateTime(undefined)).toBe('');
    expect(formatDateTime('   ')).toBe('');
    expect(formatDateTime('not-a-date')).toBe('');
  });

  it('returns an empty string when formatting options are invalid', ()=>{
    expect(formatDateTime('2024-06-15T10:30:00Z', {
      locale: 'en-US',
      options: {
        dateStyle: 'short',
        year: 'numeric',
      },
    })).toBe('');
  });
});
