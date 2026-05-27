<template>
  <div
    v-if="eventData"
    class="mhr-card event-banner"
  >
    <div class="event-banner__content">
      <div
        v-if="eventData.logo"
        class="event-banner__logo-container"
      >
        <img
          :src="eventData.logo"
          :alt="eventData.name"
          class="event-banner__logo"
        />
      </div>
      <AppIcon
        v-else
        name="calendar"
        :size="14"
        class="event-banner__icon"
      />
      <div class="event-banner__text">
        <div class="event-banner__label">{{ label }}</div>
        <div class="event-banner__name">{{ eventData.name }}</div>
      </div>
      <AppIcon name="check" :size="14" class="event-banner__check" />
    </div>
  </div>
</template>

<script setup>
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineProps({
  eventData: {
    type: Object,
    default: null,
    validator: (value) => {
      if (!value) return true
      return typeof value.name === 'string'
    }
  },
  label: {
    type: String,
    default: 'Viewing Event'
  }
})
</script>

<style scoped>
.event-banner {
  margin-bottom: 12px;
  background: linear-gradient(135deg, var(--green-700), var(--green-800));
  color: #fff;
  border: none;
  padding: 8px 12px;
}

.event-banner__content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.event-banner__logo-container {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.15);
  padding: 3px;
  flex-shrink: 0;
}

.event-banner__logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.event-banner__icon {
  opacity: 0.8;
  flex-shrink: 0;
}

.event-banner__text {
  flex: 1;
  min-width: 0;
}

.event-banner__label {
  font-size: 10px;
  opacity: 0.75;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
  line-height: 1;
}

.event-banner__name {
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}

.event-banner__check {
  opacity: 0.75;
  flex-shrink: 0;
}
</style>
