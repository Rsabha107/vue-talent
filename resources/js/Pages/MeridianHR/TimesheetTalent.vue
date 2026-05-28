<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import EventBanner from '@/Components/MeridianHR/EventBanner.vue'
import EmployeeSelector from '@/Components/MeridianHR/EmployeeSelector.vue'
import SubmitButton from '@/Components/MeridianHR/SubmitButton.vue'
import { router, usePage } from '@inertiajs/vue3'

// ── Toast ────────────────────────────────────────────────────────────
const toasts = ref([])
let toastSeq = 0

function showToast(message, type = 'success') {
  const id = ++toastSeq
  toasts.value.push({ id, message, type })
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 4000)
}

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:     { type: String, default: 'admin' },
  hrPage:     { type: String, default: 'timesheet-talent' },
  employees:  { type: Array,  default: () => [] },
  currentEmployee: { type: Object, default: null },
  monthsName: { type: Array,  default: () => [] },  // [{ id, monthName, monthNumber }]
  years:      { type: Array,  default: () => [] },  // [2024, 2025, 2026]
  statuses:   { type: Array,  default: () => [] },  // [{ id, title }]
  timesheets: { type: Array,  default: () => [] },
  cutoffDay:  { type: [Number, String], default: 21 },
  disableSubmission: { type: Boolean, default: false },
  formattedCutoff: { type: String, default: null },
  isTeamView: { type: Boolean, default: false },
  /*
    timesheets item shape:
    {
      id, employeeId, employeeName, employeeColor,
      period,         // "May-2026"
      monthNumber,    // 5
      year,           // 2026
      daysInMonth,    // 31
      startDay,       // 1, or contract start day if same month
      endDay,         // 31, or contract end day if same month
      statusId, statusTitle,
      hasEntries,     // boolean
      entries: [{ day, dayName, isWeekend, action, isLeave }],
      daysWorked,     // number
      leaveTaken,     // number
      unpaidLeave,    // number
      totalDays,      // number
      dailyRate,      // string formatted
      salary,         // string formatted
      payment,        // string formatted
      approver,       // string | null
    }
  */
})

// ── Event context ───────────────────────────────────────────────────
const selectedEventId = computed(() => usePage().props.selectedEvent)
const availableEvents = computed(() => usePage().props.availableEvents || [])
const isEmployee = computed(() => props.currentEmployee !== null)
const selectedEventData = computed(() => {
  if (!selectedEventId.value) return null
  return availableEvents.value.find(e => e.id === selectedEventId.value)
})

// Normalize cutoffDay to number for comparisons
const cutoffDayNumber = computed(() => Number(props.cutoffDay) || 0)

// Check if active timesheet is approved and should be read-only for employees
const isApprovedReadOnly = computed(() => {
  return isEmployee.value && activeTimesheet.value?.statusTitle === 'Approved'
})

// ── View state ──────────────────────────────────────────────────────
const view            = ref('list')   // 'list' | 'entries'
const activeTimesheet = ref(null)
const localTimesheets = ref(JSON.parse(JSON.stringify(props.timesheets)))

// Sync localTimesheets when props.timesheets changes (after reload)
watch(() => props.timesheets, (newTimesheets) => {
  localTimesheets.value = JSON.parse(JSON.stringify(newTimesheets))
}, { deep: true })

// ── Filters ─────────────────────────────────────────────────────────
const filterPeriod = ref('')
const filterStatus = ref('all')

const filtered = computed(() =>
  localTimesheets.value.filter(t => {
    const okPeriod = !filterPeriod.value || t.period.toLowerCase().includes(filterPeriod.value.toLowerCase())
    const okStatus = filterStatus.value === 'all' || t.statusTitle.toLowerCase() === filterStatus.value.toLowerCase()
    return okPeriod && okStatus
  })
)

// ── Refresh ─────────────────────────────────────────────────────────
const isRefreshing = ref(false)

function refreshData() {
  isRefreshing.value = true
  router.reload({
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      isRefreshing.value = false
      localTimesheets.value = JSON.parse(JSON.stringify(props.timesheets))
      showToast('Timesheets refreshed', 'success')
    }
  })
}

// ── Add timesheet modal ─────────────────────────────────────────────
const showAddModal = ref(false)
const isAdding     = ref(false)
const addForm      = ref({ employeeId: '', monthId: '', year: '' })
const addErrors    = ref({})

// Clear add form errors when fields are modified
watch(() => addForm.value.employeeId, () => {
  if (addErrors.value.employeeId) delete addErrors.value.employeeId
})
watch(() => addForm.value.monthId, () => {
  if (addErrors.value.monthId) delete addErrors.value.monthId
})
watch(() => addForm.value.year, () => {
  if (addErrors.value.year) delete addErrors.value.year
})

function openAddModal() {
  addForm.value   = { employeeId: '', monthId: '', year: '' }
  // Auto-populate employee ID for employees and managers in personal view
  if (props.currentEmployee && (isEmployee.value || !props.isTeamView)) {
    addForm.value.employeeId = props.currentEmployee.id
  }
  addErrors.value = {}
  showAddModal.value = true
}

function validateAdd() {
  const e = {}
  // Only validate employee selection for admin or manager in team view
  if ((props.hrRole === 'admin' || (props.hrRole === 'manager' && props.isTeamView)) && !addForm.value.employeeId) {
    e.employeeId = 'Employee is required.'
  }
  if (!addForm.value.monthId) e.monthId = 'Month is required.'
  if (!addForm.value.year)    e.year    = 'Year is required.'
  addErrors.value = e
  return Object.keys(e).length === 0
}

function submitAdd() {
  if (!validateAdd()) return
  isAdding.value = true
  const routeName = isEmployee.value ? 'hr.my-timesheets.store' : 'hr.timesheet-talent.store'
  router.post(route(routeName), {
    employee_id:       addForm.value.employeeId || null,
    month_selected_id: addForm.value.monthId,
    year_selected:     addForm.value.year,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false
      showToast('Timesheet created successfully.')
      // Reload to get fresh data with leave entries calculated
      router.reload({ preserveScroll: true })
    },
    onError: (errors) => {
      // Map server validation keys to form error fields
      addErrors.value = {
        employeeId: errors.employee_id,
        monthId:    errors.month_selected_id,
        year:       errors.year_selected,
      }
      showToast(Object.values(errors)[0] || 'Please fix the errors and try again.', 'error')
    },
    onFinish: () => { isAdding.value = false },
  })
}

// ── Status modal ────────────────────────────────────────────────────
const showStatusModal = ref(false)
const statusTarget    = ref(null)
const statusForm      = ref({ statusId: '', additionalInfo: '' })
const statusErrors    = ref({})
const isSavingStatus  = ref(false)

function openStatusModal(ts) {
  statusTarget.value = ts
  statusForm.value   = { statusId: ts.statusId || '', additionalInfo: '' }
  statusErrors.value = {}
  showStatusModal.value = true
}

function validateStatus() {
  const e = {}
  if (!statusForm.value.statusId) e.statusId = 'Status is required.'
  statusErrors.value = e
  return Object.keys(e).length === 0
}

function saveStatus() {
  if (!validateStatus()) return
  isSavingStatus.value = true
  router.post(route('hr.timesheet-talent.status'), {
    id:                     statusTarget.value.id,
    status_id:              statusForm.value.statusId,
    additional_information: statusForm.value.additionalInfo,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      const ts = localTimesheets.value.find(t => t.id === statusTarget.value.id)
      if (ts) {
        ts.statusId    = statusForm.value.statusId
        const st       = props.statuses.find(s => String(s.id) === String(statusForm.value.statusId))
        ts.statusTitle = st?.title || ''
      }
      showStatusModal.value = false
      showToast('Status updated successfully.')
    },
    onError: (errors) => {
      statusErrors.value = {
        statusId:       errors.status_id,
        additionalInfo: errors.additional_information,
      }
      showToast(Object.values(errors)[0] || 'Please fix the errors and try again.', 'error')
    },
    onFinish: () => { isSavingStatus.value = false },
  })
}

// ── Entries view ────────────────────────────────────────────────────
const entryRows       = ref([])
const isSavingEntries = ref(false)
const isSubmitting    = ref(false)

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
// Weekend configuration: 5 = Friday, 6 = Saturday (common in Middle East)
// For Western weekend (Sat/Sun), use: [0, 6]
const WEEKEND_DAYS = [5, 6]

// Helper to get actual days in a month
function getDaysInMonth(year, monthNumber) {
  return new Date(year, monthNumber, 0).getDate()
}

function buildRows(ts) {
  // If the timesheet already has entries from the backend, use them
  if (ts.entries && ts.entries.length) {
    return ts.entries.map(e => ({ ...e }))
  }
  // Otherwise generate blank rows for the valid date range
  const rows = []
  for (let d = ts.startDay; d <= ts.endDay; d++) {
    const date      = new Date(ts.year, ts.monthNumber - 1, d)
    const dow       = date.getDay()
    const isWeekend = WEEKEND_DAYS.includes(dow)
    rows.push({
      day:       d,
      dayName:   DAYS[dow],
      isWeekend,
      action:    'W',   // default to Worked
      isLeave:   false,
    })
  }
  return rows
}

function openEntries(ts) {
  activeTimesheet.value = ts
  entryRows.value       = buildRows(ts)
  view.value            = 'entries'
  
  // Recalculate payment when opening timesheet
  recalculatePayment()
}

function recalculatePayment() {
  if (!activeTimesheet.value || !activeTimesheet.value.id) return
  
  router.post(route('hr.timesheet.calculate'), {
    timesheet_id: activeTimesheet.value.id,
  }, {
    preserveScroll: true,
    onSuccess: (page) => {
      // Update local timesheet with new calculated values
      const updated = page.props.updatedTimesheet
      if (updated && activeTimesheet.value) {
        activeTimesheet.value.daysWorked = updated.daysWorked
        activeTimesheet.value.leaveTaken = updated.leaveTaken
        activeTimesheet.value.unpaidLeave = updated.unpaidLeave
        activeTimesheet.value.totalDays = updated.totalDays
        activeTimesheet.value.dailyRate = updated.dailyRate
        activeTimesheet.value.salary = updated.salary
        activeTimesheet.value.payment = updated.payment
        
        // Also update in localTimesheets
        const localTs = localTimesheets.value.find(t => t.id === updated.id)
        if (localTs) {
          localTs.daysWorked = updated.daysWorked
          localTs.leaveTaken = updated.leaveTaken
          localTs.unpaidLeave = updated.unpaidLeave
          localTs.totalDays = updated.totalDays
          localTs.dailyRate = updated.dailyRate
          localTs.salary = updated.salary
          localTs.payment = updated.payment
        }
      }
    },
  })
}

function backToList() {
  view.value            = 'list'
  activeTimesheet.value = null
  entryRows.value       = []
  editingDay.value      = null
}

function firstDayOffset() {
  if (!activeTimesheet.value) return 0
  const ts = activeTimesheet.value
  // With Sunday as first day (0), just use getDay() directly
  return new Date(ts.year, ts.monthNumber - 1, 1).getDay()
}

function lastDayOffset() {
  if (!activeTimesheet.value) return 0
  const ts = activeTimesheet.value
  const lastDay = ts.endDay
  const lastDayOfWeek = new Date(ts.year, ts.monthNumber - 1, lastDay).getDay()
  // Return number of blank cells needed to reach Saturday (day 6)
  return 6 - lastDayOfWeek
}

// ── Day edit modal ──────────────────────────────────────────────────
const ACTION_TYPES = [
  { key: 'W', label: 'Worked',       sub: 'Regular working day',    icon: 'calendar' },
  { key: 'U', label: 'Unpaid leave', sub: 'Unpaid time off',        icon: 'clock' },
]

const editingDay = ref(null)
const editAction = ref('W')

function canEdit(day) {
  // Managers cannot edit team timesheets (read-only access)
  if (props.hrRole === 'manager') return false
  // Employees cannot edit approved timesheets
  if (isApprovedReadOnly.value) return false
  // Employees cannot edit when submission is disabled
  if (isEmployee.value && props.disableSubmission) return false
  if (day.isWeekend) return false  // Can't edit weekends (non-working days)
  if (day.isLeave) return false    // Can't edit days with approved leave
  return true
}

function openDay(day) {
  // Prevent editing for managers (read-only access to team timesheets)
  if (props.hrRole === 'manager') {
    showToast('Team timesheets are read-only. Only employees can edit their own timesheets.', 'error')
    return
  }
  // Prevent editing approved timesheets for employees
  if (isApprovedReadOnly.value) {
    showToast('This timesheet is approved and cannot be edited.', 'error')
    return
  }
  // Prevent editing for employees when submission is disabled
  if (isEmployee.value && props.disableSubmission) {
    showToast('Timesheet submission is closed. Entries are read-only.', 'error')
    return
  }
  if (day.isWeekend) {
    showToast('Weekends are non-working days and cannot be edited', 'error')
    return
  }
  if (!canEdit(day)) {
    showToast('This day has approved leave and cannot be edited', 'error')
    return
  }
  editingDay.value = day
  editAction.value = day.action || 'W'
}

function closeModal() {
  editingDay.value = null
}

function saveDay() {
  const day = editingDay.value
  if (!day) return
  
  const dayNumber = day.day
  const newAction = editAction.value
  
  console.log('Saving day:', dayNumber, 'with action:', newAction)
  console.log('Before update:', entryRows.value.length, 'rows')
  
  // Find and update the row
  const index = entryRows.value.findIndex(r => r.day === dayNumber)
  if (index !== -1) {
    // Create completely new array with updated row
    const newRows = [
      ...entryRows.value.slice(0, index),
      { ...entryRows.value[index], action: newAction },
      ...entryRows.value.slice(index + 1)
    ]
    entryRows.value = newRows
    
    const newCounts = {
      worked: newRows.filter(r => r.action === 'W' && !r.isLeave && !r.isWeekend).length,
      leave: newRows.filter(r => (r.action === 'L' || r.isLeave) && !r.isWeekend).length,
      unpaid: newRows.filter(r => r.action === 'U' && !r.isWeekend).length
    }
    
    console.log('After update - row', index, ':', newRows[index])
    console.log('Summary should update:', newCounts)
    console.log('Unpaid days:', newCounts.unpaid, '- Payment will recalculate automatically')
  }
  
  closeModal()
  
  nextTick(() => {
    showToast('Day updated. Remember to save your timesheet.', 'success')
  })
}

// ── Summary computed ────────────────────────────────────────────────
// Exclude weekends from all counts since employees don't work Fri/Sat
const summary = computed(() => {
  const worked = entryRows.value.filter(r => r.action === 'W' && !r.isLeave && !r.isWeekend).length
  const leave  = entryRows.value.filter(r => (r.action === 'L' || r.isLeave) && !r.isWeekend).length
  const unpaid = entryRows.value.filter(r => r.action === 'U' && !r.isWeekend).length
  const weekend = entryRows.value.filter(r => r.isWeekend).length
  return { workedDays: worked, leaveDays: leave, unpaidDays: unpaid, weekend }
})

// ── Calculated payment based on unpaid days ────────────────────────
const calculatedPayment = computed(() => {
  if (!activeTimesheet.value) return '—'
  
  const unpaidDays = summary.value.unpaidDays
  if (unpaidDays === 0) {
    // No unpaid days, return original payment
    return activeTimesheet.value.payment || '0.00'
  }
  
  // Parse the salary (remove currency symbols and commas)
  const salaryStr = activeTimesheet.value.salary || '0'
  const salary = parseFloat(salaryStr.replace(/[^0-9.-]/g, ''))
  
  if (isNaN(salary) || salary === 0) return '0.00'
  
  // Use fixed 30 working days for daily rate calculation
  const totalWorkingDays = 30
  
  // Calculate daily rate
  const dailyRate = salary / totalWorkingDays
  
  // Deduct unpaid days
  const deduction = dailyRate * unpaidDays
  const finalPayment = salary - deduction
  
  // Format with 2 decimal places and comma separator
  return finalPayment.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
})

// ── Cell helpers ────────────────────────────────────────────────────
function cellClass(day) {
  if (day.isWeekend) return 'ts-cell--weekend'
  if (day.isLeave || day.action === 'L') return 'ts-cell--leave'
  if (day.action === 'U') return 'ts-cell--unpaid'
  if (day.action === 'W') return 'ts-cell--worked'
  return 'ts-cell--blank'
}

function cellLabel(day) {
  if (day.isWeekend) return 'Weekend'
  if (day.isLeave) return 'Leave'
  if (day.action === 'W') return 'Worked'
  if (day.action === 'U') return 'Unpaid'
  return '—'
}

function saveEntries() {
  isSavingEntries.value = true
  // Filter out weekends - they shouldn't be saved
  const workingDays = entryRows.value.filter(r => !r.isWeekend)
  
  router.post(route('hr.timesheet-talent.entries.store'), {
    employee_timesheet_id: activeTimesheet.value.id,
    employee_id:           activeTimesheet.value.employeeId,
    calendar_day:          workingDays.map(r => r.day),
    day_action:            workingDays.map(r => r.isLeave ? 'L' : r.action),
  }, {
    onSuccess: () => {
      // Update the local record so "View / Edit" shows the correct entry count
      const ts = localTimesheets.value.find(t => t.id === activeTimesheet.value.id)
      if (ts) {
        ts.hasEntries = true
        ts.entries = entryRows.value.map(r => ({ ...r }))
      }
      showToast('Timesheet entries saved successfully.')
      backToList()
      // Refresh data to get updated calculations from server
      refreshData()
    },
    onError: (errors) => {
      const first = Object.values(errors)[0]
      showToast(first || 'Failed to save entries. Please try again.', 'error')
    },
    onFinish: () => { isSavingEntries.value = false },
  })
}

function submitForApproval() {
  if (!activeTimesheet.value) return
  showSubmitModal.value = true
}

function confirmSubmit() {
  if (!activeTimesheet.value) return
  
  isSubmitting.value = true
  
  // First, save entries to database
  const workingDays = entryRows.value.filter(r => !r.isWeekend)
  
  router.post(route('hr.timesheet-talent.entries.store'), {
    employee_timesheet_id: activeTimesheet.value.id,
    employee_id:           activeTimesheet.value.employeeId,
    calendar_day:          workingDays.map(r => r.day),
    day_action:            workingDays.map(r => r.isLeave ? 'L' : r.action),
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Now submit the timesheet for approval
      router.post(route('hr.timesheet.submit'), {
        timesheet_id: activeTimesheet.value.id,
      }, {
        onSuccess: () => {
          // Update local status to Submitted
          const ts = localTimesheets.value.find(t => t.id === activeTimesheet.value.id)
          if (ts) {
            ts.statusId = 2 // Submitted
            ts.statusTitle = 'Submitted'
            ts.hasEntries = true
            ts.entries = entryRows.value.map(r => ({ ...r }))
          }
          if (activeTimesheet.value) {
            activeTimesheet.value.statusId = 2
            activeTimesheet.value.statusTitle = 'Submitted'
          }
          showSubmitModal.value = false
          showToast('Timesheet submitted for approval successfully.')
          backToList()
          refreshData()
        },
        onError: (errors) => {
          const first = Object.values(errors)[0]
          showToast(first || 'Failed to submit timesheet. Please try again.', 'error')
        },
        onFinish: () => { isSubmitting.value = false },
      })
    },
    onError: (errors) => {
      const first = Object.values(errors)[0]
      showToast(first || 'Failed to submit timesheet. Please try again.', 'error')
    },
    onFinish: () => { isSubmitting.value = false },
  })
}

// ── Delete ──────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const timesheetToDelete = ref(null)
const deletingId = ref(null)

// ── Submit Confirmation ─────────────────────────────────────────────
const showSubmitModal = ref(false)

function confirmDelete(ts) {
  timesheetToDelete.value = ts
  showDeleteModal.value = true
}

function deleteTimesheet() {
  const ts = timesheetToDelete.value
  if (!ts) return
  
  deletingId.value = ts.id
  const routeName = isEmployee.value ? 'hr.my-timesheets.destroy' : 'hr.timesheet-talent.destroy'
  
  router.delete(route(routeName, ts.id), {
    onSuccess: () => {
      localTimesheets.value = localTimesheets.value.filter(t => t.id !== ts.id)
      showToast('Timesheet deleted.')
      showDeleteModal.value = false
      timesheetToDelete.value = null
    },
    onError: () => { showToast('Failed to delete timesheet.', 'error') },
    onFinish: () => { deletingId.value = null },
  })
}

// ── Status badge helpers ─────────────────────────────────────────────
const STATUS_STYLE = {
  'Pending':         { bg: 'var(--mhr-warn-bg)',     color: 'var(--mhr-warn)'        },
  'Submitted':       { bg: 'var(--mhr-info-bg)',     color: 'var(--mhr-info)'        },
  'Pending Payroll': { bg: 'var(--mhr-accent-soft)', color: 'var(--mhr-accent-ink)' },
  'Approved':        { bg: 'var(--green-700)',       color: '#fff'                   },
  'Rejected':        { bg: 'var(--mhr-danger-bg)',   color: 'var(--mhr-danger)'      },
}
function statusStyle(title) {
  return STATUS_STYLE[title] || { bg: 'var(--mhr-surface-2)', color: 'var(--mhr-ink-3)' }
}

const availableYears = computed(() => {
  if (props.years.length) return props.years
  const y = new Date().getFullYear()
  return [y - 1, y, y + 1]
})

const isAdminOrManager = computed(() => props.hrRole === 'admin' || props.hrRole === 'manager')
</script>

<template>
  <div>

    <!-- ════════════════════════════════════════════════════════════
         LIST VIEW
    ════════════════════════════════════════════════════════════════ -->
    <template v-if="view === 'list'">

      <div class="mhr-page-head">
        <div>
          <h1 class="mhr-page-head__title">Timesheets</h1>
          <p class="mhr-page-head__sub">{{ filtered.length }} record{{ filtered.length !== 1 ? 's' : '' }}</p>
        </div>
        <div class="mhr-page-head__actions">
          <RefreshButton variant="ghost" :is-refreshing="isRefreshing" @refresh="refreshData" />
          <button 
            v-if="!isTeamView"
            class="mhr-btn mhr-btn--primary" 
            @click="openAddModal"
            :disabled="(!selectedEventId && hrRole === 'manager') || (isEmployee && disableSubmission)"
            :style="((!selectedEventId && hrRole === 'manager') || (isEmployee && disableSubmission)) ? 'opacity: 0.5; cursor: not-allowed;' : ''"
            :title="!selectedEventId && hrRole === 'manager' ? 'Please select an event to create timesheets' : (isEmployee && disableSubmission ? 'Timesheet submission is closed' : '')">
            <AppIcon name="plus" :size="14" /> Add Timesheet
          </button>
        </div>
      </div>

      <!-- Event Context Banner -->
      <EventBanner 
        v-if="selectedEventData"
        :event-data="selectedEventData"
      />

      <!-- Info Banner: No Event Selected for Manager -->
      <div v-if="!selectedEventId && hrRole === 'manager' && !isTeamView" style="background:var(--mhr-accent-soft);border-left:3px solid var(--mhr-accent);padding:12px 16px;margin-bottom:16px;border-radius:var(--mhr-r);display:flex;align-items:center;gap:12px;">
        <AppIcon name="info" :size="18" style="color:var(--mhr-accent);flex-shrink:0;" />
        <div style="font-size:13px;color:var(--mhr-ink);">
          <strong style="color:var(--mhr-accent);">Viewing all your events</strong> — 
          Timesheet data is aggregated from all assigned events. 
          Select a specific event from the event selector above to view timesheets for a single event.
        </div>
      </div>

      <!-- Timesheet Submission Cutoff Banner -->
      <div v-if="cutoffDayNumber !== 0 && formattedCutoff && isEmployee" 
        style="padding:14px 18px;border-radius:var(--mhr-r);margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;font-weight:500;border:2px solid;"
        :style="disableSubmission 
          ? 'background:#fee;border-color:#f44;color:#900;' 
          : 'background:#e7f5ff;border-color:#0077cc;color:#004080;'">
        <AppIcon :name="disableSubmission ? 'alert' : 'check'" :size="18" style="margin-top:2px;flex-shrink:0;" />
        <div style="flex:1;">
          <div style="font-size:14px;margin-bottom:4px;">
            <template v-if="disableSubmission">
              <strong>Timesheet Submission Window Closed</strong>
            </template>
            <template v-else>
              <strong>Timesheet Submission Window Open</strong>
            </template>
          </div>
          <div style="font-size:13px;font-weight:400;line-height:1.5;">
            <template v-if="disableSubmission">
              The monthly cutoff date ({{ formattedCutoff }}) has passed. New timesheet submissions are no longer accepted for this month. 
              You can still view your previously submitted timesheets below, but cannot create or modify entries until the next month begins.
            </template>
            <template v-else>
              Submit your timesheets before the {{ formattedCutoff }} of this month. After this date, the submission window will close 
              and you will not be able to create new timesheets until the next month. Make sure all your hours are recorded and submitted on time.
            </template>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;justify-content:space-between;">
        <div style="position:relative;max-width:280px;">
          <AppIcon name="search" :size="14" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
          <input class="mhr-input" style="padding-left:30px;" placeholder="Filter by period…" v-model="filterPeriod" />
        </div>
        <div style="display:flex;gap:4px;padding:3px;background:var(--mhr-surface-2);border:1px solid var(--mhr-line);border-radius:9px;">
          <button v-for="f in ['all','pending','submitted','approved','rejected']" :key="f"
            class="mhr-btn mhr-btn--sm"
            :style="filterStatus === f ? 'background:var(--green-700);color:#fff;' : 'background:transparent;color:var(--mhr-ink-2);'"
            @click="filterStatus = f">
            {{ f.charAt(0).toUpperCase() + f.slice(1) }}
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="mhr-card">
        <div class="mhr-table-container">
          <table class="mhr-table">
            <thead>
              <tr>
                <th v-if="isAdminOrManager">Employee</th>
                <th>Period</th>
                <th>Status</th>
                <th style="text-align:right;">Worked</th>
                <th style="text-align:right;">Leaves</th>
                <th style="text-align:right;">Unpaid</th>
                <th style="text-align:right;">Total Days</th>
                <th style="text-align:right;">Daily Rate</th>
                <th style="text-align:right;">Salary</th>
                <th style="text-align:right;">Payment</th>
                <th>Approver</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filtered.length === 0">
                <td :colspan="isAdminOrManager ? 13 : 12" style="text-align:center;padding:56px 20px;">
                  <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                    <AppIcon name="clock" :size="40" style="opacity:0.18;" />
                    <div style="font-size:14px;font-weight:600;color:var(--mhr-ink-2);">No timesheets found</div>
                    <div style="font-size:13px;color:var(--mhr-ink-3);">Click <strong>Add Timesheet</strong> to create the first one.</div>
                  </div>
                </td>
              </tr>
              <tr v-for="ts in filtered" :key="ts.id">
                <td v-if="isAdminOrManager">
                  <div style="display:flex;align-items:center;gap:10px;">
                    <AppAvatar :name="ts.employeeName" :c="ts.employeeColor" />
                    <div>
                      <div style="font-weight:500;">{{ ts.employeeName }}</div>
                      <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ ts.employeeNumber }}</div>
                      <div v-if="!selectedEventId && ts.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                        <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                        <span>{{ ts.eventName }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="mhr-mono" style="font-size:12px;color:var(--mhr-ink-2);">{{ ts.period }}</span>
                </td>
                <td>
                  <span class="mhr-badge" :style="{ background: statusStyle(ts.statusTitle).bg, color: statusStyle(ts.statusTitle).color }">
                    {{ ts.statusTitle || 'Pending' }}
                  </span>
                </td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;">{{ ts.daysWorked || 0 }}</td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;">{{ ts.leaveTaken || 0 }}</td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;">{{ ts.unpaidLeave || 0 }}</td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;font-weight:500;">{{ ts.totalDays || 0 }}</td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;font-family:monospace;">{{ ts.dailyRate || '0.00' }}</td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;font-family:monospace;">{{ ts.salary || '0.00' }}</td>
                <td style="text-align:right;color:var(--mhr-ink-2);font-size:13px;font-family:monospace;font-weight:500;">{{ ts.payment || '0.00' }}</td>
                <td style="color:var(--mhr-ink-3);font-size:12px;">
                  <span v-if="ts.approver" style="font-style:italic;">{{ ts.approver }}</span>
                  <span v-else style="opacity:0.5;">—</span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button class="mhr-btn mhr-btn--sm mhr-btn--outline" @click="openEntries(ts)">
                      <AppIcon :name="(isEmployee && (disableSubmission || ts.statusTitle === 'Approved')) || hrRole === 'manager' ? 'eye' : 'edit'" :size="13" />
                      <template v-if="(isEmployee && (disableSubmission || ts.statusTitle === 'Approved')) || hrRole === 'manager'">
                        View
                      </template>
                      <template v-else>
                        {{ ts.hasEntries ? 'View / Edit' : 'Add Entries' }}
                      </template>
                    </button>
                    <button v-if="hrRole === 'admin'" class="mhr-btn mhr-btn--sm mhr-btn--ghost" @click="openStatusModal(ts)">
                      Status
                    </button>
                    <button v-if="hrRole === 'admin' || (isEmployee && ts.statusTitle !== 'Approved')" class="mhr-btn mhr-btn--sm mhr-btn--danger"
                      @click="confirmDelete(ts)" :disabled="deletingId === ts.id">
                      <AppIcon name="trash" :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Cards View -->
      <div class="ts-mobile-cards">
        <div v-if="filtered.length === 0" class="ts-empty-state">
          <AppIcon name="clock" :size="40" style="opacity:0.18;" />
          <div style="font-size:14px;font-weight:600;color:var(--mhr-ink-2);margin-top:12px;">No timesheets found</div>
          <div style="font-size:13px;color:var(--mhr-ink-3);margin-top:4px;">Click <strong>Add Timesheet</strong> to create the first one.</div>
        </div>

        <div v-for="ts in filtered" :key="'card-' + ts.id" class="ts-card">
          <!-- Header -->
          <div class="ts-card__header">
            <div v-if="isAdminOrManager" class="ts-card__employee">
              <AppAvatar :name="ts.employeeName" :c="ts.employeeColor" :size="32" />
              <span style="font-weight:600;font-size:14px;">{{ ts.employeeName }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <span class="mhr-mono" style="font-size:13px;color:var(--mhr-ink-2);font-weight:500;">{{ ts.period }}</span>
              <span class="mhr-badge" :style="{ background: statusStyle(ts.statusTitle).bg, color: statusStyle(ts.statusTitle).color }">
                {{ ts.statusTitle || 'Pending' }}
              </span>
            </div>
          </div>

          <!-- Stats Grid -->
          <div class="ts-card__stats">
            <div class="ts-card__stat">
              <div class="ts-card__stat-label">Worked</div>
              <div class="ts-card__stat-value">{{ ts.daysWorked || 0 }}</div>
            </div>
            <div class="ts-card__stat">
              <div class="ts-card__stat-label">Leave</div>
              <div class="ts-card__stat-value">{{ ts.leaveTaken || 0 }}</div>
            </div>
            <div class="ts-card__stat">
              <div class="ts-card__stat-label">Unpaid</div>
              <div class="ts-card__stat-value">{{ ts.unpaidLeave || 0 }}</div>
            </div>
            <div class="ts-card__stat">
              <div class="ts-card__stat-label">Total</div>
              <div class="ts-card__stat-value" style="font-weight:600;">{{ ts.totalDays || 0 }}</div>
            </div>
          </div>

          <!-- Financial Info -->
          <div class="ts-card__financial">
            <div class="ts-card__fin-item">
              <span class="ts-card__fin-label">Daily Rate</span>
              <span class="ts-card__fin-value">{{ ts.dailyRate || '0.00' }}</span>
            </div>
            <div class="ts-card__fin-item">
              <span class="ts-card__fin-label">Salary</span>
              <span class="ts-card__fin-value">{{ ts.salary || '0.00' }}</span>
            </div>
            <div class="ts-card__fin-item">
              <span class="ts-card__fin-label">Payment</span>
              <span class="ts-card__fin-value" style="font-weight:600;color:var(--green-700);">{{ ts.payment || '0.00' }}</span>
            </div>
          </div>

          <!-- Approver -->
          <div v-if="ts.approver" class="ts-card__approver">
            <AppIcon name="check" :size="12" />
            <span>Approved by {{ ts.approver }}</span>
          </div>

          <!-- Actions -->
          <div class="ts-card__actions">
            <button class="mhr-btn mhr-btn--sm mhr-btn--outline" @click="openEntries(ts)" style="flex:1;">
              <AppIcon :name="(isEmployee && (disableSubmission || ts.statusTitle === 'Approved')) || hrRole === 'manager' ? 'eye' : 'edit'" :size="13" />
              <template v-if="(isEmployee && (disableSubmission || ts.statusTitle === 'Approved')) || hrRole === 'manager'">
                View
              </template>
              <template v-else>
                {{ ts.hasEntries ? 'View / Edit' : 'Add Entries' }}
              </template>
            </button>
            <button v-if="hrRole === 'admin'" class="mhr-btn mhr-btn--sm mhr-btn--ghost" @click="openStatusModal(ts)">
              Status
            </button>
            <button v-if="hrRole === 'admin' || (isEmployee && ts.statusTitle !== 'Approved')" class="mhr-btn mhr-btn--sm mhr-btn--danger"
              @click="confirmDelete(ts)" :disabled="deletingId === ts.id">
              <AppIcon name="trash" :size="13" />
            </button>
          </div>
        </div>
      </div>

    </template>

    <!-- ════════════════════════════════════════════════════════════
         ENTRIES VIEW — Calendar UI (like Timesheet.vue)
    ════════════════════════════════════════════════════════════════ -->
    <template v-else-if="view === 'entries'">

      <div class="mhr-page-head">
        <div>
          <h1 class="mhr-page-head__title">
            {{ activeTimesheet.employeeName }}
            <span style="font-weight:400;color:var(--mhr-ink-3);font-size:16px;">({{ activeTimesheet.period }})</span>
          </h1>
          <p class="mhr-page-head__sub">
            <template v-if="isApprovedReadOnly">
              Viewing approved timesheet entries (read-only)
            </template>
            <template v-else-if="isEmployee && disableSubmission">
              Viewing timesheet entries (read-only)
            </template>
            <template v-else>
              Click on any day to mark it as worked or unpaid leave
            </template>
          </p>
        </div>
        <div class="mhr-page-head__actions">
          <button class="mhr-btn mhr-btn--ghost" @click="backToList">
            {{ (isApprovedReadOnly || (isEmployee && disableSubmission) || hrRole === 'manager') ? 'Close' : 'Cancel' }}
          </button>
          <button 
            v-if="!isApprovedReadOnly && !(isEmployee && disableSubmission) && hrRole !== 'manager'"
            class="mhr-btn mhr-btn--outline" 
            @click="saveEntries" 
            :disabled="isSavingEntries || isSubmitting">
            <AppIcon name="check" :size="14" />
            {{ isSavingEntries ? 'Saving…' : 'Save' }}
          </button>
          <button 
            v-if="activeTimesheet?.statusId === 1 && !isApprovedReadOnly && !(isEmployee && disableSubmission) && hrRole !== 'manager'" 
            class="mhr-btn mhr-btn--primary" 
            @click="submitForApproval" 
            :disabled="isSavingEntries || isSubmitting">
            <AppIcon name="arrowup" :size="14" />
            {{ isSubmitting ? 'Submitting…' : 'Submit for Approval' }}
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="mhr-grid-4" style="margin-bottom:24px;">
        <div class="mhr-stat">
          <div class="mhr-stat__label">Days Worked</div>
          <div class="mhr-stat__value"><em>{{ summary.workedDays }}</em></div>
          <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">regular working days</div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">Paid Leave</div>
          <div class="mhr-stat__value"><em>{{ summary.leaveDays }}</em></div>
          <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">{{ summary.leaveDays ? 'approved leave' : 'none' }}</div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">Unpaid Leave</div>
          <div class="mhr-stat__value"><em>{{ summary.unpaidDays }}</em></div>
          <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">{{ summary.unpaidDays ? summary.unpaidDays + ' days' : 'none' }}</div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">Payment</div>
          <div class="mhr-stat__value"><em>{{ calculatedPayment }}</em></div>
          <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">{{ summary.unpaidDays > 0 ? 'after unpaid deductions' : 'total compensation' }}</div>
        </div>
      </div>

      <!-- Calendar -->
      <div class="mhr-card">
        <!-- Legend -->
        <div class="mhr-card__hd">
          <h3 class="mhr-card__title">{{ activeTimesheet.period }}</h3>
          <div class="mhr-card__hd-actions">
            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--mhr-ink-3);">
              <span class="ts-dot ts-dot--W" />
              Worked
            </div>
            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--mhr-ink-3);">
              <span class="ts-dot ts-dot--L" />
              Leave
            </div>
            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--mhr-ink-3);">
              <span class="ts-dot ts-dot--U" />
              Unpaid
            </div>
            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--mhr-ink-3);">
              <span class="ts-dot ts-dot--0" />
              Weekend
            </div>
          </div>
        </div>

        <div style="padding:16px 20px 20px;">
          <!-- Weekday headers -->
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px;">
            <div v-for="wd in WEEKDAYS" :key="wd"
              style="text-align:center;font-size:11px;font-weight:600;color:var(--mhr-ink-4);text-transform:uppercase;letter-spacing:0.06em;padding:4px 0;">
              {{ wd }}
            </div>
          </div>

          <!-- Day grid -->
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
            <div v-for="i in firstDayOffset()" :key="'b'+i" class="ts-cell ts-cell--blank" style="opacity:0;" />
            <div v-for="day in entryRows" :key="day.day"
              class="ts-cell"
              :class="[cellClass(day), canEdit(day) ? 'ts-cell--clickable' : '']"
              @click="openDay(day)">
              <span class="ts-cell__date">{{ day.day }}</span>
              <span class="ts-cell__label">{{ cellLabel(day) }}</span>
            </div>
            <div v-for="i in lastDayOffset()" :key="'e'+i" class="ts-cell ts-cell--blank" style="opacity:0;" />
          </div>

          <!-- Footer summary -->
          <div style="display:flex;align-items:center;gap:16px;margin-top:18px;padding-top:14px;border-top:1px solid var(--mhr-line-2);font-size:12px;color:var(--mhr-ink-3);">
            <span>
              <strong style="color:var(--mhr-ink);">{{ summary.workedDays }}</strong> days worked ·
              <strong style="color:var(--mhr-ink);">{{ summary.leaveDays }}</strong> leave days ·
              <strong style="color:var(--mhr-ink);">{{ summary.unpaidDays }}</strong> unpaid days
            </span>
            <span style="margin-left:auto;color:var(--mhr-ink-3);font-style:italic;">Click any day to edit</span>
          </div>
        </div>
      </div>

    </template>

    <!-- ════════════════════════════════════════════════════════════
         DAY EDIT MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div v-if="editingDay" class="mhr-modal__scrim" @click.self="closeModal">
      <div class="mhr-modal" style="max-width:460px;">
        <div class="mhr-modal__hd">
          <div>
            <h2 class="mhr-modal__title">
              Edit Day · <em style="color:var(--green-600);font-style:italic;">
                {{ activeTimesheet.period }}-{{ String(editingDay.day).padStart(2, '0') }}
              </em>
            </h2>
            <p class="mhr-modal__sub">Select how this day should be marked on the timesheet.</p>
          </div>
        </div>
        <div class="mhr-modal__body">
          <!-- Action type cards -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <button v-for="t in ACTION_TYPES" :key="t.key"
              class="ts-type-card"
              :class="{ 'ts-type-card--active': editAction === t.key }"
              @click="editAction = t.key">
              <strong style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;">
                <AppIcon :name="t.icon" :size="14" />
                {{ t.label }}
              </strong>
              <span style="font-size:12px;color:var(--mhr-ink-3);margin-top:3px;">{{ t.sub }}</span>
            </button>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeModal">Cancel</button>
          <SubmitButton text="Save" @click="saveDay" />
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
         ADD TIMESHEET MODAL
         Fields mirror talentnest timesheet_modal.blade.php:
           - employee_id  (admin/manager only, required)
           - month_selected_id (required)
           - year_selected     (required)
    ════════════════════════════════════════════════════════════════ -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="showAddModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <div>
            <h2 class="mhr-modal__title">Add Employee Timesheet</h2>
            <p class="mhr-modal__sub">Select employee, month and year to create a timesheet record.</p>
          </div>
        </div>
        <div class="mhr-modal__body">

          <!-- Employee selector — admin or manager in team view, read-only for others -->
          <div v-if="hrRole === 'admin' || (hrRole === 'manager' && isTeamView)" class="mhr-field">
            <label class="mhr-field__label">Select Employee *</label>
            <EmployeeSelector
              v-model="addForm.employeeId"
              :employees="employees"
              placeholder="Select employee…"
              :required="true"
            />
            <p v-if="addErrors.employeeId" class="ts-field-error">{{ addErrors.employeeId }}</p>
          </div>
          <div v-else-if="currentEmployee" class="mhr-field">
            <label class="mhr-field__label">Employee</label>
            <div style="padding:10px 12px;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:var(--mhr-r);color:var(--mhr-ink-2);">
              {{ currentEmployee.full_name }} ({{ currentEmployee.employee_number }})
            </div>
          </div>

          <!-- Employee display for employees (read-only) -->
          <div v-else-if="isEmployee && currentEmployee" class="mhr-field">
            <label class="mhr-field__label">Employee</label>
            <div style="padding:10px 12px;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:var(--mhr-r);color:var(--mhr-ink-2);">
              {{ currentEmployee.full_name }} ({{ currentEmployee.employee_number }})
            </div>
          </div>

          <!-- Month -->
          <div class="mhr-field">
            <label class="mhr-field__label">Select Month *</label>
            <select class="mhr-select" v-model="addForm.monthId"
              :style="addErrors.monthId ? 'border-color:var(--mhr-danger);' : ''">
              <option value="">Select month…</option>
              <option v-for="m in monthsName" :key="m.id" :value="m.id">{{ m.monthName }}</option>
            </select>
            <p v-if="addErrors.monthId" class="ts-field-error">{{ addErrors.monthId }}</p>
          </div>

          <!-- Year -->
          <div class="mhr-field">
            <label class="mhr-field__label">Select Year *</label>
            <select class="mhr-select" v-model="addForm.year"
              :style="addErrors.year ? 'border-color:var(--mhr-danger);' : ''">
              <option value="">Select year…</option>
              <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
            <p v-if="addErrors.year" class="ts-field-error">{{ addErrors.year }}</p>
          </div>

        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showAddModal = false">Close</button>
          <SubmitButton text="Save" processing-text="Saving…" :processing="isAdding" @click="submitAdd" />
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
         CHANGE STATUS MODAL
         Mirrors timesheetStatusModal in talentnest:
           - status_id         (required)
           - additional_information
    ════════════════════════════════════════════════════════════════ -->
    <div v-if="showStatusModal" class="mhr-modal__scrim" @click.self="showStatusModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <div>
            <h2 class="mhr-modal__title">Change Timesheet Status</h2>
            <p class="mhr-modal__sub">{{ statusTarget?.employeeName }} — {{ statusTarget?.period }}</p>
          </div>
        </div>
        <div class="mhr-modal__body">

          <div class="mhr-field">
            <label class="mhr-field__label">Status *</label>
            <select class="mhr-select" v-model="statusForm.statusId"
              :style="statusErrors.statusId ? 'border-color:var(--mhr-danger);' : ''">
              <option value="">Select…</option>
              <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.title }}</option>
            </select>
            <p v-if="statusErrors.statusId" class="ts-field-error">{{ statusErrors.statusId }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Additional Information</label>
            <input class="mhr-input" type="text" v-model="statusForm.additionalInfo"
              placeholder="Optional notes…" />
          </div>

        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--danger" @click="showStatusModal = false">Cancel</button>
          <SubmitButton text="Save" processing-text="Saving…" :processing="isSavingStatus" @click="saveStatus" />
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
         SUBMIT CONFIRMATION MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div v-if="showSubmitModal" class="mhr-modal__scrim" @click.self="showSubmitModal = false">
      <div class="mhr-modal" style="max-width:520px;">
        <div class="mhr-modal__hd">
          <div>
            <h2 class="mhr-modal__title">Submit Timesheet for Approval?</h2>
            <p class="mhr-modal__sub">Review the summary below before submitting</p>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div style="padding:16px;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:8px;margin-bottom:16px;">
            <div style="display:grid;gap:12px;">
              <div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-bottom:4px;">Employee</div>
                <div style="font-size:14px;font-weight:500;color:var(--mhr-ink);">{{ activeTimesheet?.employeeName }}</div>
              </div>
              <div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-bottom:4px;">Period</div>
                <div style="font-size:14px;font-weight:500;color:var(--mhr-ink);">{{ activeTimesheet?.period }}</div>
              </div>
            </div>
          </div>
          
          <div style="padding:16px;background:var(--mhr-accent-bg);border:1px solid var(--mhr-accent);border-radius:8px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
              <AppIcon name="info" :size="16" style="color:var(--mhr-accent);" />
              <strong style="color:var(--mhr-accent);font-size:13px;">Timesheet Summary</strong>
            </div>
            <div style="display:grid;gap:8px;font-size:13px;color:var(--mhr-ink-2);">
              <div style="display:flex;justify-content:space-between;">
                <span>Days Worked:</span>
                <strong style="color:var(--mhr-ink);">{{ summary.workedDays }}</strong>
              </div>
              <div style="display:flex;justify-content:space-between;">
                <span>Paid Leave:</span>
                <strong style="color:var(--mhr-ink);">{{ summary.leaveDays }}</strong>
              </div>
              <div style="display:flex;justify-content:space-between;">
                <span>Unpaid Leave:</span>
                <strong style="color:var(--mhr-ink);">{{ summary.unpaidDays }}</strong>
              </div>
              <div style="height:1px;background:var(--mhr-accent);opacity:0.2;margin:4px 0;"></div>
              <div style="display:flex;justify-content:space-between;">
                <span style="font-weight:600;">Total Payment:</span>
                <strong style="color:var(--mhr-accent);font-size:15px;">{{ calculatedPayment }}</strong>
              </div>
            </div>
          </div>
          
          <p style="font-size:13px;color:var(--mhr-ink-2);margin:0;text-align:center;">
            Once submitted, this timesheet will be sent to your manager for approval.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showSubmitModal = false" :disabled="isSubmitting">
            Cancel
          </button>
          <SubmitButton text="Submit for Approval" processing-text="Submitting…" :processing="isSubmitting" @click="confirmSubmit" />
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
         DELETE CONFIRMATION MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal" style="max-width:480px;">
        <div class="mhr-modal__hd">
          <div>
            <h2 class="mhr-modal__title">Delete Timesheet</h2>
            <p class="mhr-modal__sub">Are you sure you want to delete this timesheet?</p>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div style="padding:16px;background:var(--mhr-warn-bg);border:1px solid var(--mhr-warn);border-radius:8px;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
              <AppIcon name="alert" :size="18" style="color:var(--mhr-warn);" />
              <strong style="color:var(--mhr-warn);font-size:14px;">Warning: This action cannot be undone</strong>
            </div>
            <p style="font-size:13px;color:var(--mhr-ink-2);margin:0;">
              You are about to delete the timesheet for <strong>{{ timesheetToDelete?.employeeName }}</strong> 
              for the period <strong>{{ timesheetToDelete?.period }}</strong>.
            </p>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false" :disabled="deletingId">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteTimesheet" :disabled="deletingId">
            <AppIcon name="trash" :size="14" />
            {{ deletingId ? 'Deleting…' : 'Delete Timesheet' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
         TOAST STACK
    ════════════════════════════════════════════════════════════════ -->
    <Teleport to=".meridian-app" v-if="toasts.length > 0">
      <div class="ts-toast-stack">
        <transition-group name="ts-toast">
          <div v-for="t in toasts" :key="t.id"
            class="ts-toast"
            :class="t.type === 'error' ? 'ts-toast--error' : 'ts-toast--success'">
            <AppIcon :name="t.type === 'error' ? 'alert' : 'check'" :size="15" />
            {{ t.message }}
          </div>
        </transition-group>
      </div>
    </Teleport>

  </div>
</template>

<style scoped>
/* ── Disabled button ──────────────────────────────────────── */
.mhr-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

/* ── Calendar cells ─────────────────────────────────────────── */
.ts-cell {
  /* aspect-ratio: 4/3; */
  border-radius: 6px;
  padding: 5px 7px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-start;
  border: 1px solid var(--mhr-line);
  user-select: none;
  transition: filter 0.12s;
}
.ts-cell--clickable { cursor: pointer; }
.ts-cell--clickable:hover { filter: brightness(0.94); }

.ts-cell__date {
  font-size: 16px;
  font-weight: 600;
  opacity: 0.65;
  color: inherit;
}
.ts-cell__label {
  font-size: 13px;
  font-weight: 500;
  color: inherit;
}

.ts-cell--worked  { background: var(--green-700);      color: #fff;                      border-color: transparent; }
.ts-cell--leave   { background: var(--mhr-accent-soft); color: var(--mhr-accent-ink);     border-color: transparent; }
.ts-cell--unpaid  { background: var(--mhr-warn-bg);    color: var(--mhr-warn);            border-color: transparent; }
.ts-cell--weekend { background: var(--mhr-surface-2);  color: var(--mhr-ink-4);           border-color: var(--mhr-line); }
.ts-cell--blank   { background: transparent;           color: var(--mhr-ink-4);           border-color: var(--mhr-line); }

/* ── Legend dots ────────────────────────────────────────────── */
.ts-dot {
  width: 10px;
  height: 10px;
  border-radius: 3px;
  display: inline-block;
  flex-shrink: 0;
}
.ts-dot--W { background: var(--green-700); }
.ts-dot--L { background: var(--mhr-accent-soft); border: 1px solid var(--mhr-accent); }
.ts-dot--U { background: var(--mhr-warn-bg);     border: 1px solid var(--mhr-warn); }
.ts-dot--0 { background: var(--mhr-surface-2);   border: 1px solid var(--mhr-line); }

/* ── Type selector cards ────────────────────────────────────── */
.ts-type-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 14px 16px;
  border: 1.5px solid var(--mhr-line);
  border-radius: 10px;
  background: var(--mhr-surface);
  cursor: pointer;
  text-align: left;
  transition: border-color 0.12s, background 0.12s;
  width: 100%;
}
.ts-type-card:hover { border-color: var(--mhr-accent); }
.ts-type-card--active {
  border-color: var(--green-700);
  background: var(--mhr-accent-soft);
}

/* ── Mobile Cards ───────────────────────────────────────────── */
/* Hide by default, show on mobile */
.ts-mobile-cards {
  display: none;
}

.ts-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 56px 20px;
  text-align: center;
}

.ts-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ts-card__header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--mhr-line-2);
}

.ts-card__employee {
  display: flex;
  align-items: center;
  gap: 10px;
}

.ts-card__stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.ts-card__stat {
  text-align: center;
}

.ts-card__stat-label {
  font-size: 11px;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 500;
  margin-bottom: 4px;
}

.ts-card__stat-value {
  font-size: 20px;
  font-weight: 500;
  color: var(--mhr-ink);
}

.ts-card__financial {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  background: var(--mhr-surface-2);
  border-radius: 8px;
}

.ts-card__fin-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ts-card__fin-label {
  font-size: 12px;
  color: var(--mhr-ink-3);
  font-weight: 500;
}

.ts-card__fin-value {
  font-size: 13px;
  font-family: monospace;
  color: var(--mhr-ink-2);
}

.ts-card__approver {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--mhr-ink-3);
  font-style: italic;
}

.ts-card__actions {
  display: flex;
  gap: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--mhr-line-2);
}

/* Toast stack */
.ts-toast-stack {
  position: fixed;
  top: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
  align-items: center;
}

.ts-toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 18px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  pointer-events: auto;
  max-width: 360px;
}

.ts-toast--success {
  background: var(--mhr-accent);
  color: #fff;
}

.ts-toast--error {
  background: var(--mhr-danger);
  color: #fff;
}

.ts-toast-enter-active,
.ts-toast-leave-active {
  transition: all 0.25s ease;
}
.ts-toast-enter-from,
.ts-toast-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}

/* ── Mobile responsive ──────────────────────────────────────── */
@media (max-width: 768px) {
  /* Show cards, hide table on mobile */
  .mhr-table-container {
    display: none !important;
  }
  
  .ts-mobile-cards {
    display: block !important;
  }
  
  /* Stats grid - stack on mobile */
  .mhr-grid-4 {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 12px !important;
  }
  
  /* Compact stats style for entries view - match card stats */
  .mhr-stat {
    padding: 12px !important;
    text-align: center;
  }
  
  .mhr-stat__label {
    font-size: 11px !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px !important;
  }
  
  .mhr-stat__value {
    font-size: 20px !important;
    margin-bottom: 0 !important;
  }
  
  .mhr-stat__value + div {
    display: none !important; /* Hide description text on mobile */
  }
  
  /* Ensure content has proper padding on mobile */
  .mhr-content {
    padding: 12px !important;
  }
  
  /* Legend - wrap items */
  .mhr-card__hd {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 10px !important;
  }
  
  .mhr-card__hd-actions {
    flex-wrap: wrap !important;
    gap: 8px !important;
  }
  
  /* Calendar cells */
  .ts-cell {
    min-height: 60px;
    padding: 4px 6px;
  }
  
  .ts-cell__date {
    font-size: 14px;
  }
  
  .ts-cell__label {
    font-size: 11px;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  /* Calendar container adjustments */
  .mhr-card div[style*="padding:16px 20px 20px"] {
    padding: 12px !important;
  }
  
  /* Reduce calendar grid gaps */
  div[style*="grid-template-columns:repeat(7,1fr)"] {
    gap: 3px !important;
  }
  
  /* Ensure calendar stays within bounds */
  .mhr-card {
    overflow: hidden;
    max-width: 100%;
    box-sizing: border-box;
    margin-left: 0;
    margin-right: 0;
  }
  
  /* Calendar grid proper sizing */
  div[style*="display:grid;grid-template-columns:repeat(7,1fr)"] {
    max-width: 100%;
    box-sizing: border-box;
  }
  
  /* Page header - stack actions */
  .mhr-page-head {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
  }
  
  .mhr-page-head__actions {
    justify-content: flex-start !important;
  }
  
  /* Filters - stack vertically */
  div[style*="display:flex;gap:10px"] {
    flex-direction: column !important;
  }
  
  div[style*="max-width:280px"],
  select[style*="max-width:180px"] {
    max-width: 100% !important;
  }
  
  /* Modal adjustments */
  .mhr-modal {
    max-width: 95vw !important;
    margin: 10px !important;
  }
  
  /* Type cards in modal */
  .ts-type-card {
    padding: 12px 14px;
  }
  
  /* Toast positioning */
  .ts-toast-stack {
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 24px);
    max-width: 500px;
  }
  
  .ts-toast {
    max-width: 100%;
    width: 100%;
  }
}

@media (max-width: 480px) {
  /* Stats grid - 2 columns to match card stats layout */
  .mhr-grid-4 {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  
  /* Match entries view stats with card stats size */
  .mhr-stat {
    padding: 10px !important;
  }
  
  .mhr-stat__value {
    font-size: 18px !important;
  }
  
  /* Card stats - 2 columns on very small screens */
  .ts-card__stats {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  
  .ts-card {
    padding: 12px !important;
  }
  
  .ts-card__stat-value {
    font-size: 18px !important;
  }
  
  /* Tighter content padding */
  .mhr-content {
    padding: 8px !important;
  }
  
  /* Smaller calendar cells for very small screens */
  .ts-cell {
    min-height: 50px;
    padding: 3px 4px;
  }
  
  .ts-cell__date {
    font-size: 12px;
  }
  
  .ts-cell__label {
    font-size: 10px;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  /* Tighter calendar spacing */
  .mhr-card div[style*="padding:16px 20px 20px"] {
    padding: 8px !important;
  }
  
  div[style*="grid-template-columns:repeat(7,1fr)"] {
    gap: 2px !important;
  }
  
  /* Weekday headers more compact */
  div[style*="grid-template-columns:repeat(7,1fr)"] div[style*="font-size:11px"] {
    font-size: 10px !important;
    padding: 2px 0 !important;
  }
  
  /* Compact buttons */
  .mhr-btn {
    font-size: 13px !important;
    padding: 8px 14px !important;
  }
  
  /* Smaller stat text */
  .mhr-stat__value {
    font-size: 28px !important;
  }
  
  .mhr-stat__label {
    font-size: 12px !important;
  }
}
</style>
