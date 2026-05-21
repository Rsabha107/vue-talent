<script setup>
const props = defineProps({
  name: { type: String, default: '' },
  c: { type: Number, default: 0 },
  initials: { type: String, default: '' },
  size: { type: String, default: '' }, // '', 'lg', 'xl'
})

const computed_initials = computed(() => {
  if (props.initials) return props.initials
  return (props.name || '?').split(' ').map(w => w[0]).slice(0, 2).join('')
})

import { computed } from 'vue'
</script>

<template>
  <span
    class="mhr-avatar"
    :class="size ? `mhr-avatar--${size}` : ''"
    :data-c="c"
  >{{ computed_initials }}</span>
</template>
