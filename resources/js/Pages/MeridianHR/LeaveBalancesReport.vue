<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:             { type: String, default: 'admin' },
  balances:           { type: Array,  default: () => [] },
  leaveTypes:         { type: Array,  default: () => [] },
  selectedYear:       { type: Number, default: () => new Date().getFullYear() },
  selectedLeaveType:  { type: Number, default: null },
})

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December']
const dateFormat = computed(() => usePage().props.dateFormat || 'DD/MM/YYYY')

function applyFormat(iso, fmt) {
  if (!iso) return '—'
  const d = new Date(iso + 'T00:00:00')
  const pad = n => String(n).padStart(2, '0')
  return fmt.replace(/YYYY|YY|MMMM|MMM|MM|M|DD|D/g, t => ({
    YYYY: d.getFullYear(),
    YY:   String(d.getFullYear()).slice(-2),
    MMMM: MONTHS[d.getMonth()],
    MMM:  MONTHS[d.getMonth()].slice(0, 3),
    MM:   pad(d.getMonth() + 1),
    M:    d.getMonth() + 1,
    DD:   pad(d.getDate()),
    D:    d.getDate(),
  }[t]))
}

const fmt = iso => applyFormat(iso, dateFormat.value)

// Filters
const q            = ref('')
const yearFilter   = ref(props.selectedYear)
const typeFilter   = ref(props.selectedLeaveType ?? '')

// Year range: current year and 2 prior
const currentYear  = new Date().getFullYear()
const yearOptions  = [currentYear, currentYear - 1, currentYear - 2]

const filtered = computed(() => {
  const term = q.value.toLowerCase()
  return props.balances.filter(b => {
    const matchSearch = !term ||
      (b.employeeName  || '').toLowerCase().includes(term) ||
      (b.employeeNumber|| '').toLowerCase().includes(term)
    const matchType = !typeFilter.value || b.leaveTypeId === Number(typeFilter.value)
    return matchSearch && matchType
  })
})

// Summary totals from filtered rows
const totals = computed(() => filtered.value.reduce((acc, b) => {
  acc.allocated  += b.allocatedDays
  acc.used       += b.usedDays
  acc.pending    += b.pendingDays
  acc.available  += b.availableDays
  return acc
}, { allocated: 0, used: 0, pending: 0, available: 0 }))

function applyFilters() {
  router.get(route('hr.leave-balances'), {
    year:          yearFilter.value,
    leave_type_id: typeFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function fmt2(n) {
  return Number(n).toFixed(1).replace(/\.0$/, '')
}

const recalcForm = useForm({})
const showRecalcModal = ref(false)

function confirmRecalculate() {
  recalcForm.post(route('hr.leave-balances.recalculate'), {
    onFinish: () => { showRecalcModal.value = false },
  })
}

function exportCsv() {
  const headers = [
    'Employee', 'Employee #', 'Leave Type',
    'Allocated', 'Used', 'Pending', 'Available',
    'Year', 'Period Start', 'Period End',
  ]

  const escape = v => {
    const s = String(v ?? '')
    return s.includes(',') || s.includes('"') || s.includes('\n')
      ? `"${s.replace(/"/g, '""')}"`
      : s
  }

  const rows = filtered.value.map(b => [
    b.employeeName   ?? '',
    b.employeeNumber ?? '',
    b.leaveTypeName  ?? '',
    fmt2(b.allocatedDays),
    fmt2(b.usedDays),
    fmt2(b.pendingDays),
    fmt2(b.availableDays),
    b.year,
    fmt(b.periodStart),
    fmt(b.periodEnd),
  ].map(escape).join(','))

  const csv  = [headers.join(','), ...rows].join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `leave-balances-${yearFilter.value}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="lb-report">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="lb-header">
      <div class="lb-header__left">
        <h1 class="lb-title">Leave Balances</h1>
        <p class="lb-subtitle">Paid leave allocation, usage, and availability per employee</p>
      </div>
      <div class="lb-header__actions">
        <button
          class="mhr-btn mhr-btn--ghost lb-export-btn"
          :disabled="recalcForm.processing"
          @click="showRecalcModal = true"
        >
          <AppIcon name="refresh" :size="16" />
          Recalculate all
        </button>
        <button
          class="mhr-btn mhr-btn--outline lb-export-btn"
          :disabled="filtered.length === 0"
          @click="exportCsv"
        >
          <AppIcon name="download" :size="16" />
          Export CSV
        </button>
      </div>
    </div>

    <!-- ── Filter bar ─────────────────────────────────────────── -->
    <div class="mhr-card lb-filters">
      <div class="lb-filters__row">
        <!-- Search -->
        <div class="mhr-field lb-search">
          <AppIcon name="search" :size="16" class="lb-search__icon" />
          <input
            v-model="q"
            class="mhr-input lb-search__input"
            placeholder="Search employee…"
            type="search"
          />
        </div>

        <!-- Year -->
        <div class="mhr-field">
          <label class="lb-label">Year</label>
          <select v-model="yearFilter" class="mhr-select" @change="applyFilters">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>

        <!-- Leave type -->
        <div class="mhr-field">
          <label class="lb-label">Leave type</label>
          <select v-model="typeFilter" class="mhr-select" @change="applyFilters">
            <option value="">All paid types</option>
            <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.title }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ── Summary tiles ──────────────────────────────────────── -->
    <div class="lb-tiles">
      <div class="lb-tile">
        <span class="lb-tile__label">Employees</span>
        <span class="lb-tile__value">{{ filtered.length }}</span>
      </div>
      <div class="lb-tile">
        <span class="lb-tile__label">Total Allocated</span>
        <span class="lb-tile__value">{{ fmt2(totals.allocated) }}</span>
      </div>
      <div class="lb-tile lb-tile--warn">
        <span class="lb-tile__label">Total Used</span>
        <span class="lb-tile__value">{{ fmt2(totals.used) }}</span>
      </div>
      <div class="lb-tile lb-tile--muted">
        <span class="lb-tile__label">Total Pending</span>
        <span class="lb-tile__value">{{ fmt2(totals.pending) }}</span>
      </div>
      <div class="lb-tile lb-tile--success">
        <span class="lb-tile__label">Total Available</span>
        <span class="lb-tile__value">{{ fmt2(totals.available) }}</span>
      </div>
    </div>

    <!-- ── Table ─────────────────────────────────────────────── -->
    <div class="mhr-card lb-table-wrap">
      <div v-if="filtered.length === 0" class="lb-empty">
        <AppIcon name="inbox" :size="32" />
        <p>No leave balance records found</p>
      </div>

      <table v-else class="mhr-table lb-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Emp #</th>
            <th>Leave Type</th>
            <th class="num">Allocated</th>
            <th class="num">Used</th>
            <th class="num">Pending</th>
            <th class="num">Available</th>
            <th class="num">Year</th>
            <th>Period Start</th>
            <th>Period End</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in filtered" :key="b.id">
            <td class="lb-name">{{ b.employeeName || '—' }}</td>
            <td class="lb-num-id">{{ b.employeeNumber || '—' }}</td>
            <td>
              <span class="mhr-badge mhr-badge--neutral lb-type-pill">{{ b.leaveTypeName }}</span>
            </td>
            <td class="num">{{ fmt2(b.allocatedDays) }}</td>
            <td class="num lb-used">{{ fmt2(b.usedDays) }}</td>
            <td class="num lb-pending">{{ fmt2(b.pendingDays) }}</td>
            <td class="num" :class="b.availableDays <= 0 ? 'lb-zero' : 'lb-avail'">
              {{ fmt2(b.availableDays) }}
            </td>
            <td class="num">{{ b.year }}</td>
            <td>{{ fmt(b.periodStart) }}</td>
            <td>{{ fmt(b.periodEnd) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Recalculate confirmation modal ────────────────────── -->
    <div v-if="showRecalcModal" class="mhr-modal__scrim" @click.self="showRecalcModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Recalculate leave balances</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);">
            This will recalculate allocated, used, pending, and available days for
            <strong>all employees</strong> across all active leave types.
            Existing values will be overwritten. This may take a moment.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button
            class="mhr-btn mhr-btn--ghost"
            :disabled="recalcForm.processing"
            @click="showRecalcModal = false"
          >Cancel</button>
          <button
            class="mhr-btn mhr-btn--primary"
            :disabled="recalcForm.processing"
            @click="confirmRecalculate"
          >
            <AppIcon v-if="recalcForm.processing" name="refresh" :size="15" class="lb-spin" />
            {{ recalcForm.processing ? 'Recalculating…' : 'Yes, recalculate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.lb-report {
  display: flex;
  flex-direction: column;
  gap: var(--mhr-gap);
  padding: var(--mhr-pad);
}

/* Header */
.lb-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--mhr-gap);
}
.lb-header__actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-shrink: 0;
}
.lb-export-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.lb-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--mhr-ink);
  margin: 0;
}
.lb-subtitle {
  font-size: 0.8125rem;
  color: var(--mhr-ink-3);
  margin: 2px 0 0;
}

/* Filter bar */
.lb-filters {
  padding: var(--mhr-pad);
}
.lb-filters__row {
  display: flex;
  gap: var(--mhr-gap);
  flex-wrap: wrap;
  align-items: flex-end;
}
.lb-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--mhr-ink-3);
  margin-bottom: 4px;
}
.lb-search {
  position: relative;
  flex: 1;
  min-width: 200px;
}
.lb-search__icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--mhr-ink-3);
  pointer-events: none;
}
.lb-search__input {
  padding-left: 32px;
  width: 100%;
}

/* Summary tiles */
.lb-tiles {
  display: flex;
  gap: var(--mhr-gap);
  flex-wrap: wrap;
}
.lb-tile {
  flex: 1;
  min-width: 130px;
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r);
  padding: 14px 18px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.lb-tile__label {
  font-size: 0.75rem;
  color: var(--mhr-ink-3);
  font-weight: 500;
}
.lb-tile__value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--mhr-ink);
  line-height: 1;
}
.lb-tile--warn .lb-tile__value  { color: var(--mhr-warn); }
.lb-tile--success .lb-tile__value { color: var(--mhr-accent); }
.lb-tile--muted .lb-tile__value { color: var(--mhr-ink-3); }

/* Table */
.lb-table-wrap {
  overflow-x: auto;
  padding: 0;
}
.lb-table {
  width: 100%;
  min-width: 860px;
}
.lb-table th,
.lb-table td {
  white-space: nowrap;
}
.lb-table th.num,
.lb-table td.num {
  text-align: right;
}
.lb-name    { font-weight: 500; color: var(--mhr-ink); }
.lb-num-id  { color: var(--mhr-ink-3); font-size: 0.8125rem; }
.lb-type-pill { font-size: 0.75rem; }
.lb-used    { color: var(--mhr-warn); font-weight: 500; }
.lb-pending { color: var(--mhr-ink-3); }
.lb-avail   { color: var(--mhr-accent); font-weight: 600; }
.lb-zero    { color: var(--mhr-danger); font-weight: 600; }

/* Empty state */
.lb-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 48px;
  color: var(--mhr-ink-3);
  font-size: 0.875rem;
}
.lb-empty p { margin: 0; }

@keyframes spin { to { transform: rotate(360deg); } }
.lb-spin { animation: spin 0.8s linear infinite; }
</style>
