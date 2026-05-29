<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  payrollTimesheets: { type: Array, default: () => [] },
})

const selectedIds = ref(new Set())
const isRefreshing = ref(false)
const showConfirmModal = ref(false)
const confirmAction = ref(null)
const confirmIds = ref([])
const additionalInfo = ref('')
const isProcessing = ref(false)
const toast = ref(null)
const detailTimesheet = ref(null)
const showSingleActionModal = ref(false)
const singleActionTimesheet = ref(null)
const singleActionType = ref(null)

// Filters
const searchQuery = ref('')
const filterMonth = ref('')
const filterYear = ref('')

const filteredTimesheets = computed(() => {
  let result = props.payrollTimesheets

  // Filter by search query (employee name or number)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(ts => 
      ts.employeeName.toLowerCase().includes(query) ||
      ts.employeeNumber.toLowerCase().includes(query)
    )
  }

  // Filter by month
  if (filterMonth.value) {
    result = result.filter(ts => ts.monthId === Number(filterMonth.value))
  }

  // Filter by year
  if (filterYear.value) {
    result = result.filter(ts => ts.year === Number(filterYear.value))
  }

  return result
})

const allSelected = computed(() => {
  return filteredTimesheets.value.length > 0 && 
         selectedIds.value.size === filteredTimesheets.value.length
})

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value.clear()
  } else {
    filteredTimesheets.value.forEach(ts => selectedIds.value.add(ts.id))
  }
}

function toggleSelect(id) {
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id)
  } else {
    selectedIds.value.add(id)
  }
}

function openConfirmation(action) {
  if (selectedIds.value.size === 0) {
    showToast('Please select at least one timesheet', true)
    return
  }
  confirmAction.value = action
  confirmIds.value = Array.from(selectedIds.value)
  showConfirmModal.value = true
}

function cancelConfirmation() {
  showConfirmModal.value = false
  confirmAction.value = null
  confirmIds.value = []
  additionalInfo.value = ''
}

function confirmApprovalAction() {
  // Validate rejection requires a reason
  if (confirmAction.value === 'reject' && !additionalInfo.value.trim()) {
    showToast('Please provide a reason for rejection', true)
    return
  }

  isProcessing.value = true
  
  const routeName = confirmAction.value === 'approve' 
    ? 'payroll.timesheets.bulk-approve'
    : 'payroll.timesheets.bulk-reject'
  
  router.post(route(routeName), {
    timesheet_ids: confirmIds.value,
    additional_information: additionalInfo.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value.clear()
      cancelConfirmation()
      showToast(`${confirmIds.value.length} timesheet(s) ${confirmAction.value === 'approve' ? 'approved' : 'rejected'} successfully`)
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Action failed', true)
    },
    onFinish: () => {
      isProcessing.value = false
    }
  })
}

function viewDetails(ts) {
  detailTimesheet.value = ts
}

function closeDetails() {
  detailTimesheet.value = null
}

function openSingleAction(ts, actionType) {
  singleActionTimesheet.value = ts
  singleActionType.value = actionType
  additionalInfo.value = ''
  showSingleActionModal.value = true
}

function cancelSingleAction() {
  showSingleActionModal.value = false
  singleActionTimesheet.value = null
  singleActionType.value = null
  additionalInfo.value = ''
}

function confirmSingleAction() {
  // Validate rejection requires a reason
  if (singleActionType.value === 'reject' && !additionalInfo.value.trim()) {
    showToast('Please provide a reason for rejection', true)
    return
  }

  isProcessing.value = true
  
  const routeName = singleActionType.value === 'approve' 
    ? 'payroll.timesheets.approve'
    : 'payroll.timesheets.reject'
  
  router.post(route(routeName, singleActionTimesheet.value.id), {
    additional_information: additionalInfo.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cancelSingleAction()
      showToast(`Timesheet ${singleActionType.value === 'approve' ? 'approved' : 'rejected'} successfully`)
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Action failed', true)
    },
    onFinish: () => {
      isProcessing.value = false
    }
  })
}

function refreshTimesheets() {
  isRefreshing.value = true
  router.get(route('payroll.timesheets.review'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function fmtMoney(n) {
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
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

function getFirstDayOffset() {
  if (!detailTimesheet.value || !detailTimesheet.value.entries || detailTimesheet.value.entries.length === 0) {
    return 0
  }
  // Get the day of week for the first day of the month (0 = Sunday, 6 = Saturday)
  const firstDate = new Date(detailTimesheet.value.year, detailTimesheet.value.monthNumber - 1, 1)
  return firstDate.getDay() // 0 for Sunday, 1 for Monday, etc.
}

</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Timesheet Review</h1>
        <p class="mhr-page-head__sub">Final approval of manager-approved timesheets</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshTimesheets" />
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
      <div v-if="searchQuery || filterMonth || filterYear" class="filter-clear">
        <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="searchQuery = ''; filterMonth = ''; filterYear = ''">
          <AppIcon name="x" :size="14" /> Clear Filters
        </button>
      </div>
    </div>

    <!-- Bulk actions bar -->
    <div v-if="selectedIds.size > 0" class="bulk-actions-bar">
      <div class="bulk-actions-bar__info">
        <AppIcon name="check" :size="14" />
        {{ selectedIds.size }} timesheet(s) selected
      </div>
      <div class="bulk-actions-bar__actions">
        <button class="mhr-btn mhr-btn--sm mhr-btn--outline" @click="openConfirmation('reject')">
          <AppIcon name="x" :size="14" /> Reject Selected
        </button>
        <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="openConfirmation('approve')">
          <AppIcon name="check" :size="14" /> Approve Selected
        </button>
      </div>
    </div>

    <!-- Timesheets Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th style="width:40px;">
                <input 
                  type="checkbox" 
                  :checked="allSelected" 
                  @change="toggleSelectAll"
                  class="mhr-checkbox"
                />
              </th>
              <th>EMPLOYEE</th>
              <th>PERIOD</th>
              <th style="text-align:right;">WORKED</th>
              <th style="text-align:right;">LEAVE</th>
              <th style="text-align:right;">UNPAID</th>
              <th style="text-align:right;">PAYMENT</th>
              <th>MANAGER</th>
              <th style="width:140px;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredTimesheets.length === 0">
              <td colspan="9" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                <span v-if="searchQuery || filterMonth || filterYear">No timesheets match your filters.</span>
                <span v-else>No timesheets pending payroll review.</span>
              </td>
            </tr>
            <tr v-for="ts in filteredTimesheets" :key="ts.id">
              <td>
                <input 
                  type="checkbox" 
                  :checked="selectedIds.has(ts.id)" 
                  @change="toggleSelect(ts.id)"
                  class="mhr-checkbox"
                />
              </td>
              <td>
                <div style="font-weight:500;">{{ ts.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);">{{ ts.employeeNumber }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);">{{ ts.period }}</td>
              <td style="text-align:right;">{{ ts.daysWorked }}</td>
              <td style="text-align:right;">{{ ts.leaveTaken }}</td>
              <td style="text-align:right;">{{ ts.unpaidLeave }}</td>
              <td style="text-align:right;font-weight:600;color:var(--mhr-ink);font-family:monospace;">{{ fmtMoney(ts.totalPayment) }}</td>
              <td style="color:var(--mhr-ink-3);font-size:12px;">{{ ts.approverName }}</td>
              <td>
                <div style="display:flex;gap:4px;align-items:center;">
                  <button 
                    class="mhr-btn mhr-btn--ghost mhr-btn--sm" 
                    @click="viewDetails(ts)"
                    title="View details"
                  >
                    <AppIcon name="eye" :size="14" />
                  </button>
                  <button 
                    class="mhr-btn mhr-btn--sm" 
                    style="background:var(--green-50);color:var(--green-700);border:1px solid var(--green-200);"
                    @click="openSingleAction(ts, 'approve')"
                    title="Approve"
                  >
                    <AppIcon name="check" :size="14" />
                  </button>
                  <button 
                    class="mhr-btn mhr-btn--sm" 
                    style="background:var(--mhr-danger-bg);color:var(--mhr-danger);border:1px solid var(--mhr-danger);"
                    @click="openSingleAction(ts, 'reject')"
                    title="Reject"
                  >
                    <AppIcon name="x" :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toast" class="mhr-toast" :style="toast.isError ? 'background:var(--mhr-danger);' : ''">
      <AppIcon :name="toast.isError ? 'x' : 'check'" :size="16" />
      {{ toast.msg }}
    </div>

    <!-- Bulk Confirmation Modal -->
    <div v-if="showConfirmModal" class="mhr-modal__scrim" @click.self="cancelConfirmation">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">
            <span v-if="confirmAction === 'approve'">
              <AppIcon name="check" :size="20" style="color:var(--green-600);vertical-align:middle;margin-right:8px;" />
              Approve Timesheets
            </span>
            <span v-else>
              <AppIcon name="x" :size="20" style="color:var(--mhr-danger);vertical-align:middle;margin-right:8px;" />
              Reject Timesheets
            </span>
          </h2>
          <p class="mhr-modal__sub">
            <span v-if="confirmAction === 'approve'">Final approval for {{ confirmIds.length }} timesheet(s)</span>
            <span v-else>Reject {{ confirmIds.length }} timesheet(s)</span>
          </p>
        </div>
        <div class="mhr-modal__body">
          <!-- Warning Banner -->
          <div v-if="confirmAction === 'approve'" class="confirmation-banner confirmation-banner--success">
            <AppIcon name="check" :size="16" />
            <div>
              <strong>Bulk Approval</strong>
              <p>{{ confirmIds.length }} timesheet(s) will be marked as approved and ready for payment processing.</p>
            </div>
          </div>
          <div v-else class="confirmation-banner confirmation-banner--danger">
            <AppIcon name="alert" :size="16" />
            <div>
              <strong>Bulk Rejection</strong>
              <p>{{ confirmIds.length }} timesheet(s) will be rejected and sent back to employees for corrections.</p>
            </div>
          </div>
          
          <!-- Additional Information -->
          <div class="mhr-field">
            <label class="mhr-field__label">
              {{ confirmAction === 'approve' ? 'Approval Notes' : 'Rejection Reason' }}
              <span style="color:var(--mhr-ink-3);font-weight:normal;">{{ confirmAction === 'reject' ? '(Required)' : '(Optional)' }}</span>
            </label>
            <textarea 
              v-model="additionalInfo" 
              class="mhr-input" 
              rows="4"
              :placeholder="confirmAction === 'approve' ? 'Add notes about this approval (optional)...' : 'Explain why these timesheets are being rejected (required)...'"
              style="resize:vertical;min-height:100px;"
            ></textarea>
            <p style="font-size:12px;color:var(--mhr-ink-3);margin-top:6px;">
              <AppIcon name="info" :size="12" style="vertical-align:middle;" />
              {{ confirmAction === 'approve' ? 'Notes will be stored with all approval records.' : 'This reason will be visible to all affected employees and their managers.' }}
            </p>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="cancelConfirmation" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            :class="['mhr-btn', confirmAction === 'approve' ? 'mhr-btn--primary' : 'mhr-btn--danger']" 
            @click="confirmApprovalAction" 
            :disabled="isProcessing || (confirmAction === 'reject' && !additionalInfo.trim())"
            :style="(isProcessing || (confirmAction === 'reject' && !additionalInfo.trim())) ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              <span>{{ confirmAction === 'approve' ? 'Approving...' : 'Rejecting...' }}</span>
            </span>
            <span v-else>
              <AppIcon :name="confirmAction === 'approve' ? 'check' : 'x'" :size="14" style="vertical-align:middle;margin-right:4px;" />
              {{ confirmAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection' }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Single Action Confirmation Modal -->
    <div v-if="showSingleActionModal" class="mhr-modal__scrim" @click.self="cancelSingleAction">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">
            <span v-if="singleActionType === 'approve'">
              <AppIcon name="check" :size="20" style="color:var(--green-600);vertical-align:middle;margin-right:8px;" />
              Approve Timesheet
            </span>
            <span v-else>
              <AppIcon name="x" :size="20" style="color:var(--mhr-danger);vertical-align:middle;margin-right:8px;" />
              Reject Timesheet
            </span>
          </h2>
          <p class="mhr-modal__sub">
            <span v-if="singleActionTimesheet">{{ singleActionTimesheet.employeeName }} - {{ singleActionTimesheet.period }}</span>
          </p>
        </div>
        <div class="mhr-modal__body">
          <!-- Warning Banner -->
          <div v-if="singleActionType === 'approve'" class="confirmation-banner confirmation-banner--success">
            <AppIcon name="check" :size="16" />
            <div>
              <strong>Final Approval</strong>
              <p>This timesheet will be marked as approved and ready for payment processing.</p>
            </div>
          </div>
          <div v-else class="confirmation-banner confirmation-banner--danger">
            <AppIcon name="alert" :size="16" />
            <div>
              <strong>Rejection Notice</strong>
              <p>This timesheet will be rejected and sent back to the employee for corrections.</p>
            </div>
          </div>
          
          <!-- Summary Info -->
          <div v-if="singleActionTimesheet" class="timesheet-summary">
            <div class="timesheet-summary__item">
              <span class="timesheet-summary__label">Days Worked:</span>
              <span class="timesheet-summary__value">{{ singleActionTimesheet.daysWorked }}</span>
            </div>
            <div class="timesheet-summary__item">
              <span class="timesheet-summary__label">Leave Taken:</span>
              <span class="timesheet-summary__value">{{ singleActionTimesheet.leaveTaken }}</span>
            </div>
            <div class="timesheet-summary__item">
              <span class="timesheet-summary__label">Total Payment:</span>
              <span class="timesheet-summary__value timesheet-summary__value--payment">{{ fmtMoney(singleActionTimesheet.totalPayment) }}</span>
            </div>
          </div>

          <!-- Additional Information -->
          <div class="mhr-field">
            <label class="mhr-field__label">
              {{ singleActionType === 'approve' ? 'Approval Notes' : 'Rejection Reason' }}
              <span style="color:var(--mhr-ink-3);font-weight:normal;">{{ singleActionType === 'reject' ? '(Required)' : '(Optional)' }}</span>
            </label>
            <textarea 
              v-model="additionalInfo" 
              class="mhr-input" 
              rows="4"
              :placeholder="singleActionType === 'approve' ? 'Add notes about this approval (optional)...' : 'Explain why this timesheet is being rejected (required)...'"
              style="resize:vertical;min-height:100px;"
            ></textarea>
            <p style="font-size:12px;color:var(--mhr-ink-3);margin-top:6px;">
              <AppIcon name="info" :size="12" style="vertical-align:middle;" />
              {{ singleActionType === 'approve' ? 'Notes will be stored with the approval record.' : 'This reason will be visible to the employee and their manager.' }}
            </p>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="cancelSingleAction" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            :class="['mhr-btn', singleActionType === 'approve' ? 'mhr-btn--primary' : 'mhr-btn--danger']" 
            @click="confirmSingleAction" 
            :disabled="isProcessing || (singleActionType === 'reject' && !additionalInfo.trim())"
            :style="(isProcessing || (singleActionType === 'reject' && !additionalInfo.trim())) ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              <span>{{ singleActionType === 'approve' ? 'Approving...' : 'Rejecting...' }}</span>
            </span>
            <span v-else>
              <AppIcon :name="singleActionType === 'approve' ? 'check' : 'x'" :size="14" style="vertical-align:middle;margin-right:4px;" />
              {{ singleActionType === 'approve' ? 'Confirm Approval' : 'Confirm Rejection' }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Detail Panel (Placeholder for calendar view) -->
    <Transition name="slide-panel">
      <div v-if="detailTimesheet" class="detail-panel-backdrop" @click.self="closeDetails">
        <div class="detail-panel">
          <div class="detail-panel__header">
            <div>
              <h3 style="font-size:18px;font-weight:600;margin:0;">{{ detailTimesheet.employeeName }}</h3>
              <p style="font-size:14px;color:var(--mhr-ink-3);margin:4px 0 0;">{{ detailTimesheet.period }}</p>
            </div>
            <button class="mhr-icon-btn" @click="closeDetails">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
          <div class="detail-panel__body">
            <div class="mhr-field">
              <label class="mhr-field__label">SUMMARY</label>
              <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
                <div style="padding:12px;background:var(--mhr-surface);border-radius:8px;">
                  <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;">Worked</div>
                  <div style="font-size:20px;font-weight:600;margin-top:4px;">{{ detailTimesheet.daysWorked }}</div>
                </div>
                <div style="padding:12px;background:var(--mhr-surface);border-radius:8px;">
                  <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;">Leave</div>
                  <div style="font-size:20px;font-weight:600;margin-top:4px;">{{ detailTimesheet.leaveTaken }}</div>
                </div>
                <div style="padding:12px;background:var(--mhr-surface);border-radius:8px;">
                  <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;">Unpaid</div>
                  <div style="font-size:20px;font-weight:600;margin-top:4px;">{{ detailTimesheet.unpaidLeave }}</div>
                </div>
                <div style="padding:12px;background:var(--green-100);border-radius:8px;">
                  <div style="font-size:11px;color:var(--green-800);text-transform:uppercase;letter-spacing:0.5px;">Payment</div>
                  <div style="font-size:20px;font-weight:600;margin-top:4px;color:var(--green-800);">{{ fmtMoney(detailTimesheet.totalPayment) }}</div>
                </div>
              </div>
            </div>

            <div v-if="detailTimesheet.additionalInfo" class="mhr-field">
              <label class="mhr-field__label">MANAGER NOTES</label>
              <div style="padding:12px;background:var(--mhr-surface);border-radius:8px;font-size:13px;color:var(--mhr-ink-2);">
                {{ detailTimesheet.additionalInfo }}
              </div>
            </div>

            <!-- Calendar View -->
            <div v-if="detailTimesheet.entries" class="mhr-field">
              <label class="mhr-field__label">DAILY BREAKDOWN</label>
              
              <!-- Day headers -->
              <div class="calendar-grid" style="margin-bottom:4px;">
                <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" 
                  style="text-align:center;font-size:11px;font-weight:600;color:var(--mhr-ink-3);padding:8px 0;">
                  {{ day }}
                </div>
              </div>
              
              <!-- Calendar days with proper week alignment -->
              <div class="calendar-grid">
                <!-- Empty cells before month starts -->
                <div v-for="i in getFirstDayOffset()" :key="'empty-' + i" class="calendar-day calendar-day--empty"></div>
                
                <!-- Actual days -->
                <div v-for="entry in detailTimesheet.entries" :key="entry.day"
                  :class="['calendar-day', getCellClass(entry)]"
                  :title="getDayTitle(entry)">
                  <div class="calendar-day__number">{{ entry.day }}</div>
                  <div class="calendar-day__name">{{ entry.dayName }}</div>
                </div>
              </div>
              
              <!-- Legend -->
              <div style="display:flex;gap:16px;flex-wrap:wrap;padding:16px;background:var(--mhr-surface-2);border-radius:8px;margin-top:12px;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <div style="width:16px;height:16px;background:var(--green-700);border-radius:4px;"></div>
                  <span style="font-size:13px;color:var(--mhr-ink-2);">Worked (W)</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div style="width:16px;height:16px;background:var(--mhr-accent-soft);border-radius:4px;"></div>
                  <span style="font-size:13px;color:var(--mhr-ink-2);">Leave (L)</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div style="width:16px;height:16px;background:var(--mhr-warn-bg);border-radius:4px;"></div>
                  <span style="font-size:13px;color:var(--mhr-ink-2);">Unpaid (U)</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div style="width:16px;height:16px;background:var(--mhr-surface-2);border:1px solid var(--mhr-line);border-radius:4px;"></div>
                  <span style="font-size:13px;color:var(--mhr-ink-2);">Weekend</span>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="mhr-field">
              <div style="display:flex;gap:8px;padding-top:8px;">
                <button 
                  class="mhr-btn mhr-btn--outline" 
                  style="flex:1;"
                  @click="closeDetails()"
                >
                  Close
                </button>
                <button 
                  class="mhr-btn mhr-btn--danger" 
                  style="flex:1;"
                  @click="closeDetails(); openSingleAction(detailTimesheet, 'reject')"
                >
                  <AppIcon name="x" :size="14" /> Reject
                </button>
                <button 
                  class="mhr-btn mhr-btn--primary" 
                  style="flex:1;"
                  @click="closeDetails(); openSingleAction(detailTimesheet, 'approve')"
                >
                  <AppIcon name="check" :size="14" /> Approve
                </button>
              </div>
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
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: 8px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-input {
  width: 100%;
  font-size: 13px;
}

.filter-select {
  width: 100%;
  font-size: 13px;
}

.filter-clear {
  flex-shrink: 0;
}

/* Bulk actions bar */
.bulk-actions-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--mhr-accent-soft);
  border: 1px solid var(--mhr-accent);
  border-radius: 8px;
  margin-bottom: 16px;
}

.bulk-actions-bar__info {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: var(--mhr-accent);
}

.bulk-actions-bar__actions {
  display: flex;
  gap: 8px;
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
  width: 480px;
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
  padding: 20px 24px;
  border-bottom: 1px solid var(--mhr-line);
  background: var(--mhr-surface);
}

.detail-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
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

/* Spinner animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Calendar grid */
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
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

/* Confirmation banner */
.confirmation-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid;
}

.confirmation-banner--success {
  background: var(--green-50);
  border-color: var(--green-200);
  color: var(--green-800);
}

.confirmation-banner--danger {
  background: var(--mhr-danger-bg);
  border-color: var(--mhr-danger);
  color: var(--mhr-danger);
}

.confirmation-banner strong {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
}

.confirmation-banner p {
  font-size: 13px;
  margin: 0;
  line-height: 1.5;
}

/* Timesheet summary */
.timesheet-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 16px;
  background: var(--mhr-surface);
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid var(--mhr-line);
}

.timesheet-summary__item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.timesheet-summary__label {
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.timesheet-summary__value {
  font-size: 18px;
  font-weight: 600;
  color: var(--mhr-ink);
}

.timesheet-summary__value--payment {
  color: var(--green-700);
  font-family: monospace;
}
</style>
