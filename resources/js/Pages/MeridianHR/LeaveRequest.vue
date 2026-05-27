<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'
import EmployeeSelector from '@/Components/MeridianHR/EmployeeSelector.vue'
import EventBanner from '@/Components/MeridianHR/EventBanner.vue'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:         { type: String, default: 'admin' },
  leaveRequests:  { type: Array,  default: () => [] },
  employees:      { type: Array,  default: () => [] },
  leaveTypes:     { type: Array,  default: () => [] },
  statuses:       { type: Array,  default: () => [] },
  leaveBalances:  { type: Array,  default: () => [] },
})

const dateFormat = computed(() => usePage().props.dateFormat || 'DD/MM/YYYY')

// Event context
const selectedEventId = computed(() => usePage().props.selectedEvent)
const availableEvents = computed(() => usePage().props.availableEvents || [])
const selectedEventData = computed(() => {
  if (!selectedEventId.value) return null
  return availableEvents.value.find(e => e.id === selectedEventId.value)
})

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December']

function applyFormat(d, fmt) {
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

const q = ref('')
const statusFilter = ref('All')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showViewModal = ref(false)
const editingRequest = ref(null)
const viewingRequest = ref(null)
const requestToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)

const getPendingStatusId = () => {
  const pending = props.statuses.find(s => s.title?.toLowerCase() === 'pending')
  return pending ? pending.id : null
}

const form = useForm({
  employee_id: null,
  leave_type_id: null,
  number_of_days: null,
  date_from: '',
  date_to: '',
  reason: '',
  status_id: getPendingStatusId(),
  additional_information: '',
})

const editForm = useForm({
  id: null,
  employee_id: null,
  leave_type_id: null,
  number_of_days: null,
  date_from: '',
  date_to: '',
  reason: '',
  status_id: null,
  performer_id: null,
  additional_information: '',
})

const statusOptions = computed(() => ['All', ...new Set(props.leaveRequests.map(r => r.statusTitle).filter(Boolean))])

// Get leave balance for selected employee and leave type (Add modal)
const currentBalance = computed(() => {
  if (!form.employee_id || !form.leave_type_id) return null
  return props.leaveBalances.find(
    b => b.employee_id === form.employee_id && b.leave_type_id === form.leave_type_id
  )
})

// Get leave balance for selected employee and leave type (Edit modal)
const editCurrentBalance = computed(() => {
  if (!editForm.employee_id || !editForm.leave_type_id) return null
  return props.leaveBalances.find(
    b => b.employee_id === editForm.employee_id && b.leave_type_id === editForm.leave_type_id
  )
})

const isApproved = computed(() => {
  return editingRequest.value?.statusTitle?.toLowerCase() === 'approved'
})

const filtered = computed(() => {
  let results = props.leaveRequests
  
  if (statusFilter.value !== 'All') {
    results = results.filter(r => r.statusTitle === statusFilter.value)
  }
  
  if (q.value) {
    const query = q.value.toLowerCase()
    results = results.filter(r =>
      r.employeeName?.toLowerCase().includes(query) ||
      r.employeeNumber?.toLowerCase().includes(query) ||
      r.leaveTypeTitle?.toLowerCase().includes(query) ||
      r.reason?.toLowerCase().includes(query)
    )
  }
  
  return results
})

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function fmtDate(s) {
  if (!s) return '—'
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : s)
  return applyFormat(d, dateFormat.value)
}

function calculateDays() {
  if (form.date_from && form.date_to) {
    const from = new Date(form.date_from)
    const to = new Date(form.date_to)
    const diffTime = Math.abs(to - from)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
    form.number_of_days = diffDays
  }
}

function calculateDaysEdit() {
  if (editForm.date_from && editForm.date_to) {
    const from = new Date(editForm.date_from)
    const to = new Date(editForm.date_to)
    const diffTime = Math.abs(to - from)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
    editForm.number_of_days = diffDays
  }
}

function resetAddForm() {
  form.reset()
  form.status_id = getPendingStatusId()
}

function resetEditForm() {
  editForm.reset()
  editingRequest.value = null
}

function closeAddModal() {
  showAddModal.value = false
  resetAddForm()
}

function closeEditModal() {
  showEditModal.value = false
  resetEditForm()
}

function closeViewModal() {
  showViewModal.value = false
  viewingRequest.value = null
}

function toDateObj(str) {
  return str ? new Date(str + 'T00:00:00') : null
}
function toDateStr(d) {
  if (!d) return ''
  return d instanceof Date
    ? d.toISOString().split('T')[0]
    : String(d).split('T')[0]
}

const addDateFrom = computed({
  get: () => toDateObj(form.date_from),
  set: (v) => { form.date_from = toDateStr(v); calculateDays() },
})
const addDateTo = computed({
  get: () => toDateObj(form.date_to),
  set: (v) => { form.date_to = toDateStr(v); calculateDays() },
})
const editDateFrom = computed({
  get: () => toDateObj(editForm.date_from),
  set: (v) => { editForm.date_from = toDateStr(v); calculateDaysEdit() },
})
const editDateTo = computed({
  get: () => toDateObj(editForm.date_to),
  set: (v) => { editForm.date_to = toDateStr(v); calculateDaysEdit() },
})

function addLeaveRequest() {
  form.post(route('hr.leave-requests.store'), {
    onSuccess: () => {
      closeAddModal()
      showToast('Leave request created successfully')
    },
    onError: (errors) => {
      if (errors.date_from) {
        showToast(errors.date_from, true)
      } else {
        showToast('Failed to create leave request', true)
      }
    },
  })
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function viewRequest(request) {
  viewingRequest.value = request
  showViewModal.value = true
  openMenuId.value = null
}

function editRequest(request) {
  editingRequest.value = request
  editForm.id = request.id
  editForm.employee_id = request.employeeId
  editForm.leave_type_id = request.leaveTypeId
  editForm.number_of_days = request.numberOfDays
  editForm.date_from = request.dateFrom || ''
  editForm.date_to = request.dateTo || ''
  editForm.reason = request.reason || ''
  editForm.status_id = request.statusId
  editForm.performer_id = request.performerId
  editForm.additional_information = request.additionalInformation || ''
  showEditModal.value = true
  openMenuId.value = null
}

function updateLeaveRequest() {
  editForm.put(route('hr.leave-requests.update', editForm.id), {
    onSuccess: () => {
      closeEditModal()
      showToast('Leave request updated successfully')
    },
    onError: (errors) => {
      if (errors.date_from) {
        showToast(errors.date_from, true)
      } else {
        showToast('Failed to update leave request', true)
      }
    },
  })
}

function confirmDelete(request) {
  // Don't allow deletion of approved leave requests
  if (request.statusTitle?.toLowerCase() === 'approved') {
    showToast('Cannot delete approved leave requests', true)
    return
  }
  requestToDelete.value = request
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteLeaveRequest() {
  // Additional safety check
  if (requestToDelete.value?.statusTitle?.toLowerCase() === 'approved') {
    showToast('Cannot delete approved leave requests', true)
    showDeleteModal.value = false
    return
  }
  router.delete(route('hr.leave-requests.destroy', requestToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      showToast('Leave request archived successfully')
    }
  })
}

function refreshLeaveRequests() {
  isRefreshing.value = true
  router.get(route('hr.leave-requests'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}
</script>

<template>
  <div @click="openMenuId = null">
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Leave Requests</h1>
        <p class="mhr-page-head__sub">Manage employee leave requests</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshLeaveRequests" />
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Leave Request
        </button>
      </div>
    </div>

    <!-- Event Context Banner -->
    <EventBanner 
      v-if="selectedEventData"
      :event-data="selectedEventData"
    />

    <!-- Filters -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search by employee, leave type, or reason…" v-model="q" />
      </div>
      <select class="mhr-select" style="width:180px;" v-model="statusFilter">
        <option v-for="st in statusOptions" :key="st" :value="st">{{ st }}</option>
      </select>
    </div>

    <!-- Leave Requests Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>EMPLOYEE</th>
              <th>LEAVE TYPE</th>
              <th>FROM</th>
              <th>TO</th>
              <th>DAYS</th>
              <th>STATUS</th>
              <th>SUBMITTED</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="8" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No leave requests found
              </td>
            </tr>
            <tr v-for="request in filtered" :key="request.id">
              <td>
                <div 
                  @click="viewRequest(request)" 
                  style="font-weight:500;color:var(--mhr-accent);cursor:pointer;" 
                  class="employee-name-link"
                >
                  {{ request.employeeName }}
                </div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ request.employeeNumber }}</div>
                <div v-if="!selectedEventId && request.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                  <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                  <span>{{ request.eventName }}</span>
                </div>
              </td>
              <td>
                <span class="mhr-badge mhr-badge--neutral">{{ request.leaveTypeTitle }}</span>
              </td>
              <td style="color:var(--mhr-ink-2);">{{ fmtDate(request.dateFrom) }}</td>
              <td style="color:var(--mhr-ink-2);">{{ fmtDate(request.dateTo) }}</td>
              <td>
                <span style="font-weight:500;color:var(--mhr-ink);">{{ request.numberOfDays }}</span>
              </td>
              <td>
                <StatusPill :status="request.statusTitle" />
              </td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">
                {{ fmtDate(request.createdAt) }}
              </td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(request.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div v-if="openMenuId === request.id" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:180px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;">
                    <button @click="viewRequest(request)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="eye" :size="14" />
                      <span>View Details</span>
                    </button>
                    <button v-if="request.statusTitle?.toLowerCase() !== 'approved'" @click="editRequest(request)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div v-if="request.statusTitle?.toLowerCase() !== 'approved'" style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button v-if="request.statusTitle?.toLowerCase() !== 'approved'" @click="confirmDelete(request)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="trash" :size="14" />
                      <span>Archive</span>
                    </button>
                  </div>
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

    <!-- Add Leave Request Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Leave Request</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new leave request</p>
            </div>
            <button class="mhr-icon-btn" @click="closeAddModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">EMPLOYEE *</label>
              <EmployeeSelector
                v-model="form.employee_id"
                :employees="employees"
                :required="true"
                placeholder="Search employee..."
              />
            </div>
            
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">LEAVE TYPE *</label>
              <select class="mhr-select" v-model="form.leave_type_id">
                <option :value="null">Select leave type...</option>
                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                  {{ type.title }}
                </option>
              </select>
            </div>

            <!-- Leave Balance Display -->
            <transition name="fade-slide">
              <div v-if="currentBalance" class="leave-balance-card" style="grid-column:1/-1;">
                <div class="leave-balance-header">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <AppIcon name="calendar" :size="16" />
                    <span>Leave Balance</span>
                  </div>
                  <button 
                    type="button"
                    @click="refreshLeaveRequests" 
                    :disabled="isRefreshing"
                    class="balance-refresh-btn"
                    title="Refresh balance"
                  >
                    <AppIcon 
                      name="refresh" 
                      :size="14" 
                      :style="{ transition: 'transform 0.5s', transform: isRefreshing ? 'rotate(360deg)' : 'rotate(0deg)' }" 
                    />
                  </button>
                </div>
                <div class="leave-balance-stats">
                  <div class="balance-stat">
                    <div class="balance-stat__value">{{ currentBalance.allocated_days }}</div>
                    <div class="balance-stat__label">Allocated</div>
                  </div>
                  <div class="balance-stat balance-stat--used">
                    <div class="balance-stat__value">{{ currentBalance.used_days }}</div>
                    <div class="balance-stat__label">Used</div>
                  </div>
                  <div class="balance-stat balance-stat--pending">
                    <div class="balance-stat__value">{{ currentBalance.pending_days }}</div>
                    <div class="balance-stat__label">Pending</div>
                  </div>
                  <div class="balance-stat balance-stat--available">
                    <div class="balance-stat__value">{{ currentBalance.available_days }}</div>
                    <div class="balance-stat__label">Available</div>
                  </div>
                </div>
                <div class="leave-balance-bar">
                  <div 
                    class="leave-balance-bar__fill leave-balance-bar__fill--used" 
                    :style="{ width: `${(currentBalance.used_days / currentBalance.allocated_days * 100)}%` }"
                  ></div>
                  <div 
                    class="leave-balance-bar__fill leave-balance-bar__fill--pending" 
                    :style="{ width: `${(currentBalance.pending_days / currentBalance.allocated_days * 100)}%` }"
                  ></div>
                </div>
              </div>
            </transition>

            <div class="mhr-field">
              <label class="mhr-field__label">DATE FROM *</label>
              <DatePicker v-model="addDateFrom" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
              <div v-if="form.errors.date_from" style="color:var(--mhr-danger);font-size:12px;margin-top:4px;">
                {{ form.errors.date_from }}
              </div>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">DATE TO *</label>
              <DatePicker v-model="addDateTo" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">NUMBER OF DAYS *</label>
              <input class="mhr-input" type="number" v-model="form.number_of_days" min="1" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="form.status_id">
                <option :value="null">Select status...</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">
                  {{ status.title }}
                </option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">REASON *</label>
              <textarea class="mhr-input" v-model="form.reason" rows="3" placeholder="Reason for leave request..."></textarea>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDITIONAL INFORMATION</label>
              <textarea class="mhr-input" v-model="form.additional_information" rows="2" placeholder="Any additional notes..."></textarea>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeAddModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="addLeaveRequest"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="form.processing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              Creating...
            </span>
            <span v-else>Create Request</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Leave Request Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Leave Request</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingRequest?.employeeName }}</p>
              <div v-if="isApproved" style="margin-top:8px;padding:8px 12px;background:var(--mhr-accent-soft);border-left:3px solid var(--mhr-accent);border-radius:4px;font-size:12px;color:var(--mhr-ink-2);">
                <strong style="color:var(--mhr-accent);">✓ Approved</strong> — This leave request has been approved and cannot be edited.
              </div>
            </div>
            <button class="mhr-icon-btn" @click="closeEditModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">EMPLOYEE *</label>
              <EmployeeSelector
                v-model="editForm.employee_id"
                :employees="employees"
                :required="true"
                placeholder="Search employee..."
                :disabled="isApproved"
              />
            </div>
            
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">LEAVE TYPE *</label>
              <select class="mhr-select" v-model="editForm.leave_type_id" :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''">
                <option :value="null">Select leave type...</option>
                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                  {{ type.title }}
                </option>
              </select>
            </div>

            <!-- Leave Balance Display (Edit Modal) -->
            <transition name="fade-slide">
              <div v-if="editCurrentBalance" class="leave-balance-card" style="grid-column:1/-1;">
                <div class="leave-balance-header">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <AppIcon name="calendar" :size="16" />
                    <span>Leave Balance</span>
                  </div>
                  <button 
                    type="button"
                    @click="refreshLeaveRequests" 
                    :disabled="isRefreshing"
                    class="balance-refresh-btn"
                    title="Refresh balance"
                  >
                    <AppIcon 
                      name="refresh" 
                      :size="14" 
                      :style="{ transition: 'transform 0.5s', transform: isRefreshing ? 'rotate(360deg)' : 'rotate(0deg)' }" 
                    />
                  </button>
                </div>
                <div class="leave-balance-stats">
                  <div class="balance-stat">
                    <div class="balance-stat__value">{{ editCurrentBalance.allocated_days }}</div>
                    <div class="balance-stat__label">Allocated</div>
                  </div>
                  <div class="balance-stat balance-stat--used">
                    <div class="balance-stat__value">{{ editCurrentBalance.used_days }}</div>
                    <div class="balance-stat__label">Used</div>
                  </div>
                  <div class="balance-stat balance-stat--pending">
                    <div class="balance-stat__value">{{ editCurrentBalance.pending_days }}</div>
                    <div class="balance-stat__label">Pending</div>
                  </div>
                  <div class="balance-stat balance-stat--available">
                    <div class="balance-stat__value">{{ editCurrentBalance.available_days }}</div>
                    <div class="balance-stat__label">Available</div>
                  </div>
                </div>
                <div class="leave-balance-bar">
                  <div 
                    class="leave-balance-bar__fill leave-balance-bar__fill--used" 
                    :style="{ width: `${(editCurrentBalance.used_days / editCurrentBalance.allocated_days * 100)}%` }"
                  ></div>
                  <div 
                    class="leave-balance-bar__fill leave-balance-bar__fill--pending" 
                    :style="{ width: `${(editCurrentBalance.pending_days / editCurrentBalance.allocated_days * 100)}%` }"
                  ></div>
                </div>
              </div>
            </transition>

            <div class="mhr-field">
              <label class="mhr-field__label">DATE FROM *</label>
              <DatePicker v-model="editDateFrom" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }" :disabled="isApproved">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
              <div v-if="editForm.errors.date_from" style="color:var(--mhr-danger);font-size:12px;margin-top:4px;">
                {{ editForm.errors.date_from }}
              </div>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">DATE TO *</label>
              <DatePicker v-model="editDateTo" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }" :disabled="isApproved">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">NUMBER OF DAYS *</label>
              <input class="mhr-input" type="number" v-model="editForm.number_of_days" min="1" :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="editForm.status_id" :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''">
                <option :value="null">Select status...</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">
                  {{ status.title }}
                </option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">REASON *</label>
              <textarea class="mhr-input" v-model="editForm.reason" rows="3" placeholder="Reason for leave request..." :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''"></textarea>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDITIONAL INFORMATION</label>
              <textarea class="mhr-input" v-model="editForm.additional_information" rows="2" placeholder="Any additional notes..." :disabled="isApproved" :style="isApproved ? 'cursor:not-allowed;opacity:0.6;' : ''"></textarea>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeEditModal">{{ isApproved ? 'Close' : 'Cancel' }}</button>
          <button 
            v-if="!isApproved"
            class="mhr-btn mhr-btn--primary" 
            @click="updateLeaveRequest"
            :disabled="editForm.processing"
            :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="editForm.processing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              Saving...
            </span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </div>
    </div>

    <!-- View Details Modal -->
    <div v-if="showViewModal" class="mhr-modal__scrim" @click.self="closeViewModal">
      <div class="mhr-modal mhr-modal--md">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Leave Request Details</h2>
            </div>
            <button class="mhr-icon-btn" @click="closeViewModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body">
          <div style="display:grid;gap:20px;">
            <div>
              <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Employee</div>
              <div style="font-weight:500;color:var(--mhr-ink);">{{ viewingRequest?.employeeName }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ viewingRequest?.employeeNumber }}</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Leave Type</div>
                <span class="mhr-badge mhr-badge--neutral">{{ viewingRequest?.leaveTypeTitle }}</span>
              </div>
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Status</div>
                <StatusPill :status="viewingRequest?.statusTitle" />
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">From</div>
                <div style="color:var(--mhr-ink-2);">{{ fmtDate(viewingRequest?.dateFrom) }}</div>
              </div>
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">To</div>
                <div style="color:var(--mhr-ink-2);">{{ fmtDate(viewingRequest?.dateTo) }}</div>
              </div>
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Days</div>
                <div style="font-weight:500;color:var(--mhr-ink);">{{ viewingRequest?.numberOfDays }}</div>
              </div>
            </div>

            <div>
              <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Reason</div>
              <div style="color:var(--mhr-ink-2);line-height:1.5;">{{ viewingRequest?.reason }}</div>
            </div>

            <div v-if="viewingRequest?.additionalInformation">
              <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Additional Information</div>
              <div style="color:var(--mhr-ink-2);line-height:1.5;">{{ viewingRequest?.additionalInformation }}</div>
            </div>

            <div style="border-top:1px solid var(--mhr-line);padding-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Submitted By</div>
                <div style="color:var(--mhr-ink-2);">{{ viewingRequest?.userName }}</div>
              </div>
              <div>
                <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Submitted On</div>
                <div style="color:var(--mhr-ink-2);">{{ fmtDate(viewingRequest?.createdAt) }}</div>
              </div>
            </div>

            <div v-if="viewingRequest?.performerName" style="border-top:1px solid var(--mhr-line);padding-top:16px;">
              <div style="font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Processed By</div>
              <div style="color:var(--mhr-ink-2);">{{ viewingRequest?.performerName }}</div>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeViewModal">Close</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal mhr-modal--sm">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Archive Leave Request</h2>
          <p class="mhr-modal__sub">This action will archive the leave request.</p>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);font-size:14px;line-height:1.5;">
            Are you sure you want to archive this leave request for <strong>{{ requestToDelete?.employeeName }}</strong>?
            This will mark it as archived.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteLeaveRequest">Archive</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.employee-name-link:hover {
  text-decoration: underline;
}

.mhr-date-wrap {
  position: relative;
}
.mhr-date-trigger {
  cursor: pointer;
  padding-right: 36px;
  user-select: none;
}
.mhr-date-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--mhr-ink-3);
  pointer-events: none;
}

/* Leave Balance Card */
.leave-balance-card {
  background: linear-gradient(135deg, var(--mhr-surface) 0%, var(--mhr-surface-2) 100%);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r);
  padding: 16px;
  animation: slideDown 0.3s ease-out;
}

.leave-balance-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--mhr-ink-2);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}

.balance-refresh-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: 1px solid var(--mhr-line);
  background: var(--mhr-bg);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--mhr-ink-2);
}

.balance-refresh-btn:hover:not(:disabled) {
  background: var(--mhr-surface);
  border-color: var(--mhr-accent);
  color: var(--mhr-accent);
  transform: scale(1.05);
}

.balance-refresh-btn:active:not(:disabled) {
  transform: scale(0.95);
}

.balance-refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.leave-balance-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 12px;
}

.balance-stat {
  text-align: center;
  padding: 12px;
  background: var(--mhr-bg);
  border-radius: 6px;
  border: 1px solid var(--mhr-line);
  transition: all 0.2s;
}

.balance-stat:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.balance-stat__value {
  font-size: 24px;
  font-weight: 700;
  color: var(--mhr-ink);
  line-height: 1;
  margin-bottom: 4px;
}

.balance-stat__label {
  font-size: 11px;
  font-weight: 500;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.balance-stat--used .balance-stat__value {
  color: var(--mhr-danger);
}

.balance-stat--pending .balance-stat__value {
  color: var(--mhr-warn);
}

.balance-stat--available .balance-stat__value {
  color: var(--green-700);
}

.leave-balance-bar {
  display: flex;
  height: 8px;
  background: var(--mhr-surface);
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}

.leave-balance-bar__fill {
  height: 100%;
  transition: width 0.5s ease-out;
}

.leave-balance-bar__fill--used {
  background: var(--mhr-danger);
}

.leave-balance-bar__fill--pending {
  background: var(--mhr-warn);
}

/* Fade slide transition */
.fade-slide-enter-active {
  animation: fadeSlideIn 0.3s ease-out;
}

.fade-slide-leave-active {
  animation: fadeSlideOut 0.2s ease-in;
}

@keyframes fadeSlideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeSlideOut {
  from {
    opacity: 1;
    transform: translateY(0);
  }
  to {
    opacity: 0;
    transform: translateY(-10px);
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    max-height: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    max-height: 500px;
    transform: translateY(0);
  }
}
</style>

<style>
/* v-calendar theme overrides — scoped doesn't reach portal content */
:root {
  --vc-font-family: inherit;
  --vc-rounded: var(--mhr-r, 8px);
  --vc-accent-600: var(--mhr-accent, #2563eb);
  --vc-accent-200: color-mix(in srgb, var(--mhr-accent, #2563eb) 20%, white);
  --vc-accent-100: color-mix(in srgb, var(--mhr-accent, #2563eb) 10%, white);
  --vc-text-gray-900: var(--mhr-ink, #111);
  --vc-text-gray-600: var(--mhr-ink-2, #444);
  --vc-text-gray-400: var(--mhr-ink-3, #888);
  --vc-border: var(--mhr-line, #e5e7eb);
  --vc-bg: var(--mhr-surface, #fff);
}
.vc-container {
  border-radius: var(--mhr-r, 8px) !important;
  box-shadow: 0 4px 16px rgba(0,0,0,.1) !important;
  border: 1px solid var(--mhr-line, #e5e7eb) !important;
  font-family: inherit !important;
}
</style>
