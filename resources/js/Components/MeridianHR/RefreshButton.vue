<script setup>
import { ref } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  isRefreshing: {
    type: Boolean,
    default: false
  },
  size: {
    type: Number,
    default: 14
  },
  variant: {
    type: String,
    default: 'ghost', // 'ghost', 'outline', 'primary', etc.
  }
})

const emit = defineEmits(['refresh'])

function handleClick() {
  emit('refresh')
}
</script>

<template>
  <button 
    :class="['mhr-btn', `mhr-btn--${variant}`]" 
    @click="handleClick" 
    :disabled="isRefreshing"
    title="Refresh"
  >
    <AppIcon 
      name="refresh" 
      :size="size" 
      :class="{ 'icon-spin': isRefreshing }" 
    />
  </button>
</template>

<style scoped>
.icon-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
