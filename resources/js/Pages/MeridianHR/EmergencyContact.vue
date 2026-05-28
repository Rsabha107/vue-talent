<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import EventBanner from '@/Components/MeridianHR/EventBanner.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: { type: String, default: 'admin' },
  contacts: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  relationships: { type: Array, default: () => [] },
  currentEmployee: { type: Object, default: null },
})

const page = usePage()
const isEmployee = computed(() => !['admin', 'manager'].includes(props.hrRole))

const selectedEventId = computed(() => page.props.selectedEvent)
const availableEvents = computed(() => page.props.availableEvents || [])
const selectedEventData = computed(() => {
  if (!selectedEventId.value) return null
  return availableEvents.value.find(e => e.id === selectedEventId.value)
})

const q = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingContact = ref(null)
const contactToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)
const employeeSearch = ref('')
const showEmployeeDropdown = ref(false)

const form = useForm({
  employee_id: props.currentEmployee?.id || null,
  first_name: '',
  last_name: '',
  relationship_id: null,
  contact_number: '',
})

const editForm = useForm({
  id: null,
  first_name: '',
  last_name: '',
  relationship_id: null,
  contact_number: '',
})

const filtered = computed(() => {
  if (!q.value) return props.contacts
  const query = q.value.toLowerCase()
  return props.contacts.filter(contact =>
    contact.employeeName?.toLowerCase().includes(query) ||
    contact.employeeNumber?.toLowerCase().includes(query) ||
    contact.fullName?.toLowerCase().includes(query) ||
    contact.contactNumber?.toLowerCase().includes(query) ||
    contact.relationshipName?.toLowerCase().includes(query)
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

function resetAddForm() {
  form.reset()
  form.employee_id = props.currentEmployee?.id || null
  employeeSearch.value = ''
  showEmployeeDropdown.value = false
}

function clearEmployee() {
  form.employee_id = null
  employeeSearch.value = ''
}

function resetEditForm() {
  editForm.reset()
  editingContact.value = null
}

function closeAddModal() {
  showAddModal.value = false
  resetAddForm()
}

function closeEditModal() {
  showEditModal.value = false
  resetEditForm()
}

function addContact() {
  form.post(route('hr.emergency.store'), {
    onSuccess: () => {
      closeAddModal()
      showToast('Emergency contact added successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to add emergency contact', true)
    },
  })
}

function editContact(contact) {
  editingContact.value = contact
  editForm.id = contact.id
  editForm.first_name = contact.firstName || ''
  editForm.last_name = contact.lastName || ''
  editForm.relationship_id = contact.relationshipId
  editForm.contact_number = contact.contactNumber || ''
  showEditModal.value = true
  openMenuId.value = null
}

function updateContact() {
  editForm.put(route('hr.emergency.update', editForm.id), {
    onSuccess: () => {
      closeEditModal()
      showToast('Emergency contact updated successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      showToast(firstError || 'Failed to update emergency contact', true)
    },
  })
}

function confirmDelete(contact) {
  contactToDelete.value = contact
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteContact() {
  router.delete(route('hr.emergency.destroy', contactToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      contactToDelete.value = null
      showToast('Emergency contact archived successfully')
    },
    onError: () => {
      showToast('Failed to archive emergency contact', true)
    },
  })
}

function refreshContacts() {
  isRefreshing.value = true
  router.get(route('hr.emergency'), {}, {
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
        <h1 class="mhr-page-head__title">Emergency Contacts</h1>
        <p class="mhr-page-head__sub">Manage employee emergency contacts</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshContacts" />
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Emergency Contact
        </button>
      </div>
    </div>

    <!-- Event Context Banner -->
    <EventBanner 
      v-if="selectedEventData"
      :event-data="selectedEventData"
    />

    <!-- Search Filter -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search emergency contacts…" v-model="q" />
      </div>
    </div>

    <!-- Contacts Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th v-if="hrRole === 'admin'">EMPLOYEE</th>
              <th>CONTACT NAME</th>
              <th>RELATIONSHIP</th>
              <th>PHONE NUMBER</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td :colspan="hrRole === 'admin' ? 5 : 4" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No emergency contacts found
              </td>
            </tr>
            <tr v-for="contact in filtered" :key="contact.id">
              <td v-if="hrRole === 'admin'">
                <div style="font-weight:500;">{{ contact.employeeName }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ contact.employeeNumber }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);font-weight:500;">{{ contact.fullName }}</td>
              <td style="color:var(--mhr-ink-3);">{{ contact.relationshipName }}</td>
              <td style="font-family:monospace;color:var(--mhr-ink-2);">{{ contact.contactNumber }}</td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(contact.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div v-if="openMenuId === contact.id" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:180px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;">
                    <button @click="editContact(contact)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button @click="confirmDelete(contact)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
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

    <!-- Add Contact Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Emergency Contact</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new emergency contact</p>
            </div>
            <button class="mhr-icon-btn" @click="closeAddModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <!-- Employee field for admin: searchable dropdown -->
            <div v-if="employees.length > 0" class="mhr-field" style="grid-column:1/-1;position:relative;" data-employee-dropdown>
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

            <!-- Employee field for employee role: fixed display -->
            <div v-else class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">EMPLOYEE</label>
              <div style="padding:12px 16px;background:var(--mhr-surface-2);border:1px solid var(--mhr-line);border-radius:var(--mhr-r);color:var(--mhr-ink-2);">
                <div style="font-weight:500;font-size:14px;">{{ currentEmployee?.full_name || 'N/A' }}</div>
                <div style="font-size:13px;margin-top:2px;opacity:0.8;">{{ currentEmployee?.employee_number || '' }}</div>
              </div>
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">FIRST NAME *</label>
              <input class="mhr-input" v-model="form.first_name" placeholder="Enter first name" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">LAST NAME *</label>
              <input class="mhr-input" v-model="form.last_name" placeholder="Enter last name" />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">RELATIONSHIP *</label>
              <select class="mhr-select" v-model="form.relationship_id">
                <option :value="null">Select relationship...</option>
                <option v-for="rel in relationships" :key="rel.id" :value="rel.id">
                  {{ rel.title }}
                </option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">PHONE NUMBER *</label>
              <input class="mhr-input" v-model="form.contact_number" placeholder="+974 XXXX XXXX" />
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeAddModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="addContact"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="form.processing">Creating...</span>
            <span v-else>Create Contact</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Contact Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Emergency Contact</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">
                {{ editingContact?.employeeName }}
                <span v-if="editingContact?.employeeNumber" style="opacity:0.7;"> · {{ editingContact.employeeNumber }}</span>
              </p>
            </div>
            <button class="mhr-icon-btn" @click="closeEditModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>

        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="mhr-field">
              <label class="mhr-field__label">FIRST NAME *</label>
              <input class="mhr-input" v-model="editForm.first_name" />
            </div>

            <div class="mhr-field">
              <label class="mhr-field__label">LAST NAME *</label>
              <input class="mhr-input" v-model="editForm.last_name" />
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">RELATIONSHIP *</label>
              <select class="mhr-select" v-model="editForm.relationship_id">
                <option :value="null">Select relationship...</option>
                <option v-for="rel in relationships" :key="rel.id" :value="rel.id">
                  {{ rel.title }}
                </option>
              </select>
            </div>

            <div class="mhr-field" style="grid-column:1/-1;">
              <label class="mhr-field__label">PHONE NUMBER *</label>
              <input class="mhr-input" v-model="editForm.contact_number" />
            </div>
          </div>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeEditModal">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="updateContact"
            :disabled="editForm.processing"
            :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="editForm.processing">Updating...</span>
            <span v-else>Update Contact</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Archive Emergency Contact</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);">
            Are you sure you want to archive <strong>{{ contactToDelete?.fullName }}</strong>?
            This will remove them from the active emergency contacts list.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteContact">Archive</button>
        </div>
      </div>
    </div>
  </div>
</template>
