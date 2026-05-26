<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
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
const editingBank = ref(null)
const bankToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)
const employeeSearch = ref('')
const showEmployeeDropdown = ref(false)

const form = useForm({
  employee_id: props.hrRole === 'employee' ? currentEmployeeId.value : null,
  bank_branch_name: '',
  bank_account_name: '',
  iban: '',
  swift_code: '',
  effective_start_date: new Date().toISOString().split('T')[0],
  effective_end_date: '',
})

const editForm = useForm({
  id: null,
  bank_branch_name: '',
  bank_account_name: '',
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
    bank.bankAccountName?.toLowerCase().includes(query) ||
    bank.iban?.toLowerCase().includes(query)
  )
})

const selectedEmployee = computed(() => {
  return props.employees.find(emp => emp.id === form.employee_id)
})

const filteredEmployees = computed(() => {
  if (!employeeSearch.value) return props.employees
  const query = employeeSearch.value.toLowerCase()
  return props.employees.filter(emp =>
    emp.full_name?.toLowerCase().includes(query) ||
    emp.employee_number?.toLowerCase().includes(query)
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
  employeeSearch.value = ''
  showEmployeeDropdown.value = false
}

function clearEmployee() {
  form.employee_id = null
  employeeSearch.value = ''
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
  editForm.bank_account_name = bank.bankAccountName || ''
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

// Close dropdowns when clicking outside
function handleClickOutside(event) {
  const employeeDropdown = event.target.closest('[data-employee-dropdown]')
  if (!employeeDropdown) {
    showEmployeeDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
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

    <!-- Banks Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th v-if="hrRole !== 'employee'">EMPLOYEE</th>
              <th>BANK BRANCH</th>
              <th>ACCOUNT NAME</th>
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
            <tr v-for="bank in filtered" :key="bank.id">
              <td v-if="hrRole !== 'employee'">
                <div style="font-weight:500;">{{ bank.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ bank.employeeNumber }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);">{{ bank.bankBranchName }}</td>
              <td style="color:var(--mhr-ink-2);font-weight:500;">{{ bank.bankAccountName }}</td>
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
            <div v-if="hrRole !== 'employee'" class="mhr-field" style="grid-column:1/-1;position:relative;" data-employee-dropdown>
              <label class="mhr-field__label">EMPLOYEE *</label>
              <div style="position:relative;">
                <input
                  type="text"
                  class="mhr-input"
                  :value="showEmployeeDropdown ? employeeSearch : (selectedEmployee ? `${selectedEmployee.full_name} (${selectedEmployee.employee_number})` : '')"
                  @focus="showEmployeeDropdown = true; employeeSearch = ''"
                  @input="employeeSearch = $event.target.value; showEmployeeDropdown = true"
                  placeholder="Search employee..."
                  :style="selectedEmployee && !showEmployeeDropdown ? 'cursor:pointer;padding-right:36px;' : 'cursor:pointer;'"
                />
                <button
                  v-if="selectedEmployee && !showEmployeeDropdown"
                  type="button"
                  @click.stop="clearEmployee"
                  class="mhr-icon-btn"
                  style="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:24px;height:24px;padding:0;"
                  title="Clear selection"
                >
                  <AppIcon name="x" :size="14" />
                </button>
                <div v-if="showEmployeeDropdown" style="position:absolute;top:100%;left:0;right:0;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:var(--mhr-r);margin-top:4px;max-height:250px;overflow-y:auto;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                  <div
                    v-if="filteredEmployees.length === 0"
                    style="padding:12px;color:var(--mhr-ink-3);font-size:13px;text-align:center;"
                  >
                    No employees found
                  </div>
                  <button
                    v-for="emp in filteredEmployees"
                    :key="emp.id"
                    type="button"
                    @click="form.employee_id = emp.id; showEmployeeDropdown = false; employeeSearch = ''"
                    style="width:100%;padding:10px 12px;border:none;background:transparent;text-align:left;cursor:pointer;font-size:13px;color:var(--mhr-ink);display:flex;flex-direction:column;gap:2px;"
                    :style="form.employee_id === emp.id ? 'background:var(--mhr-accent);color:white;' : ''"
                    @mouseenter="$event.currentTarget.style.background = form.employee_id === emp.id ? 'var(--mhr-accent)' : 'var(--mhr-surface-2)'"
                    @mouseleave="$event.currentTarget.style.background = form.employee_id === emp.id ? 'var(--mhr-accent)' : 'transparent'"
                  >
                    <span style="font-weight:500;">{{ emp.full_name }}</span>
                    <span style="font-size:12px;opacity:0.8;">{{ emp.employee_number }}</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">BANK BRANCH NAME *</label>
              <input class="mhr-input" v-model="form.bank_branch_name" placeholder="e.g., Kuwait National Bank - Salmiya Branch" />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">BANK ACCOUNT NAME *</label>
              <input class="mhr-input" v-model="form.bank_account_name" placeholder="Account holder name" />
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

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">BANK ACCOUNT NAME *</label>
              <input class="mhr-input" v-model="editForm.bank_account_name" />
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
  </div>
</template>
