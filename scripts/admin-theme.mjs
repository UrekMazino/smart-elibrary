// Generates the admin's stylesheets from NobleUI's: public/css/admin-theme.css from the light demo1/style.css
// and public/css/admin-theme-dark.css from the dark demo2/style.css, with their theme colours, neutral
// colours (page, panels, borders, text), pixel font sizes, font and sidebar width turned into variables,
// so the admin follows each staff member's Appearance (App\Services\Appearance). The admin layout loads
// one of them (both, by the device's light/dark setting, for "Follow my device") instead of NobleUI's.
//
//   node scripts/admin-theme.mjs        (run again after replacing the template's stylesheets)
//
// Each is the whole stylesheet, in the template's order, so every rule overrides others exactly as
// before; only values change, to variables that fall back to the template's own values. The --t-*
// variables (accent, status colours, font, text size) have their defaults in a :root block at the top;
// the --ui-* roles (page, surface, border, text...) have none: they are only set for a background, high
// contrast or the dark mode, so with nothing chosen the stylesheet renders exactly as NobleUI's.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const TEMPLATE = path.join(root, 'public/admintemplate/nobleui/template/assets/css');

const BASES = { primary: '#6571ff', success: '#05a34a', danger: '#ff3366', warning: '#fbbc06', info: '#66d1d1' };
const SHADES = [10, 15, 20, 25, 30, 40, 50, 60];               // Bootstrap's shade-color() steps
const TINTS = [10, 15, 20, 25, 30, 40, 50, 60, 70, 75, 80, 85, 90];   // and tint-color()
const FONT = '"Roboto", Helvetica, sans-serif';

// The neutral colours each variant uses for a role, by property: page background, surfaces (cards,
// navbar, menus), raised surfaces (disabled fields, input labels, hover), borders, text, muted text.
const SHEETS = [
  {
    source: 'demo1/style.css', output: 'public/css/admin-theme.css', dark: false,
    roles: {
      background: { '#fff': 'surface', '#ffffff': 'surface', '#f9fafb': 'page', '#e9ecef': 'surface-2', '#f8f9fa': 'surface-2' },
      border: { '#e9ecef': 'border', '#f2f4f9': 'border', '#dee2e6': 'border', '#fff': 'surface', '#ffffff': 'surface' },
      color: { '#000': 'text', '#000000': 'text', '#7987a1': 'muted' },
      '--bs-body-bg': { '#f9fafb': 'page' }, '--bs-body-color': { '#000': 'text' },
    },
  },
  {
    source: 'demo2/style.css', output: 'public/css/admin-theme-dark.css', dark: true,
    roles: {
      // #0a1122: card headers and footers; #18284e: hover and scrollbar backgrounds; #243866: focused fields.
      background: { '#0c1427': 'surface', '#070d19': 'page', '#121e3b': 'surface-2', '#060c17': 'page', '#0a1122': 'page', '#18284e': 'surface-2' },
      border: { '#172340': 'border', '#0c1427': 'surface', '#18284e': 'surface-2', '#243866': 'muted' },
      color: { '#d0d6e1': 'text', '#7987a1': 'muted' },
      '--bs-body-bg': { '#070d19': 'page' }, '--bs-body-color': { '#d0d6e1': 'text' },
    },
  },
];

const rgb = h => { h = h.replace('#', ''); if (h.length === 3) h = [...h].map(c => c + c).join(''); return [0, 2, 4].map(i => parseInt(h.slice(i, i + 2), 16)); };
const mix = (a, b, w) => a.map((v, i) => Math.round(v * (1 - w) + b[i] * w));
const same = (a, b) => a.every((v, i) => Math.abs(v - b[i]) <= 1);

/** Which theme colour a colour is: { name, shade|tint|null, pct }, or null. Must be unambiguous. */
function classify(c) {
  const hits = [];
  for (const [name, hex] of Object.entries(BASES)) {
    const base = rgb(hex);
    if (same(c, base)) hits.push({ name, kind: null, pct: 0 });
    for (const p of SHADES) if (same(c, mix(base, [0, 0, 0], p / 100))) hits.push({ name, kind: 'shade', pct: p });
    for (const p of TINTS) if (same(c, mix(base, [255, 255, 255], p / 100))) hits.push({ name, kind: 'tint', pct: p });
  }
  return hits.length === 1 ? hits[0] : null;
}

/** The variable expression for a theme colour (with its alpha, if any); on dark, text and lines use the lighter variant. */
function expr({ name, kind, pct }, alpha, onDark) {
  const v = onDark ? `${name}-on-dark` : name;
  if (!kind) return alpha === null ? `var(--t-${v})` : `rgba(var(--t-${v}-rgb), ${alpha})`;
  const base = `color-mix(in srgb, var(--t-${v}), ${kind === 'shade' ? '#000' : '#fff'} ${pct}%)`;
  return alpha === null ? base : `color-mix(in srgb, ${base} ${Math.round(alpha * 100)}%, transparent)`;
}

const group = prop => /^background(-color)?$/.test(prop) ? 'background' : /^border/.test(prop) ? 'border' : prop === 'color' ? 'color' : prop;

/** The value with its colours, pixel font size, font or sidebar width replaced; null if nothing changes. */
function rewrite(decl, sheet, darkContext) {
  let changed = false;
  let value = decl.value;

  // --bs-primary-rgb: 101, 113, 255 (Bootstrap's rgb lists)
  const list = decl.prop.match(/^--bs-(\w+)-rgb$/);
  if (list && BASES[list[1]] && same(value.split(',').map(Number), rgb(BASES[list[1]]))) return `var(--t-${list[1]}-rgb)`;

  if (!value.includes('data:')) {   // inline SVG images keep their own (encoded) colours
    // The light stylesheet's dark sidebar keeps its own neutrals: the page's roles (a light background,
    // high contrast) are not for it.
    const roles = (sheet.dark || !darkContext) ? (sheet.roles[group(decl.prop)] || {}) : {};
    // On a dark background (the dark stylesheet, or the light one's dark sidebar), text and lines in a
    // theme colour use its lighter variant; backgrounds keep the colour itself (white text sits on them).
    const onDark = darkContext && !/^background/.test(decl.prop);
    value = value.replace(/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b|rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/g, (m, hex, r, g, b, a) => {
      const role = hex && roles[m.toLowerCase()];
      if (role) { changed = true; return `var(--ui-${role}, ${m})`; }
      const hit = classify(hex ? rgb(hex) : [+r, +g, +b]);
      if (!hit) return m;
      changed = true;
      return expr(hit, a === undefined ? null : +a, onDark);
    });
  }
  if (decl.prop === 'font-size' && /^[\d.]+px$/.test(value)) { value = `calc(${value} * var(--t-text, 1))`; changed = true; }
  if ((decl.prop === 'font-family' || decl.prop === '--bs-font-sans-serif') && value.includes('"Roboto"')) { value = 'var(--t-font)'; changed = true; }
  // The sidebar is 240px wide (and the page and navbar beside it start there): it grows with the text
  // size, so the menu's labels and badges still fit.
  if (['width', 'margin-left', 'left'].includes(decl.prop) && /(^|[^\d.])-?240px/.test(value)) {
    value = value.replace(/(-?)240px/g, (m, minus) => `calc(${minus}240px * var(--t-text, 1))`);
    changed = true;
  }
  return changed ? value : null;
}

const defaults = [
  ...Object.entries(BASES).flatMap(([n, h]) => [`--t-${n}: ${h};`, `--t-${n}-rgb: ${rgb(h).join(', ')};`, `--t-${n}-on-dark: ${h};`, `--t-${n}-on-dark-rgb: ${rgb(h).join(', ')};`]),
  `--t-font: ${FONT};`, '--t-text: 1;',
].join('\n  ');

for (const sheet of SHEETS) {
  const css = postcss.parse(fs.readFileSync(path.join(TEMPLATE, sheet.source), 'utf8'));
  css.walkComments(c => { if (c.text.startsWith('# sourceMappingURL')) c.remove(); });
  // @charset must open the file: taken out here, written first below.
  let charset = '';
  css.walkAtRules('charset', at => { charset = `@charset ${at.params};\n`; at.remove(); });

  const rules = [];
  css.walkRules(rule => { if (!(rule.parent.type === 'atrule' && /keyframes/.test(rule.parent.name))) rules.push(rule); });

  let count = 0;
  const apply = (rule, darkContext) => rule.each(node => {
    if (node.type !== 'decl') return;
    const value = rewrite(node, sheet, darkContext);
    if (value !== null) { node.value = value; count++; }
  });
  for (const rule of rules) {
    if (sheet.dark) { apply(rule, true); continue; }
    // The light stylesheet's dark sidebar shows colours on dark too. A rule naming both the dark
    // sidebar and other places is split in two, side by side.
    const dark = rule.selectors.filter(s => s.includes('sidebar-dark'));
    const light = rule.selectors.filter(s => !s.includes('sidebar-dark'));
    const differs = dark.length && light.length && rule.nodes.some(n => n.type === 'decl' && rewrite(n, sheet, true) !== rewrite(n, sheet, false));
    if (differs) {
      const darkRule = rule.clone({ selectors: dark });
      rule.selectors = light;
      rule.after(darkRule);
      apply(darkRule, true);
      apply(rule, false);
    } else {
      apply(rule, dark.length > 0 && !light.length);
    }
  }

  const header = `/* GENERATED by scripts/admin-theme.mjs from NobleUI's ${sheet.source}: do not edit by hand.
   The template's stylesheet with ${count} values (theme and neutral colours, pixel font sizes, font,
   sidebar width) as variables (App\\Services\\Appearance). The --t-* defaults below are the template's
   own values; the --ui-* roles fall back to them where they are used. */
:root {
  ${defaults}
}
`;
  const out = path.join(root, sheet.output);
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, charset + header + css.toString().replace(/^﻿/, '').replace(/^\s+/, '') + '\n');
  console.log(`${sheet.output}: ${count} values as variables`);
}
