<script setup>
import { router } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  events: { type: Array, default: () => [] },
  venues: { type: Array, default: () => [] },
  leaveTypes: { type: Array, default: () => [] },
  lookupCounts: { type: Object, default: () => ({}) },
})

const setupCards = [
  {
    key: 'events',
    title: 'Events',
    description: 'Manage organizational events, assign venues, and track employee assignments',
    icon: 'calendar',
    iconColor: 'var(--mhr-accent)',
    count: () => props.events.length,
    route: 'hr.events',
  },
  {
    key: 'venues',
    title: 'Venues',
    description: 'Configure venue locations and facility details for your events',
    icon: 'pin',
    iconColor: 'var(--mhr-brand)',
    count: () => props.venues.length,
    route: 'hr.venues',
  },
  {
    key: 'leave-types',
    title: 'Leave Types',
    description: 'Define leave categories, accrual rules, and eligibility criteria',
    icon: 'calendar',
    iconColor: '#f59e0b',
    count: () => props.leaveTypes.length,
    route: 'hr.leave-types',
  },
  // Lookup Tables
  {
    key: 'departments',
    title: 'Departments',
    description: 'Organizational departments and units',
    icon: 'briefcase',
    iconColor: '#10b981',
    count: () => props.lookupCounts.departments || 0,
    route: 'hr.lookup',
    routeParams: { type: 'departments' },
  },
  {
    key: 'designations',
    title: 'Job Titles',
    description: 'Employee job titles and positions',
    icon: 'award',
    iconColor: '#3b82f6',
    count: () => props.lookupCounts.designations || 0,
    route: 'hr.lookup',
    routeParams: { type: 'designations' },
  },
  {
    key: 'contract-types',
    title: 'Contract Types',
    description: 'Employment contract categories',
    icon: 'file-text',
    iconColor: '#8b5cf6',
    count: () => props.lookupCounts['contract-types'] || 0,
    route: 'hr.lookup',
    routeParams: { type: 'contract-types' },
  },
  {
    key: 'genders',
    title: 'Genders',
    description: 'Gender options for employee records',
    icon: 'users',
    iconColor: '#ec4899',
    count: () => props.lookupCounts.genders || 0,
    route: 'hr.lookup',
    routeParams: { type: 'genders' },
  },
  {
    key: 'marital-statuses',
    title: 'Marital Statuses',
    description: 'Marital status options',
    icon: 'heart',
    iconColor: '#f59e0b',
    count: () => props.lookupCounts['marital-statuses'] || 0,
    route: 'hr.lookup',
    routeParams: { type: 'marital-statuses' },
  },
  {
    key: 'relationships',
    title: 'Relationships',
    description: 'Family relationship types for emergency contacts',
    icon: 'link',
    iconColor: '#06b6d4',
    count: () => props.lookupCounts.relationships || 0,
    route: 'hr.lookup',
    routeParams: { type: 'relationships' },
  },
  {
    key: 'directorates',
    title: 'Directorates',
    description: 'Organizational directorates',
    icon: 'building',
    iconColor: '#0891b2',
    count: () => props.lookupCounts.directorates || 0,
    route: 'hr.lookup',
    routeParams: { type: 'directorates' },
  },
  {
    key: 'salary-basis',
    title: 'Salary Basis',
    description: 'Salary calculation basis (hourly, monthly, etc.)',
    icon: 'dollar',
    iconColor: '#059669',
    count: () => props.lookupCounts['salary-basis'] || 0,
    route: 'hr.lookup',
    routeParams: { type: 'salary-basis' },
  },
  {
    key: 'countries',
    title: 'Countries',
    description: 'Country list with ISO codes',
    icon: 'globe',
    iconColor: '#6366f1',
    count: () => props.lookupCounts.countries || 0,
    route: 'hr.lookup',
    routeParams: { type: 'countries' },
  },
  {
    key: 'nationalities',
    title: 'Nationalities',
    description: 'Nationality options for employees',
    icon: 'flag',
    iconColor: '#d946ef',
    count: () => props.lookupCounts.nationalities || 0,
    route: 'hr.lookup',
    routeParams: { type: 'nationalities' },
  },
  {
    key: 'sponsorships',
    title: 'Sponsorships',
    description: 'Employee sponsorship types',
    icon: 'shield',
    iconColor: '#14b8a6',
    count: () => props.lookupCounts.sponsorships || 0,
    route: 'hr.lookup',
    routeParams: { type: 'sponsorships' },
  },
  {
    key: 'entities',
    title: 'Entities',
    description: 'Legal entities or organizational units',
    icon: 'layers',
    iconColor: '#f97316',
    count: () => props.lookupCounts.entities || 0,
    route: 'hr.lookup',
    routeParams: { type: 'entities' },
  },
  {
    key: 'salutations',
    title: 'Salutations',
    description: 'Title prefixes (Mr., Mrs., Dr., etc.)',
    icon: 'user',
    iconColor: '#a855f7',
    count: () => props.lookupCounts.salutations || 0,
    route: 'hr.lookup',
    routeParams: { type: 'salutations' },
  },
  {
    key: 'address-types',
    title: 'Address Types',
    description: 'Address category types (Home, Work, etc.)',
    icon: 'map-pin',
    iconColor: '#ef4444',
    count: () => props.lookupCounts['address-types'] || 0,
    route: 'hr.lookup',
    routeParams: { type: 'address-types' },
  },
  {
    key: 'pay-periods',
    title: 'Pay Cycles',
    description: 'Payment frequency cycles (Weekly, Monthly, etc.)',
    icon: 'calendar',
    iconColor: '#84cc16',
    count: () => props.lookupCounts['pay-periods'] || 0,
    route: 'hr.lookup',
    routeParams: { type: 'pay-periods' },
  },
  {
    key: 'invoice-notes',
    title: 'Invoice Notes',
    description: 'Standard notes for invoices',
    icon: 'file-text',
    iconColor: '#64748b',
    count: () => props.lookupCounts['invoice-notes'] || 0,
    route: 'hr.lookup',
    routeParams: { type: 'invoice-notes' },
  },
  {
    key: 'settings',
    title: 'Application Settings',
    description: 'Configure system preferences and application settings',
    icon: 'settings',
    iconColor: '#ec4899',
    count: () => 0,
    route: 'hr.settings',
    highlight: true,
  },
]

function navigateTo(routeName, params = null) {
  if (params) {
    router.visit(route(routeName, params))
  } else {
    router.visit(route(routeName))
  }
}
</script>

<template>
  <div class="setup-hub">
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Setup</h1>
        <p class="mhr-page-head__sub">Configure organizational settings and resources</p>
      </div>
    </div>

    <!-- Cards Grid -->
    <div class="setup-grid">
      <div v-for="card in setupCards" :key="card.key" class="setup-card" :class="{ 'setup-card--highlight': card.highlight }">
        <div class="setup-card__icon" :style="{ color: card.iconColor }">
          <AppIcon :name="card.icon" :size="22" />
        </div>
        <div class="setup-card__content">
          <h2 class="setup-card__title">{{ card.title }}</h2>
          <p class="setup-card__desc">{{ card.description }}</p>
        </div>
        <div class="setup-card__footer">
          <div class="setup-card__meta">
            <span class="setup-card__count">{{ card.count() }} {{ card.count() === 1 ? 'item' : 'items' }}</span>
          </div>
          <div class="setup-card__actions">
            <button class="mhr-btn mhr-btn--primary mhr-btn--sm" @click="navigateTo(card.route, card.routeParams)">
              <AppIcon name="settings" :size="13" />
              Manage
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Setup Hub Layout */
.setup-hub {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Cards Grid */
.setup-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 14px;
  padding-bottom: var(--mhr-gap);
}

/* Setup Card */
.setup-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.setup-card:hover {
  border-color: var(--mhr-accent);
  box-shadow: 0 2px 8px rgba(59,111,67,0.08);
}

.setup-card--highlight {
  background: #fdf2f8;
  border-color: #fce7f3;
}

.setup-card--highlight:hover {
  border-color: #ec4899;
  box-shadow: 0 2px 8px rgba(236,72,153,0.12);
}

/* Icon Section */
.setup-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: var(--mhr-r);
  background: var(--mhr-surface-2);
  flex-shrink: 0;
}

/* Content Section */
.setup-card__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.setup-card__title {
  font-size: 15px;
  font-weight: 600;
  color: var(--mhr-ink);
  margin: 0;
}

.setup-card__desc {
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--mhr-ink-3);
  margin: 0;
}

/* Footer Section */
.setup-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.setup-card__meta {
  display: flex;
  align-items: center;
  gap: 10px;
}

.setup-card__count {
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  background: var(--mhr-line-2);
  padding: 3px 8px;
  flex-shrink: 0;
  border-radius: 10px;
}

/* Actions Section */
.setup-card__actions {
  display: flex;
  gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .setup-grid {
    grid-template-columns: 1fr;
  }
}
</style>
