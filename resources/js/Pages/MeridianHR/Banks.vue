<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import EmployeeSelector from '@/Components/MeridianHR/EmployeeSelector.vue'
import ImportStatsModal from '@/Components/MeridianHR/ImportStatsModal.vue'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: { type: String, default: 'admin' },
  banks: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
})

const page = usePage()
const currentEmployeeId = computed(() => page.props.me?.id)

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
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showImportModal = ref(false)
const showStatsModal = ref(false)
const showDuplicateConfirmModal = ref(false)
const editingBank = ref(null)
const bankToDelete = ref(null)
const importFile = ref(null)
const fileInput = ref(null)
const treatDuplicatesAsError = ref(true)
const importStats = ref(null)
const importErrors = ref([])
const hasFailures = ref(false)
const hasExportableFailures = ref(false)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)
const isImporting = ref(false)

const canManage = computed(() => props.hrRole === 'admin' || props.hrRole === 'manager')

// ── Row selection ────────────────────────────────────────────────────
const selectedBanks = ref(new Set())
const allSelected = computed(() =>
  filtered.value.length > 0 && filtered.value.every(b => selectedBanks.value.has(b.id))
)
const someSelected = computed(() =>
  filtered.value.some(b => selectedBanks.value.has(b.id)) && !allSelected.value
)
function toggleSelectAll() {
  if (allSelected.value) {
    filtered.value.forEach(b => selectedBanks.value.delete(b.id))
  } else {
    filtered.value.forEach(b => selectedBanks.value.add(b.id))
  }
}
function toggleSelect(id) {
  selectedBanks.value.has(id) ? selectedBanks.value.delete(id) : selectedBanks.value.add(id)
}
function exportSelected() {
  const ids = Array.from(selectedBanks.value)
  if (!ids.length) return
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('hr.banks.export.selected')
  form.style.display = 'none'
  const csrf = document.createElement('input')
  csrf.type = 'hidden'; csrf.name = '_token'
  csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  form.appendChild(csrf)
  ids.forEach(id => {
    const input = document.createElement('input')
    input.type = 'hidden'; input.name = 'bank_ids[]'; input.value = id
    form.appendChild(input)
  })
  document.body.appendChild(form)
  form.submit()
  setTimeout(() => document.body.contains(form) && document.body.removeChild(form), 2000)
}

const form = useForm({
  employee_id: props.hrRole === 'employee' ? currentEmployeeId.value : null,
  bank_branch_name: '',
  iban: '',
  swift_code: '',
  effective_start_date: new Date().toISOString().split('T')[0],
  effective_end_date: '',
})

const editForm = useForm({
  id: null,
  bank_branch_name: '',
  iban: '',
  swift_code: '',
  effective_start_date: '',
  effective_end_date: '',
})

const filtered = computed(() => {
  if (!q.value) return props.banks
  const query = q.value.toLowerCase()
  return props.banks.filter(bank =>
    bank.employeeName?.toLowerCase().includes(query) ||
    bank.employeeNumber?.toLowerCase().includes(query) ||
    bank.bankBranchName?.toLowerCase().includes(query) ||
    bank.iban?.toLowerCase().includes(query)
  )
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

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function resetAddForm() {
  form.reset()
  form.employee_id = props.hrRole === 'employee' ? currentEmployeeId.value : null
  form.effective_start_date = new Date().toISOString().split('T')[0]
}

function resetEditForm() {
  editForm.reset()
  editingBank.value = null
}

function closeAddModal() {
  showAddModal.value = false
  resetAddForm()
}

function closeEditModal() {
  showEditModal.value = false
  resetEditForm()
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

const addStartDate = computed({
  get: () => toDateObj(form.effective_start_date),
  set: (v) => { form.effective_start_date = toDateStr(v) },
})

const addEndDate = computed({
  get: () => toDateObj(form.effective_end_date),
  set: (v) => { form.effective_end_date = toDateStr(v) },
})

const editStartDate = computed({
  get: () => toDateObj(editForm.effective_start_date),
  set: (v) => { editForm.effective_start_date = toDateStr(v) },
})

const editEndDate = computed({
  get: () => toDateObj(editForm.effective_end_date),
  set: (v) => { editForm.effective_end_date = toDateStr(v) },
})

function addBank() {
  form.post(route('hr.banks.store'), {
    onSuccess: () => {
      closeAddModal()
      showToast('Bank account added successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to add bank account', true)
    },
  })
}

function editBank(bank) {
  editingBank.value = bank
  editForm.id = bank.id
  editForm.bank_branch_name = bank.bankBranchName || ''
  editForm.iban = bank.iban || ''
  editForm.swift_code = bank.swiftCode || ''
  editForm.effective_start_date = bank.effectiveStartDate || ''
  editForm.effective_end_date = bank.effectiveEndDate || ''
  showEditModal.value = true
  openMenuId.value = null
}

function updateBank() {
  editForm.put(route('hr.banks.update', editForm.id), {
    onSuccess: () => {
      closeEditModal()
      showToast('Bank account updated successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to update bank account', true)
    },
  })
}

function confirmDelete(bank) {
  bankToDelete.value = bank
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteBank() {
  router.delete(route('hr.banks.destroy', bankToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      showToast('Bank account archived successfully')
    }
  })
}

function refreshBanks() {
  isRefreshing.value = true
  router.get(route('hr.banks'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}

function downloadTemplate() {
  window.location.href = route('hr.banks.template')
}

function openImportModal() {
  showImportModal.value = true
  importFile.value = null
  treatDuplicatesAsError.value = true
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    importFile.value = file
  }
}

function triggerFileInput() {
  fileInput.value?.click()
}

async function importBanks() {
  if (!importFile.value) {
    alert('Please select a file to import')
    return
  }

  isImporting.value = true
  const formData = new FormData()
  formData.append('file', importFile.value)
  formData.append('treat_duplicates_as_error', treatDuplicatesAsError.value ? '1' : '0')

  try {
    const response = await fetch(route('hr.banks.import'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: formData,
    })

    const data = await response.json()

    if (data.success || data.hasFailures || data.errors) {
      importStats.value = data.stats || {
        total: 0,
        success: 0,
        updated: 0,
        skipped: 0,
        failed: data.errors ? data.errors.length : 0
      }
      importErrors.value = data.errors || []
      hasFailures.value = data.hasFailures || (data.errors && data.errors.length > 0) || false
      hasExportableFailures.value = data.hasExportableFailures || false
      showImportModal.value = false
      showStatsModal.value = true
      importFile.value = null

      if (data.success && importStats.value.success > 0) {
        router.reload({ only: ['banks'] })
      }
    } else {
      importStats.value = {
        total: 0,
        success: 0,
        updated: 0,
        skipped: 0,
        failed: 1
      }
      importErrors.value = [data.message || 'Import failed']
      hasFailures.value = true
      hasExportableFailures.value = false
      showImportModal.value = false
      showStatsModal.value = true
      importFile.value = null
    }
  } catch (error) {
    showToast('Import failed: ' + error.message, true)
  } finally {
    isImporting.value = false
  }
}

function exportFailedRows() {
  window.location.href = route('hr.banks.export.failed')
}

function handleDuplicateOptionChange(event) {
  const isChecked = event.target.checked

  if (!isChecked) {
    showDuplicateConfirmModal.value = true
    event.target.checked = true
  } else {
    treatDuplicatesAsError.value = true
  }
}

function confirmEnableDateTracking() {
  treatDuplicatesAsError.value = false
  showDuplicateConfirmModal.value = false
}

function cancelEnableDateTracking() {
  showDuplicateConfirmModal.value = false
}
</script>

<template>
  <div @click="openMenuId = null">
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Bank Details</h1>
        <p class="mhr-page-head__sub">Manage employee bank accounts</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshBanks" />
        <button v-if="canManage" class="mhr-btn mhr-btn--outline" @click="openImportModal">
          <AppIcon name="upload" :size="14" /> Import
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Bank Account
        </button>
      </div>
    </div>

    <!-- Search Filter -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search bank accounts…" v-model="q" />
      </div>
    </div>

    <!-- Selection banner -->
    <div v-if="selectedBanks.size > 0" class="mhr-card" style="margin-bottom:14px;padding:12px 16px;display:flex;align-items:center;gap:12px;background:var(--mhr-accent-soft);border:1px solid var(--mhr-accent);">
      <AppIcon name="check" :size="16" style="color:var(--mhr-accent);" />
      <span style="font-size:13px;font-weight:500;color:var(--mhr-accent);">{{ selectedBanks.size }} bank account{{ selectedBanks.size > 1 ? 's' : '' }} selected</span>
      <button class="mhr-btn mhr-btn--sm mhr-btn--outline" @click="selectedBanks.clear()">Clear</button>
      <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="exportSelected">
        <AppIcon name="download" :size="13" /> Export
      </button>
    </div>

    <!-- Banks Table -->
    <div class="mhr-card">
      <div class="mhr-table-container">
        <table class="mhr-table">
          <thead>
            <tr>
              <th style="width:40px;">
                <input type="checkbox" :checked="allSelected" :indeterminate="someSelected" @change="toggleSelectAll" class="mhr-checkbox" style="cursor:pointer;" />
              </th>
              <th v-if="hrRole !== 'employee'">STAFF</th>
              <th>BANK BRANCH</th>
              <th>IBAN</th>
              <th>SWIFT CODE</th>
              <th>EFFECTIVE FROM</th>
              <th>EFFECTIVE TO</th>
              <th>STATUS</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td :colspan="hrRole !== 'employee' ? 9 : 8" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No bank accounts found
              </td>
            </tr>
            <tr v-for="bank in filtered" :key="bank.id" :style="selectedBanks.has(bank.id) ? 'background:var(--mhr-accent-soft);' : ''">
              <td>
                <input type="checkbox" :checked="selectedBanks.has(bank.id)" @change="toggleSelect(bank.id)" class="mhr-checkbox" style="cursor:pointer;" />
              </td>
              <td v-if="hrRole !== 'employee'">
                <div style="font-weight:500;">{{ bank.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ bank.employeeNumber }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);">{{ bank.bankBranchName }}</td>
              <td style="font-family:monospace;font-size:13px;color:var(--mhr-ink-2);">{{ bank.iban }}</td>
              <td style="font-family:monospace;font-size:13px;color:var(--mhr-ink-3);">{{ bank.swiftCode }}</td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">{{ fmtDate(bank.effectiveStartDate) }}</td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">
                <span v-if="bank.effectiveEndDate === '9999-12-31'">—</span>
                <span v-else>{{ fmtDate(bank.effectiveEndDate) }}</span>
              </td>
              <td>
                <span v-if="bank.isActive" class="mhr-badge mhr-badge--success">Active</span>
                <span v-else class="mhr-badge mhr-badge--neutral">Inactive</span>
              </td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(bank.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div v-if="openMenuId === bank.id" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:180px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;">
                    <button @click="editBank(bank)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button @click="confirmDelete(bank)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
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

    <!-- Add Bank Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Bank Account</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new bank account</p>
            </div>
            <button class="mhr-icon-btn" @click="closeAddModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div v-if="hrRole !== 'employee'" class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">STAFF *</label>
              <EmployeeSelector
                v-model="form.employee_id"
                :employees="employees"
                placeholder="Select staff…"
                :required="true"
              />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">BANK BRANCH NAME *</label>
              <input class="mhr-input" v-model="form.bank_branch_name" placeholder="e.g., Kuwait National Bank - Salmiya Branch" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">IBAN *</label>
              <input class="mhr-input" v-model="form.iban" placeholder="KW74NBOK0000000000001000372151" style="font-family:monospace;" maxlength="29" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">SWIFT CODE *</label>
              <input class="mhr-input" v-model="form.swift_code" placeholder="NBOKKWKW" style="font-family:monospace;" maxlength="8" />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">EFFECTIVE START DATE *</label>
              <DatePicker v-model="addStartDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeAddModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="addBank"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="form.processing">Creating...</span>
            <span v-else>Create Bank Account</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Bank Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Bank Account</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingBank?.employeeName }}</p>
            </div>
            <button class="mhr-icon-btn" @click="closeEditModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">BANK BRANCH NAME *</label>
              <input class="mhr-input" v-model="editForm.bank_branch_name" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">IBAN *</label>
              <input class="mhr-input" v-model="editForm.iban" style="font-family:monospace;" maxlength="29" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">SWIFT CODE *</label>
              <input class="mhr-input" v-model="editForm.swift_code" style="font-family:monospace;" maxlength="8" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">EFFECTIVE START DATE</label>
              <DatePicker v-model="editStartDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">EFFECTIVE END DATE</label>
              <DatePicker v-model="editEndDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div class="mhr-date-wrap">
                    <input class="mhr-input mhr-date-trigger" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" />
                    <AppIcon name="calendar" :size="14" class="mhr-date-icon" />
                  </div>
                </template>
              </DatePicker>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeEditModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="updateBank"
            :disabled="editForm.processing"
            :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="editForm.processing">Updating...</span>
            <span v-else>Update Bank Account</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Archive Bank Account</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);">
            Are you sure you want to archive this bank account? This action can be undone by an administrator.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteBank">Archive</button>
        </div>
      </div>
    </div>

    <!-- Duplicate Handling Confirmation Modal -->
    <div v-if="showDuplicateConfirmModal" class="mhr-modal__scrim" @click.self="cancelEnableDateTracking" style="z-index:10000;">
      <div class="mhr-modal mhr-modal--md">
        <div class="mhr-modal__hd">
          <div style="display:flex;align-items:start;gap:12px;">
            <div style="width:40px;height:40px;background:var(--mhr-warn-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <AppIcon name="alert" :size="20" style="color:var(--mhr-warn);" />
            </div>
            <div>
              <h2 class="mhr-modal__title" style="color:var(--mhr-warn);">Enable Automatic Date-Tracking?</h2>
              <p class="mhr-modal__sub" style="margin-top:4px;">This will modify existing bank records</p>
            </div>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-surface-2);border-left:3px solid var(--mhr-warn);padding:14px 16px;border-radius:6px;margin-bottom:16px;">
            <p style="color:var(--mhr-ink);font-size:14px;line-height:1.6;margin-bottom:12px;">
              By unchecking this option, the system will <strong style="color:var(--mhr-warn);">automatically close (date-track)</strong> any existing active bank records when importing new ones.
            </p>
            <div style="font-size:13px;color:var(--mhr-ink-2);line-height:1.6;">
              <strong style="color:var(--mhr-ink);">This means:</strong>
              <ul style="margin:8px 0 0 20px;padding:0;">
                <li style="margin-bottom:6px;">• Existing open-ended bank records will be closed</li>
                <li style="margin-bottom:6px;">• New bank records will be created</li>
                <li style="margin-bottom:6px;">• Bank account history will be maintained automatically</li>
              </ul>
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:13px;">
            If you want to prevent automatic modifications and treat duplicates as validation errors instead, click <strong>Cancel</strong>.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="cancelEnableDateTracking">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="confirmEnableDateTracking" style="background:var(--mhr-warn);border-color:var(--mhr-warn);">
            <AppIcon name="check" :size="14" /> Enable Date-Tracking
          </button>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="mhr-modal__scrim" @click.self="!isImporting && (showImportModal = false)">
      <div class="mhr-modal mhr-modal--md" style="position:relative;">
        <!-- Processing Overlay -->
        <div v-if="isImporting" style="position:absolute;inset:0;background:rgba(255,255,255,0.95);border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:10;backdrop-filter:blur(2px);">
          <div style="width:48px;height:48px;border:4px solid var(--mhr-line);border-top-color:var(--blue-600);border-radius:50%;animation:spin 0.8s linear infinite;margin-bottom:16px;"></div>
          <div style="font-size:16px;font-weight:600;color:var(--mhr-ink);margin-bottom:4px;">Processing Import</div>
          <div style="font-size:13px;color:var(--mhr-ink-2);text-align:center;max-width:300px;">
            <div>Validating and importing bank records...</div>
            <div style="margin-top:4px;font-size:12px;color:var(--mhr-ink-3);">This may take a few moments</div>
          </div>
        </div>

        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Import Bank Accounts</h2>
          <p class="mhr-modal__sub">Upload an Excel file to import multiple bank accounts at once</p>
        </div>
        <div class="mhr-modal__body">
          <div style="margin-bottom:20px;padding:14px;background:var(--mhr-surface);border-radius:8px;border:1px solid var(--mhr-line);">
            <div style="display:flex;align-items:start;gap:10px;margin-bottom:8px;">
              <AppIcon name="info" :size="16" style="color:var(--blue-600);margin-top:2px;" />
              <div style="flex:1;">
                <p style="font-weight:500;font-size:13px;color:var(--mhr-ink);margin-bottom:4px;">Before importing:</p>
                <ol style="font-size:13px;color:var(--mhr-ink-2);line-height:1.6;margin:0;padding-left:20px;">
                  <li>Download the template file using the button below</li>
                  <li>Fill in bank data (Employee Number, IBAN, and Bank Branch Name are required)</li>
                  <li>Effective Start Date defaults to 2026-01-01 if left blank</li>
                  <li>Save and upload the completed file</li>
                </ol>
              </div>
            </div>
          </div>

          <div style="margin-bottom:20px;">
            <button class="mhr-btn mhr-btn--outline" @click="downloadTemplate" style="width:100%;">
              <AppIcon name="download" :size="14" />
              Download Bank Template
            </button>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Upload Excel File</label>
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileSelect"
              style="display:none;"
            />
            <div
              @click="triggerFileInput"
              style="border:2px dashed var(--mhr-line);border-radius:8px;padding:24px;text-align:center;cursor:pointer;transition:all 0.2s;"
              @mouseenter="$event.target.style.borderColor='var(--blue-500)'"
              @mouseleave="$event.target.style.borderColor='var(--mhr-line)'"
            >
              <AppIcon name="upload" :size="24" style="color:var(--mhr-ink-3);margin-bottom:8px;" />
              <p style="font-size:14px;color:var(--mhr-ink-2);margin:0;">
                <span v-if="!importFile">Click to select file or drag and drop</span>
                <span v-else style="color:var(--green-600);font-weight:500;">✓ {{ importFile.name }}</span>
              </p>
              <p style="font-size:12px;color:var(--mhr-ink-3);margin:4px 0 0 0;">
                Supports: .xlsx, .xls, .csv (Max 10MB)
              </p>
            </div>

            <!-- Warning and Options -->
            <div style="margin-top:20px;background:var(--mhr-surface-2);border-left:3px solid var(--blue-500);padding:14px 16px;border-radius:6px;">
              <div style="display:flex;align-items:start;gap:10px;margin-bottom:12px;">
                <AppIcon name="info" :size="16" style="color:var(--blue-500);flex-shrink:0;margin-top:2px;" />
                <div style="flex:1;">
                  <div style="font-size:13px;font-weight:700;color:var(--blue-600);margin-bottom:4px;">Date Tracking Behavior</div>
                  <div style="font-size:12px;color:var(--mhr-ink);line-height:1.5;font-weight:500;">
                    By default, if an employee already has an active bank record, the system will <strong style="color:var(--blue-700);">automatically close it
                    (date-track)</strong> and create the new record. This maintains a complete bank account history.
                  </div>
                </div>
              </div>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 0;">
                <input
                  type="checkbox"
                  :checked="treatDuplicatesAsError"
                  @change="handleDuplicateOptionChange"
                  style="width:16px;height:16px;cursor:pointer;"
                />
                <span style="font-size:13px;color:var(--mhr-ink);">
                  Treat duplicates as errors instead of date-tracking
                </span>
              </label>
              <div v-if="treatDuplicatesAsError" style="margin-left:24px;font-size:12px;color:var(--red-600);margin-top:4px;">
                ⚠ Rows with existing active bank records will fail validation
              </div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showImportModal = false" :disabled="isImporting">Cancel</button>
          <button
            class="mhr-btn mhr-btn--primary"
            @click="importBanks"
            :disabled="!importFile || isImporting"
            :style="(!importFile || isImporting) ? 'opacity: 0.5; cursor: not-allowed;' : ''"
          >
            <AppIcon v-if="!isImporting" name="upload" :size="14" />
            <span v-if="isImporting">Importing...</span>
            <span v-else>Import Bank Accounts</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Import Stats Modal -->
    <ImportStatsModal
      :show="showStatsModal"
      :stats="importStats"
      :errors="importErrors"
      :has-failures="hasFailures"
      :has-exportable-failures="hasExportableFailures"
      entity-name="bank record(s)"
      @close="showStatsModal = false"
      @export-failed="exportFailedRows"
    />
  </div>
</template>
