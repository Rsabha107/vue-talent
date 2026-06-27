<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  submittedTimesheets: { type: Array, default: () => [] },
  payrollTimesheets:   { type: Array, default: () => [] },
  isAdmin:             { type: Boolean, default: false },
  hrRole:              { type: String, default: 'manager' },
  selectedEvent:       { type: Number, default: null },
})

// Manager approval state
const selectedManager  = ref(new Set())
// Payroll approval state
const selectedPayroll  = ref(new Set())

const toast     = ref(null)
const reviewing = ref(null)
const isProcessing = ref(false)
const isRefreshing = ref(false)
const showConfirmModal = ref(false)
const confirmAction = ref(null) // 'approve', 'reject', 'payroll-approve', 'payroll-reject'
const confirmIds = ref([])
const additionalInfo = ref('')
const activeTab = ref('manager') // 'manager' or 'payroll'
const detailTimesheet = ref(null) // For detail panel

const pending = computed(() => props.submittedTimesheets)
const payrollPending = computed(() => props.payrollTimesheets)

// Manager approval functions
function toggleAllManager() {
  if (selectedManager.value.size === pending.value.length) {
    selectedManager.value = new Set()
  } else {
    selectedManager.value = new Set(pending.value.map(i => i.id))
  }
}

function toggleManager(id) {
  const s = new Set(selectedManager.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedManager.value = s
}

// Payroll approval functions
function toggleAllPayroll() {
  if (selectedPayroll.value.size === payrollPending.value.length) {
    selectedPayroll.value = new Set()
  } else {
    selectedPayroll.value = new Set(payrollPending.value.map(i => i.id))
  }
}

function togglePayroll(id) {
  const s = new Set(selectedPayroll.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedPayroll.value = s
}

function approve(ids) {
  confirmAction.value = 'approve'
  confirmIds.value = ids
  additionalInfo.value = ''
  showConfirmModal.value = true
}

function reject(ids) {
  confirmAction.value = 'reject'
  confirmIds.value = ids
  additionalInfo.value = ''
  showConfirmModal.value = true
}

function payrollApprove(ids) {
  confirmAction.value = 'payroll-approve'
  confirmIds.value = ids
  additionalInfo.value = ''
  showConfirmModal.value = true
}

function payrollReject(ids) {
  confirmAction.value = 'payroll-reject'
  confirmIds.value = ids
  additionalInfo.value = ''
  showConfirmModal.value = true
}

function viewDetails(timesheet) {
  // Toggle: close if same timesheet is clicked again
  if (detailTimesheet.value && detailTimesheet.value.id === timesheet.id) {
    detailTimesheet.value = null
  } else {
    detailTimesheet.value = timesheet
  }
}

function closeDetails() {
  detailTimesheet.value = null
}

function confirmApprovalAction() {
  if (isProcessing.value) return
  
  const ids = confirmIds.value
  const action = confirmAction.value
  let route_name, payload_key
  
  if (action === 'payroll-approve') {
    route_name = 'hr.payroll.time.approve'
    payload_key = 'payroll_additional_information'
  } else if (action === 'payroll-reject') {
    route_name = 'hr.payroll.time.reject'
    payload_key = 'payroll_additional_information'
  } else if (action === 'approve') {
    route_name = 'hr.approvals.time.approve'
    payload_key = 'additional_information'
  } else {
    route_name = 'hr.approvals.time.reject'
    payload_key = 'additional_information'
  }
  
  isProcessing.value = true
  router.post(route(route_name), {
    ids: ids,
    [payload_key]: additionalInfo.value
  }, {
    onSuccess: () => {
      selectedManager.value = new Set()
      selectedPayroll.value = new Set()
      reviewing.value = null
      showConfirmModal.value = false
      additionalInfo.value = ''
      const actionLabel = action.includes('payroll') 
        ? (action === 'payroll-approve' ? 'approved by payroll' : 'rejected by payroll')
        : (action === 'approve' ? 'sent to payroll' : 'rejected')
      showToast(`${ids.length} timesheet${ids.length > 1 ? 's' : ''} ${actionLabel}`)
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || `Failed to process timesheets`)
    },
    onFinish: () => {
      isProcessing.value = false
    }
  })
}

function cancelConfirmation() {
  showConfirmModal.value = false
  confirmAction.value = null
  confirmIds.value = []
  additionalInfo.value = ''
}

function showToast(msg) {
  toast.value = msg
  setTimeout(() => { toast.value = null }, 3000)
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

function refreshData() {
  isRefreshing.value = true
  router.reload({
    only: ['submittedTimesheets', 'payrollTimesheets'],
    onFinish: () => {
      isRefreshing.value = false
    }
  })
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
  const firstEntry = detailTimesheet.value.entries[0]
  const firstDate = new Date(detailTimesheet.value.year, detailTimesheet.value.monthNumber - 1, 1)
  return firstDate.getDay() // 0 for Sunday, 1 for Monday, etc.
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Timesheet approvals</h1>
        <p class="mhr-page-head__sub">
          <span v-if="activeTab === 'manager'">{{ pending.length }} pending manager review</span>
          <span v-else>{{ payrollPending.length }} pending payroll review</span>
        </p>
      </div>
      <div class="mhr-page-head__actions" v-if="(activeTab === 'manager' && selectedManager.size === 0) || (activeTab === 'payroll' && selectedPayroll.size === 0)">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
      </div>
      <div v-if="activeTab === 'manager' && selectedManager.size > 0" class="mhr-page-head__actions">
        <span style="font-size:13px;color:var(--mhr-ink-3);">{{ selectedManager.size }} selected</span>
        <button class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([...selectedManager])" :disabled="isProcessing">
          <AppIcon name="x" /> Reject
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="approve([...selectedManager])" :disabled="isProcessing">
          <AppIcon name="check" /> Approve
        </button>
      </div>
      <div v-if="activeTab === 'payroll' && selectedPayroll.size > 0" class="mhr-page-head__actions">
        <span style="font-size:13px;color:var(--mhr-ink-3);">{{ selectedPayroll.size }} selected</span>
        <button class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="payrollReject([...selectedPayroll])" :disabled="isProcessing">
          <AppIcon name="x" /> Reject
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="payrollApprove([...selectedPayroll])" :disabled="isProcessing">
          <AppIcon name="check" /> Approve
        </button>
      </div>
    </div>

    <!-- Tabs (Admin only) -->
    <div v-if="isAdmin" style="display:flex;gap:4px;padding:3px;background:var(--mhr-surface-2);border:1px solid var(--mhr-line);border-radius:9px;margin-bottom:16px;width:fit-content;">
      <button
        @click="activeTab = 'manager'"
        :class="['mhr-btn', 'mhr-btn--sm', activeTab === 'manager' ? '' : 'mhr-btn--ghost']"
        style="padding:6px 14px;"
      >
        Manager Review
        <span v-if="pending.length > 0" class="mhr-badge" style="margin-left:6px;">{{ pending.length }}</span>
      </button>
      <button
        @click="activeTab = 'payroll'"
        :class="['mhr-btn', 'mhr-btn--sm', activeTab === 'payroll' ? '' : 'mhr-btn--ghost']"
        style="padding:6px 14px;"
      >
        Payroll Review
        <span v-if="payrollPending.length > 0" class="mhr-badge mhr-badge--success" style="margin-left:6px;">{{ payrollPending.length }}</span>
      </button>
    </div>

    <!-- Manager Approval Table -->
    <div v-show="activeTab === 'manager'" class="mhr-card">
      <table class="mhr-table">
        <thead>
          <tr>
            <th style="width:36px;">
              <span class="mhr-checkbox"
                :data-checked="selectedManager.size === pending.length && pending.length > 0 ? '1' : selectedManager.size > 0 ? 'indeterminate' : '0'"
                @click="toggleAllManager" />
            </th>
            <th>Staff</th>
            <th>Period</th>
            <th>Days Worked</th>
            <th>Leave</th>
            <th>Unpaid</th>
            <th>Submitted</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="pending.length === 0">
            <td colspan="8" style="text-align:center;padding:60px;color:var(--mhr-ink-3);">
              <div style="font-family:var(--mhr-font-display);font-size:20px;color:var(--mhr-ink);margin-bottom:6px;">All clear</div>
              No timesheets pending manager review
            </td>
          </tr>
          <tr v-for="item in pending" :key="item.id">
            <td>
              <span class="mhr-checkbox"
                :data-checked="selectedManager.has(item.id) ? '1' : '0'"
                @click="toggleManager(item.id)" />
            </td>
            <td @click="viewDetails(item)" style="cursor:pointer;">
              <div style="font-weight:500;color:var(--mhr-ink);">{{ item.emp }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ item.empId }}</div>
              <div v-if="!selectedEvent && item.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                <span>{{ item.eventName }}</span>
              </div>
            </td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.period }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.worked }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.leave }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.unpaid }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;color:var(--mhr-ink-3);">{{ fmtDate(item.submitted) }}</td>
            <td>
              <div style="display:flex;gap:6px;">
                <button class="mhr-btn mhr-btn--sm mhr-btn--ghost mhr-btn--danger" @click="reject([item.id])" :disabled="isProcessing">
                  Reject
                </button>
                <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="approve([item.id])" :disabled="isProcessing">
                  Approve
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payroll Approval Table -->
    <div v-show="activeTab === 'payroll' && isAdmin" class="mhr-card">
      <table class="mhr-table">
        <thead>
          <tr>
            <th style="width:36px;">
              <span class="mhr-checkbox"
                :data-checked="selectedPayroll.size === payrollPending.length && payrollPending.length > 0 ? '1' : selectedPayroll.size > 0 ? 'indeterminate' : '0'"
                @click="toggleAllPayroll" />
            </th>
            <th>Staff</th>
            <th>Period</th>
            <th>Days Worked</th>
            <th>Leave</th>
            <th>Unpaid</th>
            <th>Manager Approved</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="payrollPending.length === 0">
            <td colspan="8" style="text-align:center;padding:60px;color:var(--mhr-ink-3);">
              <div style="font-family:var(--mhr-font-display);font-size:20px;color:var(--mhr-ink);margin-bottom:6px;">All clear</div>
              No timesheets pending payroll review
            </td>
          </tr>
          <tr v-for="item in payrollPending" :key="item.id">
            <td>
              <span class="mhr-checkbox"
                :data-checked="selectedPayroll.has(item.id) ? '1' : '0'"
                @click="togglePayroll(item.id)" />
            </td>
            <td @click="viewDetails(item)" style="cursor:pointer;">
              <div style="font-weight:500;color:var(--mhr-ink);">{{ item.emp }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ item.empId }}</div>
              <div v-if="!selectedEvent && item.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                <span>{{ item.eventName }}</span>
              </div>
            </td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.period }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.worked }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.leave }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;">{{ item.unpaid }}</td>
            <td @click="viewDetails(item)" style="cursor:pointer;color:var(--mhr-ink-3);">{{ fmtDate(item.approved) }}</td>
            <td>
              <div style="display:flex;gap:6px;">
                <button class="mhr-btn mhr-btn--sm mhr-btn--ghost mhr-btn--danger" @click="payrollReject([item.id])" :disabled="isProcessing">
                  Reject
                </button>
                <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="payrollApprove([item.id])" :disabled="isProcessing">
                  Approve
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmModal" class="mhr-modal__scrim" @click.self="cancelConfirmation">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">
            <span v-if="confirmAction === 'approve'">Approve Timesheets</span>
            <span v-else-if="confirmAction === 'reject'">Reject Timesheets</span>
            <span v-else-if="confirmAction === 'payroll-approve'">Payroll Approval</span>
            <span v-else>Payroll Rejection</span>
          </h2>
          <p class="mhr-modal__sub">
            <span v-if="confirmAction === 'approve'">Send {{ confirmIds.length }} timesheet(s) to payroll review</span>
            <span v-else-if="confirmAction === 'reject'">Reject {{ confirmIds.length }} timesheet(s)</span>
            <span v-else-if="confirmAction === 'payroll-approve'">Final approval for {{ confirmIds.length }} timesheet(s)</span>
            <span v-else>Reject {{ confirmIds.length }} timesheet(s) from payroll</span>
          </p>
        </div>
        <div class="mhr-modal__body">
          <div v-if="confirmAction === 'reject' || confirmAction === 'payroll-reject'" style="background:var(--mhr-warn-bg);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--mhr-warn);display:flex;gap:8px;align-items:center;margin-bottom:16px;">
            <AppIcon name="alert" :size="14" /> This action will reject the selected timesheet(s)
          </div>
          
          <div class="mhr-field">
            <label class="mhr-field__label">
              Additional Information 
              <span style="color:var(--mhr-ink-3);font-weight:normal;">(Optional)</span>
            </label>
            <textarea 
              v-model="additionalInfo" 
              class="mhr-input" 
              rows="4"
              :placeholder="confirmAction?.includes('approve') ? 'Add notes about this approval...' : 'Explain the reason for rejection...'"
              style="resize:vertical;min-height:80px;"
            ></textarea>
            <p style="font-size:12px;color:var(--mhr-ink-3);margin-top:6px;">
              This information will be stored with the {{ confirmAction?.includes('approve') ? 'approval' : 'rejection' }} record.
            </p>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="cancelConfirmation" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            :class="['mhr-btn', confirmAction?.includes('approve') ? 'mhr-btn--primary' : 'mhr-btn--danger']" 
            @click="confirmApprovalAction" 
            :disabled="isProcessing"
            :style="isProcessing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              <span v-if="confirmAction?.includes('approve')">Processing...</span>
              <span v-else>Rejecting...</span>
            </span>
            <span v-else>{{ confirmAction?.includes('approve') ? 'Confirm Approval' : 'Confirm Rejection' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Detail Panel -->
    <Transition name="slide-panel">
      <div v-if="detailTimesheet" class="detail-panel-backdrop" @click.self="closeDetails">
        <div class="detail-panel">
          <div class="detail-panel__header">
            <div>
              <h3 style="font-size:18px;font-weight:600;margin:0;">{{ detailTimesheet.emp }}</h3>
              <p style="font-size:14px;color:var(--mhr-ink-3);margin:4px 0 0;">{{ detailTimesheet.period }}</p>
            </div>
            <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="closeDetails">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        
        <div class="detail-panel__body">
          <!-- Summary Stats -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
            <div style="background:var(--mhr-surface-2);border-radius:8px;padding:12px;">
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-bottom:4px;">Worked</div>
              <div style="font-size:24px;font-weight:600;color:var(--green-700);">{{ detailTimesheet.worked }}</div>
            </div>
            <div style="background:var(--mhr-surface-2);border-radius:8px;padding:12px;">
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-bottom:4px;">Leave</div>
              <div style="font-size:24px;font-weight:600;color:var(--mhr-accent);">{{ detailTimesheet.leave }}</div>
            </div>
            <div style="background:var(--mhr-surface-2);border-radius:8px;padding:12px;">
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-bottom:4px;">Unpaid</div>
              <div style="font-size:24px;font-weight:600;color:var(--mhr-warn);">{{ detailTimesheet.unpaid }}</div>
            </div>
          </div>
          
          <!-- Calendar Grid -->
          <div style="margin-bottom:16px;">
            <h4 style="font-size:14px;font-weight:600;margin:0 0 12px;color:var(--mhr-ink);">Daily Breakdown</h4>
            
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
          </div>
          
          <!-- Legend -->
          <div style="display:flex;gap:16px;flex-wrap:wrap;padding:16px;background:var(--mhr-surface-2);border-radius:8px;">
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
        </div>
      </div>
    </Transition>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast"><AppIcon name="check" /> {{ toast }}</div>
    </Transition>
  </div>
</template>

<style scoped>
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

/* Slide panel transition */
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

.slide-panel-enter-to,
.slide-panel-leave-from {
  background: rgba(0, 0, 0, 0.3);
}

.slide-panel-enter-to .detail-panel,
.slide-panel-leave-from .detail-panel {
  transform: translateX(0);
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
</style>
