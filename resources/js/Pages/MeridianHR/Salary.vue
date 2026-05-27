<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import EmployeeSelector from '@/Components/MeridianHR/EmployeeSelector.vue'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: { type: String, default: 'admin' },
  salaries: { type: Array, default: () => [] },
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
const editingSalary = ref(null)
const salaryToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)

const form = useForm({
  employee_id: null,
  net_salary: '',
  payroll_cycle_id: null,
  effective_start_date: new Date().toISOString().split('T')[0],
  effective_end_date: '',
})

const editForm = useForm({
  id: null,
  net_salary: '',
  payroll_cycle_id: null,
  effective_start_date: '',
  effective_end_date: '',
})

const filtered = computed(() => {
  if (!q.value) return props.salaries
  const query = q.value.toLowerCase()
  return props.salaries.filter(salary =>
    salary.employeeName?.toLowerCase().includes(query) ||
    salary.employeeNumber?.toLowerCase().includes(query)
  )
})



const canManage = computed(() => {
  return props.hrRole === 'admin' || props.hrRole === 'manager'
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

function fmtCurrency(amount) {
  if (!amount && amount !== 0) return '—'
  return new Intl.NumberFormat('en-QA', {
    style: 'currency',
    currency: 'QAR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
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
  editingSalary.value = null
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

function addSalary() {
  form.post(route('hr.salary.store'), {
    onSuccess: () => {
      closeAddModal()
      showToast('Salary record added successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to add salary record', true)
    },
  })
}

function editSalary(salary) {
  editingSalary.value = salary
  editForm.id = salary.id
  editForm.net_salary = salary.netSalary || ''
  editForm.payroll_cycle_id = salary.payrollCycleId
  editForm.effective_start_date = salary.effectiveStartDate || ''
  editForm.effective_end_date = salary.effectiveEndDate || ''
  showEditModal.value = true
  openMenuId.value = null
}

function updateSalary() {
  editForm.put(route('hr.salary.update', editForm.id), {
    onSuccess: () => {
      closeEditModal()
      showToast('Salary record updated successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to update salary record', true)
    },
  })
}

function confirmDelete(salary) {
  salaryToDelete.value = salary
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteSalary() {
  router.delete(route('hr.salary.destroy', salaryToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      showToast('Salary record archived successfully')
    }
  })
}

function refreshSalaries() {
  isRefreshing.value = true
  router.get(route('hr.salary'), {}, {
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
        <h1 class="mhr-page-head__title">Salary Information</h1>
        <p class="mhr-page-head__sub">View and manage employee salary records</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshSalaries" />
        <button v-if="canManage" class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Salary Record
        </button>
      </div>
    </div>

    <!-- Search Filter -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search salaries…" v-model="q" />
      </div>
    </div>

    <!-- Salaries Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th v-if="hrRole !== 'employee'">EMPLOYEE</th>
              <th>NET SALARY</th>
              <th>EFFECTIVE FROM</th>
              <th>EFFECTIVE TO</th>
              <th>STATUS</th>
              <th v-if="canManage" style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td :colspan="hrRole !== 'employee' ? (canManage ? 6 : 5) : (canManage ? 5 : 4)" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No salary records found
              </td>
            </tr>
            <tr v-for="salary in filtered" :key="salary.id">
              <td v-if="hrRole !== 'employee'">
                <div style="font-weight:500;">{{ salary.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ salary.employeeNumber }}</div>
              </td>
              <td style="font-weight:600;color:var(--mhr-accent);font-size:15px;">
                {{ fmtCurrency(salary.netSalary) }}
              </td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">{{ fmtDate(salary.effectiveStartDate) }}</td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">
                <span v-if="salary.effectiveEndDate === '9999-12-31'">—</span>
                <span v-else>{{ fmtDate(salary.effectiveEndDate) }}</span>
              </td>
              <td>
                <span v-if="salary.isActive" class="mhr-badge mhr-badge--success">Active</span>
                <span v-else class="mhr-badge mhr-badge--neutral">Inactive</span>
              </td>
              <td v-if="canManage">
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(salary.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div v-if="openMenuId === salary.id" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:180px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;">
                    <button @click="editSalary(salary)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button @click="confirmDelete(salary)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
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

    <!-- Add Salary Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Salary Record</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new salary record</p>
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
                placeholder="Search employee..."
                required
              />
              <p v-if="form.errors.employee_id" class="mhr-field__error">{{ form.errors.employee_id }}</p>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">NET SALARY (QAR) *</label>
              <input class="mhr-input" type="number" step="0.01" v-model="form.net_salary" placeholder="0.00" />
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">
                Enter the net monthly salary in Qatari Riyals
              </div>
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
            @click="addSalary"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="form.processing">Creating...</span>
            <span v-else>Create Salary Record</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Salary Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Salary Record</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingSalary?.employeeName }}</p>
            </div>
            <button class="mhr-icon-btn" @click="closeEditModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">NET SALARY (QAR) *</label>
              <input class="mhr-input" type="number" step="0.01" v-model="editForm.net_salary" placeholder="0.00" />
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
            @click="updateSalary"
            :disabled="editForm.processing"
            :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="editForm.processing">Updating...</span>
            <span v-else>Update Salary Record</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Archive Salary Record</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);">
            Are you sure you want to archive this salary record? This action can be undone by an administrator.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteSalary">Archive</button>
        </div>
      </div>
    </div>
  </div>
</template>
