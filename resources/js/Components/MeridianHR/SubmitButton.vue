<template>
  <button 
    class="mhr-btn mhr-btn--primary" 
    @click="$emit('click')"
    :disabled="processing"
    :style="processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
  >
    <span v-if="processing" style="display:flex;align-items:center;gap:8px;">
      <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" opacity="0.25"/>
        <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
      </svg>
      {{ processingText }}
    </span>
    <span v-else>{{ text }}</span>
  </button>
</template>

<script setup>
defineProps({
  processing: {
    type: Boolean,
    default: false
  },
  text: {
    type: String,
    default: 'Save changes'
  },
  processingText: {
    type: String,
    default: 'Saving...'
  }
})

defineEmits(['click'])
</script>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
