<script setup>
defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 18 },
})

const ICONS = {
  home:       `<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>`,
  calendar:   `<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>`,
  inbox:      `<path d="M3 13h5l1 3h6l1-3h5"/><path d="M5 13 6 5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2l1 8v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6Z"/>`,
  clock:      `<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>`,
  doc:        `<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>`,
  wallet:     `<path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7H5a2 2 0 0 1-2-2Zm0 0a2 2 0 0 1 2-2h11v3"/><circle cx="17" cy="14" r="1"/>`,
  users:      `<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 4.5A3.5 3.5 0 0 1 16 11"/><path d="M17 14a6 6 0 0 1 4 6"/>`,
  user:       `<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>`,
  cog:        `<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1A1.7 1.7 0 0 0 10 3.1V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1A1.7 1.7 0 0 0 20.9 10H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>`,
  settings:   `<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1A1.7 1.7 0 0 0 10 3.1V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1A1.7 1.7 0 0 0 20.9 10H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>`,
  search:     `<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>`,
  bell:       `<path d="M6 8a6 6 0 1 1 12 0c0 5 2 7 2 7H4s2-2 2-7"/><path d="M10 19a2 2 0 0 0 4 0"/>`,
  chevron:    `<path d="m9 6 6 6-6 6"/>`,
  chevdown:   `<path d="m6 9 6 6 6-6"/>`,
  plus:       `<path d="M12 5v14M5 12h14"/>`,
  filter:     `<path d="M3 5h18M6 12h12M10 19h4"/>`,
  download:   `<path d="M12 4v12"/><path d="m7 11 5 5 5-5"/><path d="M5 20h14"/>`,
  upload:     `<path d="M12 20V8"/><path d="m7 13 5-5 5 5"/><path d="M5 4h14"/>`,
  check:      `<path d="m5 12 5 5L20 7"/>`,
  x:          `<path d="M6 6 18 18M6 18 18 6"/>`,
  more:       `<circle cx="5" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="19" cy="12" r="1.4"/>`,
  arrowup:    `<path d="M12 19V5"/><path d="m6 11 6-6 6 6"/>`,
  arrowdown:  `<path d="M12 5v14"/><path d="m6 13 6 6 6-6"/>`,
  'arrow-right': `<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>`,
  briefcase:  `<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>`,
  award:      `<circle cx="12" cy="9" r="6"/><path d="m9 14-2 7 5-3 5 3-2-7"/>`,
  book:       `<path d="M4 5a2 2 0 0 1 2-2h13v17H6a2 2 0 0 0-2 2V5Z"/><path d="M4 19a2 2 0 0 0 2 2h13"/>`,
  'id-card':  `<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2.5"/><path d="M14 10h5M14 14h3M5 18a4 4 0 0 1 8 0"/>`,
  'file-signature': `<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/><path d="M14 3v5h5M9 13h3"/><path d="M14.5 17.5 19 13l2 2-4.5 4.5L14 20Z"/>`,
  eye:        `<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>`,
  'eye-off':  `<path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M7 7A10 10 0 0 0 2 12s3.5 7 10 7a10 10 0 0 0 5-1.3M12 5c2.7 0 5.3 1.2 7.4 3.5L21 10M15 12a3 3 0 0 1-2.5 2.9"/>`,
  lock:       `<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>`,
  zap:        `<path d="M13 3 4 14h7l-1 7 9-11h-7Z"/>`,
  trash:      `<path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M6 7v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7M10 11v6M14 11v6"/>`,
  edit:       `<path d="M12 20h9"/><path d="M16.5 3.5 20 7l-12 12-4 1 1-4Z"/>`,
  copy:       `<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>`,
  alert:      `<path d="M12 4 2 21h20Z"/><path d="M12 10v5M12 18h.01"/>`,
  info:       `<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>`,
  collapse:   `<path d="M9 4v16"/><path d="m13 9 3 3-3 3"/><rect x="3" y="4" width="18" height="16" rx="2"/>`,
  expand:     `<path d="M9 4v16"/><path d="m16 9-3 3 3 3"/><rect x="3" y="4" width="18" height="16" rx="2"/>`,
  phone:      `<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7 13 13 0 0 0 .7 2.8 2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5 13 13 0 0 0 2.8.7 2 2 0 0 1 1.7 2.1Z"/>`,
  mail:       `<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>`,
  building:   `<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 7h.01M14 7h.01M9 11h.01M14 11h.01M9 15h.01M14 15h.01M10 21v-4h4v4"/>`,
  history:    `<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 8v5l4 2"/>`,
  refresh:    `<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>`,
  logout:     `<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>`,
  pin:        `<path d="M12 2v8m0 0L8 6m4 4 4-4M8 14l-3 8 8-3 7 7V8L8 14Z"/>`,
  image:      `<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L9 18"/>`,
  close:      `<path d="M6 6 18 18M6 18 18 6"/>`,
  shield:     `<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>`,
}
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
    v-html="ICONS[name] || ''"
  />
</template>
