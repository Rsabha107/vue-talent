<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import EmployeeEventAssignment from '@/Components/MeridianHR/EmployeeEventAssignment.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:             { type: String, default: 'admin' },
  event:              { type: Object, required: true },
  assignedEmployees:  { type: Array,  default: () => [] },
  availableEmployees: { type: Array,  default: () => [] },
})

const isRefreshing = ref(false)

function refresh() {
  isRefreshing.value = true
  router.reload({
    only: ['assignedEmployees', 'availableEmployees'],
    onFinish: () => setTimeout(() => { isRefreshing.value = false }, 300),
  })
}

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s.length === 10 ? s + 'T00:00:00' : s)
    .toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div>
    <!-- Back link -->
    <Link :href="route('hr.events')" class="mhr-btn mhr-btn--ghost mhr-btn--sm" style="margin-bottom:20px;">
      <AppIcon name="chevronLeft" :size="15" />
      Back to Events
    </Link>

    <!-- Page header -->
    <div class="event-hero mhr-card" style="margin-bottom:20px;">
      <!-- Logo / placeholder -->
      <div class="event-hero__logo">
        <img
          v-if="event.logoUrl"
          :src="event.logoUrl"
          :alt="event.name"
          style="width:100%;height:100%;object-fit:cover;"
        />
        <AppIcon v-else name="calendar" :size="28" style="opacity:0.35;" />
      </div>

      <!-- Title + meta -->
      <div class="event-hero__body">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
          <h1 style="font-size:22px;font-weight:700;color:var(--mhr-ink);line-height:1.2;">
            {{ event.name }}
          </h1>
          <span
            class="mhr-badge"
            :class="event.statusName?.toLowerCase() === 'active' ? 'mhr-badge--success' : 'mhr-badge--neutral'"
          >{{ event.statusName }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <span v-if="event.venues?.length" style="display:flex;align-items:center;gap:5px;font-size:13px;color:var(--mhr-ink-3);">
            <AppIcon name="mapPin" :size="13" />
            {{ event.venues.length }} venue{{ event.venues.length !== 1 ? 's' : '' }}
          </span>
          <span style="display:flex;align-items:center;gap:5px;font-size:13px;color:var(--mhr-ink-3);">
            <AppIcon name="users" :size="13" />
            {{ assignedEmployees.length }} team member{{ assignedEmployees.length !== 1 ? 's' : '' }}
          </span>
          <span style="display:flex;align-items:center;gap:5px;font-size:13px;color:var(--mhr-ink-3);">
            <AppIcon name="calendar" :size="13" />
            Created {{ fmtDate(event.createdAt) }}
          </span>
        </div>
      </div>

      <!-- Refresh -->
      <button
        @click="refresh"
        class="mhr-icon-btn"
        style="width:32px;height:32px;align-self:flex-start;"
        :disabled="isRefreshing"
        title="Refresh data"
      >
        <AppIcon name="refresh" :size="14" :class="{ 'spin': isRefreshing }" />
      </button>
    </div>

    <!-- Info strip -->
    <div class="mhr-card" style="margin-bottom:20px;">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));divide-x:1px solid var(--mhr-line);">
        <!-- Status -->
        <div class="info-stat">
          <div class="info-stat__icon" style="background:var(--mhr-accent-soft);color:var(--mhr-accent);">
            <AppIcon name="check" :size="14" />
          </div>
          <div>
            <div class="info-stat__label">Status</div>
            <span
              class="mhr-badge"
              :class="event.statusName?.toLowerCase() === 'active' ? 'mhr-badge--success' : 'mhr-badge--neutral'"
            >{{ event.statusName }}</span>
          </div>
        </div>

        <!-- Venues -->
        <div class="info-stat" v-if="event.venues?.length">
          <div class="info-stat__icon" style="background:var(--mhr-info-bg);color:var(--mhr-info);">
            <AppIcon name="mapPin" :size="14" />
          </div>
          <div>
            <div class="info-stat__label">Venues</div>
            <div style="font-size:13px;color:var(--mhr-ink);">
              <div v-for="v in event.venues" :key="v.id" style="line-height:1.7;">{{ v.title }}</div>
            </div>
          </div>
        </div>

        <!-- Team size -->
        <div class="info-stat">
          <div class="info-stat__icon" style="background:var(--mhr-warn-bg);color:var(--mhr-warn);">
            <AppIcon name="users" :size="14" />
          </div>
          <div>
            <div class="info-stat__label">Team Size</div>
            <div style="font-size:20px;font-weight:700;color:var(--mhr-ink);line-height:1.2;">
              {{ assignedEmployees.length }}
            </div>
          </div>
        </div>

        <!-- Created -->
        <div class="info-stat">
          <div class="info-stat__icon" style="background:var(--mhr-surface-2);color:var(--mhr-ink-3);">
            <AppIcon name="calendar" :size="14" />
          </div>
          <div>
            <div class="info-stat__label">Created</div>
            <div style="font-size:13px;color:var(--mhr-ink);">{{ fmtDate(event.createdAt) }}</div>
          </div>
        </div>

        <!-- Last updated (only if different) -->
        <div v-if="event.updatedAt && event.updatedAt !== event.createdAt" class="info-stat">
          <div class="info-stat__icon" style="background:var(--mhr-surface-2);color:var(--mhr-ink-3);">
            <AppIcon name="refresh" :size="14" />
          </div>
          <div>
            <div class="info-stat__label">Last Updated</div>
            <div style="font-size:13px;color:var(--mhr-ink);">{{ fmtDate(event.updatedAt) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Team -->
    <EmployeeEventAssignment
      :event="event"
      :assigned-employees="assignedEmployees"
      :available-employees="availableEmployees"
      @refresh="refresh"
    />
  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
.spin { animation: spin 1s linear infinite; }

/* Hero header */
.event-hero {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px 24px;
}
.event-hero__logo {
  width: 56px;
  height: 56px;
  flex-shrink: 0;
  border-radius: var(--mhr-r);
  border: 1px solid var(--mhr-line);
  background: var(--mhr-surface-2);
  display: grid;
  place-items: center;
  overflow: hidden;
  color: var(--mhr-ink-3);
}
.event-hero__body {
  flex: 1;
  min-width: 0;
}

/* Info stat cells */
.info-stat {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  border-right: 1px solid var(--mhr-line);
}
.info-stat:last-child {
  border-right: none;
}
.info-stat__icon {
  width: 32px;
  height: 32px;
  border-radius: var(--mhr-r-sm);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.info-stat__label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  margin-bottom: 4px;
}
</style>
