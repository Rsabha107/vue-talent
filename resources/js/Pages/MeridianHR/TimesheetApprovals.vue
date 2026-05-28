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
            <th>Employee</th>
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
            <td>
              <div style="font-weight:500;color:var(--mhr-ink);">{{ item.emp }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ item.empId }}</div>
              <div v-if="!selectedEvent && item.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                <span>{{ item.eventName }}</span>
              </div>
            </td>
            <td>{{ item.period }}</td>
            <td>{{ item.worked }}</td>
            <td>{{ item.leave }}</td>
            <td>{{ item.unpaid }}</td>
            <td style="color:var(--mhr-ink-3);">{{ fmtDate(item.submitted) }}</td>
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
            <th>Employee</th>
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
            <td>
              <div style="font-weight:500;color:var(--mhr-ink);">{{ item.emp }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ item.empId }}</div>
              <div v-if="!selectedEvent && item.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                <span>{{ item.eventName }}</span>
              </div>
            </td>
            <td>{{ item.period }}</td>
            <td>{{ item.worked }}</td>
            <td>{{ item.leave }}</td>
            <td>{{ item.unpaid }}</td>
            <td style="color:var(--mhr-ink-3);">{{ fmtDate(item.approved) }}</td>
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
          >
            <AppIcon v-if="isProcessing" name="refresh" :size="14" class="icon-spin" />
            <span v-else>{{ confirmAction?.includes('approve') ? 'Confirm Approval' : 'Confirm Rejection' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast"><AppIcon name="check" /> {{ toast }}</div>
    </Transition>
  </div>
</template>
