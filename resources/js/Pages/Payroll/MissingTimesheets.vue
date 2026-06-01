<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  missingTimesheets: { type: Array, default: () => [] },
  selectedMonth: { type: Number, default: () => new Date().getMonth() + 1 },
  selectedYear: { type: Number, default: () => new Date().getFullYear() },
})

const isRefreshing = ref(false)

// Period filters (trigger backend reload)
const periodMonth = ref(props.selectedMonth)
const periodYear = ref(props.selectedYear)

// Client-side filters
const searchQuery = ref('')
const filterEvent = ref('')
const filterDepartment = ref('')
const filterDesignation = ref('')

const filteredTimesheets = computed(() => {
  let result = props.missingTimesheets

  // Filter by search query (employee name, email, or number)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(emp => 
      emp.fullName.toLowerCase().includes(query) ||
      emp.email.toLowerCase().includes(query) ||
      emp.employeeNumber.toLowerCase().includes(query)
    )
  }

  // Filter by event
  if (filterEvent.value) {
    result = result.filter(emp => emp.events && emp.events.includes(filterEvent.value))
  }

  // Filter by department
  if (filterDepartment.value) {
    result = result.filter(emp => emp.department === filterDepartment.value)
  }

  // Filter by designation
  if (filterDesignation.value) {
    result = result.filter(emp => emp.designation === filterDesignation.value)
  }

  return result
})

const uniqueEvents = computed(() => {
  const events = new Set()
  props.missingTimesheets.forEach(emp => {
    if (emp.events && emp.events.length > 0) {
      emp.events.forEach(event => events.add(event))
    }
  })
  return Array.from(events).sort()
})

const uniqueDepartments = computed(() => {
  const depts = new Set(props.missingTimesheets.map(emp => emp.department).filter(Boolean))
  return Array.from(depts).sort()
})

const uniqueDesignations = computed(() => {
  const desigs = new Set(props.missingTimesheets.map(emp => emp.designation).filter(Boolean))
  return Array.from(desigs).sort()
})

// Month and year options
const months = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const yearList = []
  // Show last 3 years
  for (let i = 0; i <= 2; i++) {
    yearList.push(currentYear - i)
  }
  return yearList
})

const clearFilters = () => {
  searchQuery.value = ''
  filterEvent.value = ''
  filterDepartment.value = ''
  filterDesignation.value = ''
}

function changePeriod() {
  isRefreshing.value = true
  router.get(route('payroll.timesheets.missing'), {
    month: periodMonth.value,
    year: periodYear.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}

function refreshData() {
  isRefreshing.value = true
  router.get(route('payroll.timesheets.missing'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Missing Timesheets</h1>
        <p class="mhr-page-head__sub">Employees who have not submitted timesheets for the current period</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
      </div>
    </div>

    <!-- Alert banner if there are missing timesheets -->
    <div v-if="missingTimesheets.length > 0" class="alert-banner">
      <AppIcon name="alert" :size="16" />
      <div>
        <strong>{{ missingTimesheets.length }} employee(s) have not submitted timesheets</strong>
        <p>Follow up with these employees to ensure timely payroll processing.</p>
      </div>
    </div>

    <!-- Sticky Filters Bar -->
    <div class="filters-bar">
      <div class="filters-bar__content">
        <!-- Search -->
        <div class="filter-group">
          <div class="mhr-input-icon">
            <AppIcon name="search" :size="16" />
            <input 
              v-model="searchQuery"
              type="text" 
              class="mhr-input" 
              placeholder="Search by name, email, or employee #"
              style="padding-left:36px;"
            />
          </div>
        </div>

        <!-- Month Filter -->
        <div class="filter-group">
          <select v-model="periodMonth" @change="changePeriod" class="mhr-select" style="width:140px;">
            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
        </div>

        <!-- Year Filter -->
        <div class="filter-group">
          <select v-model="periodYear" @change="changePeriod" class="mhr-select" style="width:100px;">
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>

        <!-- Event Filter -->
        <div class="filter-group">
          <select v-model="filterEvent" class="mhr-select">
            <option value="">All Events</option>
            <option v-for="event in uniqueEvents" :key="event" :value="event">{{ event }}</option>
          </select>
        </div>

        <!-- Department Filter -->
        <div class="filter-group">
          <select v-model="filterDepartment" class="mhr-select">
            <option value="">All Departments</option>
            <option v-for="dept in uniqueDepartments" :key="dept" :value="dept">{{ dept }}</option>
          </select>
        </div>

        <!-- Designation Filter -->
        <div class="filter-group">
          <select v-model="filterDesignation" class="mhr-select">
            <option value="">All Designations</option>
            <option v-for="desig in uniqueDesignations" :key="desig" :value="desig">{{ desig }}</option>
          </select>
        </div>

        <!-- Clear Filters -->
        <button 
          v-if="searchQuery || filterEvent || filterDepartment || filterDesignation"
          @click="clearFilters"
          class="mhr-btn mhr-btn--ghost"
          style="padding:0 12px;"
        >
          <AppIcon name="x" :size="16" />
          Clear
        </button>

        <!-- Result Count -->
        <div class="filter-result-count">
          {{ filteredTimesheets.length }} of {{ missingTimesheets.length }} employees
        </div>
      </div>
    </div>

    <!-- Missing Timesheets Table -->
    <div class="mhr-card">
      <div class="mhr-table-container">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>EMPLOYEE</th>
              <th>EMPLOYEE NUMBER</th>
              <th>EVENTS</th>
              <th>DEPARTMENT</th>
              <th>DESIGNATION</th>
              <th>PERIOD</th>
              <th>LAST SUBMITTED</th>
              <th>DAYS OVERDUE</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredTimesheets.length === 0 && missingTimesheets.length === 0">
              <td colspan="8" style="text-align:center;padding:48px;color:var(--mhr-ink-3);">
                <AppIcon name="check" :size="32" style="color:var(--green-500);margin-bottom:12px;" />
                <div style="font-size:15px;font-weight:500;margin-bottom:6px;">All timesheets submitted</div>
                <div style="font-size:13px;">All employees have submitted their timesheets for the current period.</div>
              </td>
            </tr>
            <tr v-else-if="filteredTimesheets.length === 0">
              <td colspan="8" style="text-align:center;padding:48px;color:var(--mhr-ink-3);">
                <AppIcon name="search" :size="32" style="color:var(--mhr-ink-3);margin-bottom:12px;" />
                <div style="font-size:15px;font-weight:500;margin-bottom:6px;">No matching employees</div>
                <div style="font-size:13px;">Try adjusting your filters to see more results.</div>
              </td>
            </tr>
            <tr v-for="emp in filteredTimesheets" :key="emp.id">
              <td>
                <div style="font-weight:500;">{{ emp.fullName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);">{{ emp.email }}</div>
              </td>
              <td style="font-family:monospace;color:var(--mhr-ink-2);">{{ emp.employeeNumber }}</td>
              <td>
                <div v-if="emp.events && emp.events.length > 0" style="display:flex;flex-wrap:wrap;gap:4px;">
                  <span v-for="event in emp.events" :key="event" class="mhr-badge mhr-badge--neutral" style="font-size:11px;">
                    {{ event }}
                  </span>
                </div>
                <span v-else style="color:var(--mhr-ink-3);font-size:12px;">No events</span>
              </td>
              <td style="color:var(--mhr-ink-2);">{{ emp.department }}</td>
              <td style="color:var(--mhr-ink-2);">{{ emp.designation }}</td>
              <td style="color:var(--mhr-ink-2);">{{ emp.period }}</td>
              <td style="color:var(--mhr-ink-3);font-size:12px;">{{ fmtDate(emp.lastSubmittedDate) || 'Never' }}</td>
              <td>
                <span v-if="emp.daysOverdue > 0" class="mhr-badge mhr-badge--warn">
                  {{ emp.daysOverdue }} days
                </span>
                <span v-else class="mhr-badge mhr-badge--neutral">Due today</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.alert-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  background: var(--mhr-warn-bg);
  border: 1px solid var(--mhr-warn);
  border-radius: 8px;
  margin-bottom: 16px;
  color: var(--mhr-warn);
}

.alert-banner strong {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
}

.alert-banner p {
  font-size: 13px;
  margin: 0;
  color: var(--mhr-ink-2);
}

/* Sticky Filters Bar */
.filters-bar {
  position: sticky;
  top: 0;
  z-index: 10;
  background: var(--mhr-bg);
  border-bottom: 1px solid var(--mhr-line);
  margin: 0 calc(-1 * var(--mhr-pad));
  padding: 12px var(--mhr-pad);
  margin-bottom: 16px;
}

.filters-bar__content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-group {
  flex: 0 0 auto;
}

.filter-group:first-child {
  flex: 1 1 300px;
  min-width: 200px;
}

.mhr-input-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.mhr-input-icon svg {
  position: absolute;
  left: 12px;
  color: var(--mhr-ink-3);
  pointer-events: none;
}

.filter-result-count {
  margin-left: auto;
  font-size: 13px;
  color: var(--mhr-ink-3);
  font-weight: 500;
  white-space: nowrap;
}
</style>
