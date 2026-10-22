// Generates public/css/admin-theme.css: the NobleUI stylesheet the admin uses, with its theme colours,
// pixel font sizes, font and sidebar width turned into variables, so the admin can follow each staff
// member's Appearance (App\Services\Appearance). The admin layout loads it instead of the template's
// own demo1/style.css.
//
//   node scripts/admin-theme.mjs        (run again after replacing the template's style.css)
//
// It is the whole stylesheet, in the template's order, so every rule overrides others exactly as before;
// only values change, and only to variables whose defaults (the :root block at the top) are the
// template's own values, so nothing looks different until a staff member chooses otherwise.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SOURCE = path.join(root, 'public/admintemplate/nobleui/template/assets/css/demo1/style.css');
const OUTPUT = path.join(root, 'public/css/admin-theme.css');

const BASES = { primary: '#6571ff', success: '#05a34a', danger: '#ff3366', warning: '#fbbc06', info: '#66d1d1' };
const SHADES = [10, 15, 20, 25, 30, 40, 50, 60];               // Bootstrap's shade-color() steps
const TINTS = [10, 15, 20, 25, 30, 40, 50, 60, 70, 75, 80, 85, 90];   // and tint-color()
const FONT = '"Roboto", Helvetica, sans-serif';

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

/** The variable expression for a theme colour (with its alpha, if any). */
function expr({ name, kind, pct }, alpha, onDark) {
  const v = name === 'primary' && onDark ? 'primary-on-dark' : name;
  if (!kind) return alpha === null ? `var(--t-${v})` : `rgba(var(--t-${v}-rgb), ${alpha})`;
  const base = `color-mix(in srgb, var(--t-${v}), ${kind === 'shade' ? '#000' : '#fff'} ${pct}%)`;
  return alpha === null ? base : `color-mix(in srgb, ${base} ${Math.round(alpha * 100)}%, transparent)`;
}

/** The value with its theme colours, pixel font size, font or sidebar width replaced; null if nothing changes. */
function rewrite(decl, onDark) {
  let changed = false;
  let value = decl.value;

  // --bs-primary-rgb: 101, 113, 255 (Bootstrap's rgb lists)
  const list = decl.prop.match(/^--bs-(\w+)-rgb$/);
  if (list && BASES[list[1]] && same(value.split(',').map(Number), rgb(BASES[list[1]]))) return `var(--t-${list[1]}-rgb)`;

  if (!value.includes('data:')) {   // inline SVG images keep their own (encoded) colours
    value = value.replace(/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b|rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/g, (m, hex, r, g, b, a) => {
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

const sheet = postcss.parse(fs.readFileSync(SOURCE, 'utf8'));
sheet.walkComments(c => { if (c.text.startsWith('# sourceMappingURL')) c.remove(); });
// @charset must open the file: taken out here, written first below.
let charset = '';
sheet.walkAtRules('charset', at => { charset = `@charset ${at.params};\n`; at.remove(); });

const rules = [];
sheet.walkRules(rule => { if (!(rule.parent.type === 'atrule' && /keyframes/.test(rule.parent.name))) rules.push(rule); });

let count = 0;
const apply = (rule, onDark) => rule.each(node => {
  if (node.type !== 'decl') return;
  const value = rewrite(node, onDark);
  if (value !== null) { node.value = value; count++; }
});
for (const rule of rules) {
  // The dark sidebar shows the accent as text, so its rules use the lighter sidebar variant. A rule
  // naming both the dark sidebar and other places is split in two, side by side.
  const dark = rule.selectors.filter(s => s.includes('sidebar-dark'));
  const light = rule.selectors.filter(s => !s.includes('sidebar-dark'));
  const differs = dark.length && light.length && rule.nodes.some(n => n.type === 'decl' && rewrite(n, true) !== rewrite(n, false));
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

const defaults = [
  ...Object.entries(BASES).flatMap(([n, h]) => [`--t-${n}: ${h};`, `--t-${n}-rgb: ${rgb(h).join(', ')};`]),
  `--t-primary-on-dark: ${BASES.primary};`, `--t-primary-on-dark-rgb: ${rgb(BASES.primary).join(', ')};`,
  ...['success', 'danger', 'warning', 'info'].map(n => `--t-${n}-on-dark: ${BASES[n]};`),
  `--t-font: ${FONT};`, '--t-text: 1;',
].join('\n  ');

const header = `/* GENERATED by scripts/admin-theme.mjs from NobleUI's demo1/style.css: do not edit by hand.
   The template's stylesheet with ${count} values (theme colours, pixel font sizes, font, sidebar width)
   as variables (App\\Services\\Appearance). The defaults below are the template's own values. */
:root {
  ${defaults}
}
`;
fs.mkdirSync(path.dirname(OUTPUT), { recursive: true });
fs.writeFileSync(OUTPUT, charset + header + sheet.toString().replace(/^\uFEFF/, '').replace(/^\s+/, '') + '\n');
console.log(`${path.relative(root, OUTPUT)}: ${count} values as variables`);
