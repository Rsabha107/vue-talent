<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: { type: String, default: 'admin' },
  addresses: { type: Array, default: () => [] },
  countries: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  addressTypes: { type: Array, default: () => [] },
})

const page = usePage()
const currentEmployeeId = computed(() => page.props.me?.id)

const q = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingAddress = ref(null)
const addressToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)
const employeeSearch = ref('')
const showEmployeeDropdown = ref(false)

const form = useForm({
  employee_id: props.hrRole === 'employee' ? currentEmployeeId.value : null,
  address_type: null,
  primary_address: 'N',
  address1: '',
  address2: '',
  city: '',
  state: '',
  zipcode: '',
  country_id: null,
})

const editForm = useForm({
  id: null,
  address_type: null,
  primary_address: 'N',
  address1: '',
  address2: '',
  city: '',
  state: '',
  zipcode: '',
  country_id: null,
})

const filtered = computed(() => {
  if (!q.value) return props.addresses
  const query = q.value.toLowerCase()
  return props.addresses.filter(addr =>
    addr.employeeName?.toLowerCase().includes(query) ||
    addr.employeeNumber?.toLowerCase().includes(query) ||
    addr.address1?.toLowerCase().includes(query) ||
    addr.city?.toLowerCase().includes(query) ||
    addr.countryName?.toLowerCase().includes(query)
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

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function getAddressTypeName(typeId) {
  const type = props.addressTypes.find(t => t.id === typeId)
  return type ? type.name : 'Unknown'
}

function resetAddForm() {
  form.reset()
  form.employee_id = props.hrRole === 'employee' ? currentEmployeeId.value : null
  employeeSearch.value = ''
  showEmployeeDropdown.value = false
}

function clearEmployee() {
  form.employee_id = null
  employeeSearch.value = ''
}

function resetEditForm() {
  editForm.reset()
  editingAddress.value = null
}

function closeAddModal() {
  showAddModal.value = false
  resetAddForm()
}

function closeEditModal() {
  showEditModal.value = false
  resetEditForm()
}

function addAddress() {
  form.post(route('hr.addresses.store'), {
    onSuccess: () => {
      closeAddModal()
      showToast('Address added successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to add address', true)
    },
  })
}

function editAddress(address) {
  editingAddress.value = address
  editForm.id = address.id
  editForm.address_type = address.addressType
  editForm.primary_address = address.isPrimary ? 'Y' : 'N'
  editForm.address1 = address.address1 || ''
  editForm.address2 = address.address2 || ''
  editForm.city = address.city || ''
  editForm.state = address.state || ''
  editForm.zipcode = address.zipcode || ''
  editForm.country_id = address.countryId
  showEditModal.value = true
  openMenuId.value = null
}

function updateAddress() {
  editForm.put(route('hr.addresses.update', editForm.id), {
    onSuccess: () => {
      closeEditModal()
      showToast('Address updated successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to update address', true)
    },
  })
}

function confirmDelete(address) {
  addressToDelete.value = address
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteAddress() {
  router.delete(route('hr.addresses.destroy', addressToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      showToast('Address archived successfully')
    }
  })
}

function refreshAddresses() {
  isRefreshing.value = true
  router.get(route('hr.addresses'), {}, {
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
        <h1 class="mhr-page-head__title">Addresses</h1>
        <p class="mhr-page-head__sub">Manage employee addresses</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <button class="mhr-btn mhr-btn--outline" @click="refreshAddresses" :disabled="isRefreshing">
          <AppIcon name="refresh" :size="14" :style="{ transition: 'transform 0.5s', transform: isRefreshing ? 'rotate(360deg)' : 'rotate(0deg)' }" />
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Address
        </button>
      </div>
    </div>

    <!-- Search Filter -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search addresses…" v-model="q" />
      </div>
    </div>

    <!-- Addresses Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th v-if="hrRole !== 'employee'">EMPLOYEE</th>
              <th>TYPE</th>
              <th>ADDRESS</th>
              <th>CITY</th>
              <th>COUNTRY</th>
              <th>PRIMARY</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td :colspan="hrRole !== 'employee' ? 7 : 6" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No addresses found
              </td>
            </tr>
            <tr v-for="address in filtered" :key="address.id">
              <td v-if="hrRole !== 'employee'">
                <div style="font-weight:500;">{{ address.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ address.employeeNumber }}</div>
              </td>
              <td>
                <span class="mhr-badge mhr-badge--neutral">{{ getAddressTypeName(address.addressType) }}</span>
              </td>
              <td style="color:var(--mhr-ink-2);max-width:300px;">
                <div>{{ address.address1 }}</div>
                <div v-if="address.address2" style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ address.address2 }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);">{{ address.city }}</td>
              <td style="color:var(--mhr-ink-2);">{{ address.countryName }}</td>
              <td>
                <span v-if="address.isPrimary" class="mhr-badge mhr-badge--success">Primary</span>
                <span v-else style="color:var(--mhr-ink-3);font-size:13px;">—</span>
              </td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(address.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div v-if="openMenuId === address.id" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:180px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;">
                    <button @click="editAddress(address)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button @click="confirmDelete(address)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
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

    <!-- Add Address Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Address</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new address</p>
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

            <div class="mhr-field">
              <label class="mhr-field__label">ADDRESS TYPE *</label>
              <select class="mhr-select" v-model="form.address_type">
                <option :value="null">Select type...</option>
                <option v-for="type in addressTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">PRIMARY ADDRESS</label>
              <select class="mhr-select" v-model="form.primary_address">
                <option value="N">No</option>
                <option value="Y">Yes</option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDRESS LINE 1 *</label>
              <input class="mhr-input" v-model="form.address1" placeholder="Street address, P.O. box, company name..." />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDRESS LINE 2</label>
              <input class="mhr-input" v-model="form.address2" placeholder="Apartment, suite, unit, building, floor..." />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">CITY *</label>
              <input class="mhr-input" v-model="form.city" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">STATE / PROVINCE</label>
              <input class="mhr-input" v-model="form.state" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">ZIP / POSTAL CODE</label>
              <input class="mhr-input" v-model="form.zipcode" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">COUNTRY *</label>
              <select class="mhr-select" v-model="form.country_id">
                <option :value="null">Select country...</option>
                <option v-for="country in countries" :key="country.id" :value="country.id">
                  {{ country.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeAddModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="addAddress"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="form.processing">Creating...</span>
            <span v-else>Create Address</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Address Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Address</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingAddress?.employeeName }}</p>
            </div>
            <button class="mhr-icon-btn" @click="closeEditModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field">
              <label class="mhr-field__label">ADDRESS TYPE *</label>
              <select class="mhr-select" v-model="editForm.address_type">
                <option :value="null">Select type...</option>
                <option v-for="type in addressTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">PRIMARY ADDRESS</label>
              <select class="mhr-select" v-model="editForm.primary_address">
                <option value="N">No</option>
                <option value="Y">Yes</option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDRESS LINE 1 *</label>
              <input class="mhr-input" v-model="editForm.address1" />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">ADDRESS LINE 2</label>
              <input class="mhr-input" v-model="editForm.address2" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">CITY *</label>
              <input class="mhr-input" v-model="editForm.city" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">STATE / PROVINCE</label>
              <input class="mhr-input" v-model="editForm.state" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">ZIP / POSTAL CODE</label>
              <input class="mhr-input" v-model="editForm.zipcode" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">COUNTRY *</label>
              <select class="mhr-select" v-model="editForm.country_id">
                <option :value="null">Select country...</option>
                <option v-for="country in countries" :key="country.id" :value="country.id">
                  {{ country.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeEditModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="updateAddress"
            :disabled="editForm.processing"
            :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="editForm.processing">Updating...</span>
            <span v-else>Update Address</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Archive Address</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);">
            Are you sure you want to archive this address? This action can be undone by an administrator.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteAddress">Archive</button>
        </div>
      </div>
    </div>
  </div>
</template>
