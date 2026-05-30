<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import BaseModuleLayout from './BaseModuleLayout.vue'
import { useHRNavigation } from '@/Composables/useHRNavigation'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

const page = usePage()
const { navigation, pageTitle, breadcrumbGroup, hrPage } = useHRNavigation()

// Event management
const availableEvents = computed(() => page.props.availableEvents || [])
const selectedEvent = computed(() => page.props.selectedEvent || null)
const eventSelectorOpen = ref(false)
const eventSelectorRef = ref(null)

// User and role info
const auth = computed(() => page.props.auth || {})
const hrRole = computed(() => page.props.hrRole || 'employee')
const isEmployee = computed(() => !['admin', 'manager'].includes(hrRole.value))
const isAdmin = computed(() => hrRole.value === 'admin')
const isManager = computed(() => hrRole.value === 'manager')

const selectedEventData = computed(() => {
  if (!selectedEvent.value) return null
  return availableEvents.value.find(e => e.id === selectedEvent.value) || null
})

const activeEventLabel = computed(() => {
  if (!selectedEvent.value) {
    if (isAdmin.value) return 'All Events'
    if (isManager.value) return 'All My Events'
    return 'Select Event'
  }
  const ev = availableEvents.value.find(e => e.id === selectedEvent.value)
  if (ev) return ev.name
  if (isAdmin.value) return 'All Events'
  if (isManager.value) return 'All My Events'
  return 'Select Event'
})

// Browser title
const browserTitle = computed(() => {
  const base = pageTitle.value
  return base === 'Home' ? 'Meridian HR' : `${base} · Meridian HR`
})

// Event selector management
function selectEvent(eventId) {
  eventSelectorOpen.value = false
  if (!eventId) {
    // Admin and manager can clear event selection
    if (isEmployee.value) return
    const message = isAdmin.value ? 'Showing all events' : 'Showing all your events'
    router.post(route('event.clear'), {}, {
      preserveScroll: true,
      onSuccess: () => {
        // Toast handled by BaseModuleLayout
      },
    })
  } else {
    router.post(route('event.select'), { event_id: eventId }, {
      preserveScroll: true,
      onSuccess: () => {
        // Toast handled by BaseModuleLayout
      },
    })
  }
}

// Close event selector on outside click
function closeEventSelector(e) {
  if (eventSelectorRef.value && !eventSelectorRef.value.contains(e.target)) {
    eventSelectorOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeEventSelector)
  
  // Auto-select first event for employees and managers if no event selected and they have only one event
  if ((isEmployee.value || isManager.value) && !selectedEvent.value && availableEvents.value.length === 1) {
    selectEvent(availableEvents.value[0].id)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', closeEventSelector)
})
</script>

<template>
  <BaseModuleLayout
    module-key="hr"
    module-name="HR"
    module-icon="users"
    :navigation="navigation"
    :page-title="browserTitle"
    :breadcrumb-group="breadcrumbGroup"
    :current-page="hrPage"
  >
    <!-- Event Selector in Topbar -->
    <template #topbar>
      <div v-if="availableEvents.length > 0" class="event-selector" ref="eventSelectorRef">
        <button class="event-selector-btn" @click="eventSelectorOpen = !eventSelectorOpen">
          <AppIcon name="award" :size="14" />
          <span class="event-selector-label">{{ activeEventLabel }}</span>
          <svg 
            class="event-selector-chevron" 
            :class="{ 'rotated': eventSelectorOpen }" 
            width="12" 
            height="12" 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            stroke-width="2.5"
          >
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </button>

        <transition name="dropdown">
          <div v-if="eventSelectorOpen" class="event-dropdown">
            <div class="event-dropdown-header">Switch Event</div>
            <button
              v-if="!isEmployee"
              class="event-dropdown-item"
              :class="{ 'event-dropdown-item--active': !selectedEvent }"
              @click="selectEvent(null)"
            >
              <span class="event-dropdown-name">{{ isAdmin ? 'All Events' : 'All My Events' }}</span>
              <svg v-if="!selectedEvent" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M20 6L9 17l-5-5"/>
              </svg>
            </button>
            <button
              v-for="ev in availableEvents"
              :key="ev.id"
              class="event-dropdown-item"
              :class="{ 'event-dropdown-item--active': ev.id === selectedEvent }"
              @click="selectEvent(ev.id)"
            >
              <span class="event-dropdown-name">{{ ev.name }}</span>
              <svg v-if="ev.id === selectedEvent" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M20 6L9 17l-5-5"/>
              </svg>
            </button>
            <div v-if="!availableEvents.length" class="event-dropdown-empty">No events</div>
          </div>
        </transition>
      </div>
    </template>

    <!-- Main Content -->
    <slot />
  </BaseModuleLayout>
</template>

<style scoped>
/* Event Selector Styling */
.event-selector {
  position: relative;
  margin-right: 16px;
}

.event-selector-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  color: var(--mhr-ink);
  cursor: pointer;
  transition: all 0.15s ease;
}

.event-selector-btn:hover {
  border-color: var(--mhr-accent);
  background: var(--mhr-accent-soft);
}

.event-selector-label {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.event-selector-chevron {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.event-selector-chevron.rotated {
  transform: rotate(180deg);
}

.event-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  min-width: 260px;
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
  overflow: hidden;
}

.event-dropdown-header {
  padding: 10px 14px;
  font-size: 12px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--mhr-line);
}

.event-dropdown-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 10px 14px;
  font-size: 13px;
  color: var(--mhr-ink);
  background: none;
  border: none;
  cursor: pointer;
  transition: background 0.15s ease;
  text-align: left;
}


.event-dropdown-item:hover {
  background: var(--mhr-bg);
}

.event-dropdown-item--active {
  background: var(--mhr-accent-soft);
  color: var(--mhr-accent);
  font-weight: 500;
}

.event-dropdown-name {
  flex: 1;
}

.event-dropdown-empty {
  padding: 20px;
  text-align: center;
  font-size: 13px;
  color: var(--mhr-ink-3);
}

/* Dropdown animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (max-width: 768px) {
  .event-selector {
    margin-right: 8px;
  }
  
  .event-selector-label {
    max-width: 120px;
  }
  
  .event-dropdown {
    min-width: 200px;
    right: auto;
    left: 0;
  }
}
</style>
