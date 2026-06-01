<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  employees: { type: Array, default: () => [] },
  monthsName: { type: Object, default: () => ({}) },
  years: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  timesheets: { type: Array, default: () => [] },
})

const detailTimesheet = ref(null)
const isRefreshing = ref(false)

// Filters
const searchQuery = ref('')
const filterEvent = ref('')
const filterMonth = ref('')
const filterYear = ref('')
const filterStatus = ref('all')

const filteredTimesheets = computed(() => {
  let result = props.timesheets

  // Filter by search query (employee name or number)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(ts => 
      ts.employeeName.toLowerCase().includes(query) ||
      ts.employeeNumber.toLowerCase().includes(query)
    )
  }

  // Filter by event
  if (filterEvent.value) {
    result = result.filter(ts => ts.eventName === filterEvent.value)
  }

  // Filter by month
  if (filterMonth.value) {
    result = result.filter(ts => ts.monthNumber === Number(filterMonth.value))
  }

  // Filter by year
  if (filterYear.value) {
    result = result.filter(ts => ts.year === Number(filterYear.value))
  }

  // Filter by status
  if (filterStatus.value !== 'all') {
    result = result.filter(ts => ts.statusTitle.toLowerCase() === filterStatus.value.toLowerCase())
  }

  return result
})

const uniqueEvents = computed(() => {
  const events = new Set(props.timesheets.map(ts => ts.eventName).filter(e => e && e !== 'N/A'))
  return Array.from(events).sort()
})

function viewDetails(ts) {
  detailTimesheet.value = ts
}

function closeDetails() {
  detailTimesheet.value = null
}

// Keyboard support - close on Escape
function handleKeydown(e) {
  if (e.key === 'Escape' && detailTimesheet.value) {
    closeDetails()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

function refreshData() {
  isRefreshing.value = true
  router.reload({
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}

function fmtMoney(n) {
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function getStatusClass(statusTitle) {
  const lower = statusTitle.toLowerCase()
  if (lower === 'approved') return 'mhr-badge--success'
  if (lower === 'pending payroll') return 'mhr-badge--info'
  if (lower === 'submitted') return 'mhr-badge--info'
  if (lower === 'rejected') return 'mhr-badge--danger'
  return 'mhr-badge--neutral'
}

function getFirstDayOffset() {
  if (!detailTimesheet.value || !detailTimesheet.value.entries || detailTimesheet.value.entries.length === 0) {
    return 0
  }
  const firstDate = new Date(detailTimesheet.value.year, detailTimesheet.value.monthNumber - 1, 1)
  return firstDate.getDay()
}

function getCellClass(entry) {
  if (entry.isWeekend) return 'calendar-day--weekend'
  if (entry.action === 'W') return 'calendar-day--worked'
  if (entry.action === 'L') return 'calendar-day--leave'
  if (entry.action === 'U') return 'calendar-day--unpaid'
  return ''
}

function getDayTitle(entry) {
  if (entry.isWeekend) return 'Weekend'
  if (entry.action === 'W') return 'Worked'
  if (entry.action === 'L') return 'Leave'
  if (entry.action === 'U') return 'Unpaid Leave'
  return entry.dayName
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">All Timesheets</h1>
        <p class="mhr-page-head__sub">Read-only view of all employee timesheets</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="filter-group">
        <input 
          v-model="searchQuery" 
          type="text" 
          class="mhr-input filter-input"
          placeholder="Search by employee name or number..."
        />
      </div>
      <div class="filter-group">        <select v-model="filterEvent" class="mhr-select filter-select">
          <option value="">All Events</option>
          <option v-for="event in uniqueEvents" :key="event" :value="event">{{ event }}</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="filterMonth" class="mhr-select filter-select">
          <option value="">All Months</option>
          <option value="1">January</option>
          <option value="2">February</option>
          <option value="3">March</option>
          <option value="4">April</option>
          <option value="5">May</option>
          <option value="6">June</option>
          <option value="7">July</option>
          <option value="8">August</option>
          <option value="9">September</option>
          <option value="10">October</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="filterYear" class="mhr-select filter-select">
          <option value="">All Years</option>
          <option value="2026">2026</option>
          <option value="2025">2025</option>
          <option value="2024">2024</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="filterStatus" class="mhr-select filter-select">
          <option value="all">All Statuses</option>
          <option v-for="status in statuses" :key="status.id" :value="status.title.toLowerCase()">{{ status.title }}</option>
        </select>
      </div>
      <div v-if="searchQuery || filterEvent || filterMonth || filterYear || filterStatus !== 'all'" class="filter-clear">
        <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="searchQuery = ''; filterEvent = ''; filterMonth = ''; filterYear = ''; filterStatus = 'all'">
          <AppIcon name="x" :size="14" /> Clear Filters
        </button>
      </div>
    </div>

    <!-- Timesheets Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>EMPLOYEE</th>
              <th>EVENT</th>
              <th>PERIOD</th>
              <th>STATUS</th>
              <th style="text-align:right;">WORKED</th>
              <th style="text-align:right;">LEAVE</th>
              <th style="text-align:right;">UNPAID</th>
              <th style="text-align:right;">PAYMENT</th>
              <th style="width:80px;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredTimesheets.length === 0">
              <td colspan="9" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                <span v-if="searchQuery || filterEvent || filterMonth || filterYear || filterStatus !== 'all'">No timesheets match your filters.</span>
                <span v-else>No timesheets found.</span>
              </td>
            </tr>
            <tr v-for="ts in filteredTimesheets" :key="ts.id" @click="viewDetails(ts)" class="clickable-row">
              <td>
                <div style="font-weight:500;">{{ ts.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);">{{ ts.employeeNumber }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ ts.eventName }}</td>
              <td style="color:var(--mhr-ink-2);">{{ ts.period }}</td>
              <td>
                <span :class="['mhr-badge', getStatusClass(ts.statusTitle)]">{{ ts.statusTitle }}</span>
              </td>
              <td style="text-align:right;">{{ ts.daysWorked }}</td>
              <td style="text-align:right;">{{ ts.leaveTaken }}</td>
              <td style="text-align:right;">{{ ts.unpaidLeave }}</td>
              <td style="text-align:right;font-weight:600;color:var(--mhr-ink);font-family:monospace;">{{ fmtMoney(ts.payment) }}</td>
              <td>
                <button 
                  class="mhr-btn mhr-btn--ghost mhr-btn--sm" 
                  @click.stop="viewDetails(ts)"
                  title="View details"
                >
                  <AppIcon name="eye" :size="14" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detail Panel -->
    <Transition name="slide-panel">
      <div v-if="detailTimesheet" class="detail-panel-backdrop" @click.self="closeDetails">
        <div class="detail-panel">
          <!-- Header -->
          <div class="detail-panel__header">
            <div>
              <h3 class="detail-panel__title">{{ detailTimesheet.employeeName }}</h3>
              <p class="detail-panel__subtitle">{{ detailTimesheet.period }}</p>
            </div>
            <button class="mhr-icon-btn" @click="closeDetails">
              <AppIcon name="x" :size="16" />
            </button>
          </div>

          <!-- Body -->
          <div class="detail-panel__body">
            <!-- Status -->
            <div class="detail-section">
              <span :class="['mhr-badge', getStatusClass(detailTimesheet.statusTitle)]">{{ detailTimesheet.statusTitle }}</span>
            </div>

            <!-- Summary Stats -->
            <div class="detail-section">
              <h4 class="detail-section__label">Summary</h4>
              <div class="stats-grid">
                <div class="stat-card">
                  <div class="stat-card__label">Worked</div>
                  <div class="stat-card__value">{{ detailTimesheet.daysWorked }}</div>
                </div>
                <div class="stat-card">
                  <div class="stat-card__label">Leave</div>
                  <div class="stat-card__value">{{ detailTimesheet.leaveTaken }}</div>
                </div>
                <div class="stat-card">
                  <div class="stat-card__label">Unpaid</div>
                  <div class="stat-card__value">{{ detailTimesheet.unpaidLeave }}</div>
                </div>
                <div class="stat-card stat-card--payment">
                  <div class="stat-card__label">Payment</div>
                  <div class="stat-card__value">{{ fmtMoney(detailTimesheet.payment) }}</div>
                </div>
              </div>
            </div>

            <!-- Employee Details -->
            <div class="detail-section">
              <h4 class="detail-section__label">Employee Details</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-item__label">Employee Number</div>
                  <div class="detail-item__value detail-item__value--mono">{{ detailTimesheet.employeeNumber }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-item__label">Daily Rate</div>
                  <div class="detail-item__value detail-item__value--mono">{{ fmtMoney(detailTimesheet.dailyRate) }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-item__label">Monthly Salary</div>
                  <div class="detail-item__value detail-item__value--mono">{{ fmtMoney(detailTimesheet.salary) }}</div>
                </div>
              </div>
            </div>

            <!-- Calendar -->
            <div v-if="detailTimesheet.entries" class="detail-section">
              <h4 class="detail-section__label">Daily Breakdown</h4>
              
              <!-- Day headers -->
              <div class="calendar-grid calendar-header">
                <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="calendar-header__day">
                  {{ day }}
                </div>
              </div>
              
              <!-- Calendar days -->
              <div class="calendar-grid">
                <div v-for="i in getFirstDayOffset()" :key="'empty-' + i" class="calendar-day calendar-day--empty"></div>
                <div v-for="entry in detailTimesheet.entries" :key="entry.day"
                  :class="['calendar-day', getCellClass(entry)]"
                  :title="getDayTitle(entry)">
                  <div class="calendar-day__number">{{ entry.day }}</div>
                  <div class="calendar-day__name">{{ entry.dayName }}</div>
                </div>
              </div>
              
              <!-- Legend -->
              <div class="calendar-legend">
                <div class="legend-item">
                  <div class="legend-item__color legend-item__color--worked"></div>
                  <span class="legend-item__label">Worked (W)</span>
                </div>
                <div class="legend-item">
                  <div class="legend-item__color legend-item__color--leave"></div>
                  <span class="legend-item__label">Leave (L)</span>
                </div>
                <div class="legend-item">
                  <div class="legend-item__color legend-item__color--unpaid"></div>
                  <span class="legend-item__label">Unpaid (U)</span>
                </div>
                <div class="legend-item">
                  <div class="legend-item__color legend-item__color--weekend"></div>
                  <span class="legend-item__label">Weekend</span>
                </div>
              </div>
            </div>

            <!-- Close Button -->
            <div class="detail-section">
              <button class="mhr-btn mhr-btn--outline" style="width:100%;" @click="closeDetails">
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Filters bar */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  align-items: center;
  flex-wrap: wrap;
}

.filter-group {
  flex: 0 0 auto;
}

.filter-group:first-child {
  flex: 1 1 300px;
}

.filter-input {
  width: 100%;
}

.filter-select {
  min-width: 150px;
}

.filter-clear {
  margin-left: auto;
}

/* Table row clickable */
.clickable-row {
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.clickable-row:hover {
  background-color: var(--mhr-surface) !important;
}

/* Detail panel */
.detail-panel-backdrop {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 999;
  display: flex;
  justify-content: flex-end;
}

.detail-panel {
  position: relative;
  width: 500px;
  max-width: 90vw;
  background: var(--mhr-bg);
  border-left: 1px solid var(--mhr-line);
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.detail-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
  border-bottom: 1px solid var(--mhr-line);
  background: var(--mhr-surface);
  flex-shrink: 0;
}

.detail-panel__title {
  font-size: 18px;
  font-weight: 600;
  color: var(--mhr-ink);
  margin: 0;
}

.detail-panel__subtitle {
  font-size: 14px;
  color: var(--mhr-ink-3);
  margin: 4px 0 0;
}

.detail-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

.detail-section {
  padding: 20px 24px;
  border-bottom: 1px solid var(--mhr-line);
}

.detail-section:last-child {
  border-bottom: none;
}

.detail-section__label {
  font-size: 12px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 12px;
}

/* Icon button */
.mhr-icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: var(--mhr-ink-2);
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.15s ease;
}

.mhr-icon-btn:hover {
  background: var(--mhr-surface-2);
  color: var(--mhr-ink);
}

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.stat-card {
  padding: 16px;
  background: var(--mhr-surface);
  border-radius: 8px;
  border: 1px solid var(--mhr-line);
}

.stat-card--payment {
  background: var(--green-100);
  border-color: var(--green-200);
}

.stat-card__label {
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.stat-card--payment .stat-card__label {
  color: var(--green-800);
}

.stat-card__value {
  font-size: 24px;
  font-weight: 600;
  color: var(--mhr-ink);
  line-height: 1;
}

.stat-card--payment .stat-card__value {
  color: var(--green-800);
}

/* Detail grid */
.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item__label {
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.detail-item__value {
  font-size: 14px;
  font-weight: 500;
  color: var(--mhr-ink-2);
}

.detail-item__value--mono {
  font-family: monospace;
}

/* Calendar */
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
}

.calendar-header {
  margin-bottom: 4px;
}

.calendar-header__day {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  padding: 8px 0;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid var(--mhr-line);
  background: var(--mhr-surface);
  transition: all 0.15s ease;
}

.calendar-day__number {
  font-size: 16px;
  font-weight: 600;
  color: var(--mhr-ink);
  line-height: 1;
}

.calendar-day__name {
  font-size: 10px;
  color: var(--mhr-ink-3);
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.calendar-day--worked {
  background: var(--green-700);
  border-color: transparent;
}

.calendar-day--worked .calendar-day__number,
.calendar-day--worked .calendar-day__name {
  color: white;
}

.calendar-day--leave {
  background: var(--mhr-accent-soft);
  border-color: transparent;
}

.calendar-day--leave .calendar-day__number,
.calendar-day--leave .calendar-day__name {
  color: var(--mhr-accent-ink);
}

.calendar-day--unpaid {
  background: var(--mhr-warn-bg);
  border-color: transparent;
}

.calendar-day--unpaid .calendar-day__number,
.calendar-day--unpaid .calendar-day__name {
  color: var(--mhr-warn);
}

.calendar-day--weekend {
  background: var(--mhr-surface-2);
  border-color: var(--mhr-line);
  opacity: 0.5;
}

.calendar-day--empty {
  background: transparent;
  border: none;
}

/* Calendar legend */
.calendar-legend {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  padding: 16px;
  background: var(--mhr-surface-2);
  border-radius: 8px;
  margin-top: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.legend-item__color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  flex-shrink: 0;
}

.legend-item__color--worked {
  background: var(--green-700);
}

.legend-item__color--leave {
  background: var(--mhr-accent-soft);
}

.legend-item__color--unpaid {
  background: var(--mhr-warn-bg);
}

.legend-item__color--weekend {
  background: var(--mhr-surface-2);
  border: 1px solid var(--mhr-line);
}

.legend-item__label {
  font-size: 13px;
  color: var(--mhr-ink-2);
  font-weight: 500;
}

/* Transitions */
.slide-panel-enter-active,
.slide-panel-leave-active {
  transition: background-color 0.3s ease;
}

.slide-panel-enter-active .detail-panel,
.slide-panel-leave-active .detail-panel {
  transition: transform 0.3s ease;
}

.slide-panel-enter-from,
.slide-panel-leave-to {
  background: rgba(0, 0, 0, 0);
}

.slide-panel-enter-from .detail-panel,
.slide-panel-leave-to .detail-panel {
  transform: translateX(100%);
}
</style>
