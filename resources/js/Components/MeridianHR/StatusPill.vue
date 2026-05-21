<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: '' },
})

const MAP = {
  approved:  { cls: 'mhr-pill--success', label: 'Approved' },
  pending:   { cls: 'mhr-pill--warn',    label: 'Pending' },
  rejected:  { cls: 'mhr-pill--danger',  label: 'Rejected' },
  draft:     { cls: 'mhr-pill--plain',   label: 'Draft' },
  submitted: { cls: 'mhr-pill--info',    label: 'Submitted' },
  paid:      { cls: 'mhr-pill--success', label: 'Paid' },
  signed:    { cls: 'mhr-pill--success', label: 'Signed' },
  verified:  { cls: 'mhr-pill--success', label: 'Verified' },
  issued:    { cls: 'mhr-pill--info',    label: 'Issued' },
  current:   { cls: 'mhr-pill--info',    label: 'Current' },
}

const pill = computed(() => {
  const key = (props.status || '').toLowerCase()
  return MAP[key] || { cls: 'mhr-pill--plain', label: props.status }
})
</script>

<template>
  <span class="mhr-pill" :class="pill.cls">{{ pill.label }}</span>
</template>
