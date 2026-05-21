<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:         { type: String, default: 'admin' },
  leaveRequests:  { type: Array,  default: () => [] },
  employees:      { type: Array,  default: () => [] },
  leaveTypes:     { type: Array,  default: () => [] },
  statuses:       { type: Array,  default: () => [] },
})

const dateFormat = computed(() => usePage().props.dateFormat || 'DD/MM/YYYY')

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
      showAddModal.value = false
      showToast('Leave request created successfully')
      form.reset()
      form.status_id = getPendingStatusId()
    },
    onError: () => showToast('Failed to create leave request', true),
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
      showEditModal.value = false
      showToast('Leave request updated successfully')
    },
    onError: () => showToast('Failed to update leave request', true),
  })
}

function confirmDelete(request) {
  requestToDelete.value = request
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteLeaveRequest() {
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
        <button class="mhr-btn mhr-btn--outline" @click="refreshLeaveRequests" :disabled="isRefreshing">
          <AppIcon name="refresh" :size="14" :style="{ transition: 'transform 0.5s', transform: isRefreshing ? 'rotate(360deg)' : 'rotate(0deg)' }" />
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Leave Request
        </button>
      </div>
    </div>

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
                <span class="mhr-badge" :style="{ background: request.statusColor || 'var(--mhr-line)', color: 'white' }">
                  {{ request.statusTitle }}
                </span>
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
                    <button @click="editRequest(request)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button @click="confirmDelete(request)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
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
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="showAddModal = false">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Leave Request</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new leave request</p>
            </div>
            <button class="mhr-icon-btn" @click="showAddModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">EMPLOYEE *</label>
              <select class="mhr-select" v-model="form.employee_id">
                <option :value="null">Select employee...</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.full_name }} ({{ emp.employee_number }})
                </option>
              </select>
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
          <button class="mhr-btn mhr-btn--ghost" @click="showAddModal = false">Cancel</button>
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
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="showEditModal = false">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Leave Request</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingRequest?.employeeName }}</p>
            </div>
            <button class="mhr-icon-btn" @click="showEditModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">EMPLOYEE *</label>
              <select class="mhr-select" v-model="editForm.employee_id">
                <option :value="null">Select employee...</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.full_name }} ({{ emp.employee_number }})
                </option>
              </select>
            </div>
            
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">LEAVE TYPE *</label>
              <select class="mhr-select" v-model="editForm.leave_type_id">
                <option :value="null">Select leave type...</option>
                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                  {{ type.title }}
                </option>
              </select>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">DATE FROM *</label>
              <DatePicker v-model="editDateFrom" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">DATE TO *</label>
              <DatePicker v-model="editDateTo" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
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
              <input class="mhr-input" type="number" v-model="editForm.number_of_days" min="1" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="editForm.status_id">
                <option :value="null">Select status...</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">
                  {{ status.title }}
                </option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">REASON *</label>
              <textarea class="mhr-input" v-model="editForm.reason" rows="3" placeholder="Reason for leave request..."></textarea>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDITIONAL INFORMATION</label>
              <textarea class="mhr-input" v-model="editForm.additional_information" rows="2" placeholder="Any additional notes..."></textarea>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showEditModal = false">Cancel</button>
          <button 
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
    <div v-if="showViewModal" class="mhr-modal__scrim" @click.self="showViewModal = false">
      <div class="mhr-modal mhr-modal--md">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Leave Request Details</h2>
            </div>
            <button class="mhr-icon-btn" @click="showViewModal = false" style="margin-top:-4px;">
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
                <span class="mhr-badge" :style="{ background: viewingRequest?.statusColor || 'var(--mhr-line)', color: 'white' }">
                  {{ viewingRequest?.statusTitle }}
                </span>
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
          <button class="mhr-btn mhr-btn--ghost" @click="showViewModal = false">Close</button>
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
