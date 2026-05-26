<script setup>
import { ref, computed, watch } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import { router, usePage } from '@inertiajs/vue3'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:              { type: String, default: 'employee' },
  hrPage:              { type: String, default: 'employee' },
  employees:           { type: Array,  default: () => [] },
  salutations:         { type: Array,  default: () => [] },
  designations:        { type: Array,  default: () => [] },
  departments:         { type: Array,  default: () => [] },
  directorates:        { type: Array,  default: () => [] },
  functionalAreas:     { type: Array,  default: () => [] },
  entities:            { type: Array,  default: () => [] },
  employeeTypes:       { type: Array,  default: () => [] },
  contractTypes:       { type: Array,  default: () => [] },
  salaryBases:         { type: Array,  default: () => [] },
  genders:             { type: Array,  default: () => [] },
  maritalStatuses:     { type: Array,  default: () => [] },
  nationalities:       { type: Array,  default: () => [] },
  countries:           { type: Array,  default: () => [] },
  reportingToOptions:  { type: Array,  default: () => [] },
})

const dateFormat = computed(() => usePage().props.dateFormat || 'DD/MM/YYYY')

const isAdmin = computed(() => ['admin', 'manager'].includes(props.hrRole))

// Get selected event for banner
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

const q    = ref('')
const dept = ref('All')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showImportModal = ref(false)
const showStatsModal = ref(false)
const editingEmployee = ref(null)
const employeeToDelete = ref(null)
const importFile = ref(null)
const fileInput = ref(null)
const importStats = ref(null)
const importErrors = ref([])
const hasFailures = ref(false)
const added = ref([])
const toast = ref(null)
const openMenuId = ref(null)
const menuPosition = ref({ top: 0, right: 0 })
const isRefreshing = ref(false)
const isImporting = ref(false)
const isUpdating = ref(false)
const showColumnMenu = ref(false)
const selectedEmployees = ref(new Set())
const showActionsMenu = ref(false)
const showAssignEventModal = ref(false)
const assignEventId = ref(null)
const isAssigning = ref(false)

const visibleColumns = ref({
  employeeNumber: true,
  role: true,
  department: true,
  email: true,
  contractStart: true,
  contractEnd: true,
  agreementNumber: false,
  nationalId: false,
  salutation: false,
  gender: false,
  maritalStatus: false,
  directorate: false,
  functionalArea: false,
  entity: false,
  contractType: true,
  personalEmail: false,
  phone: false,
  altPhone: false,
  dateOfBirth: false,
  dateOfHire: false,
  joinDate: false,
  sponsorshipName: false,
  nationalityName: false,
  nationalityCode: false,
  passportNumber: false,
  passportExpiry: false,
  civilIdExpiry: false,
  managerFlag: false,
  adminFlag: false,
})

const form = ref({
  // Basic Information
  firstName: '',
  middleName: '',
  lastName: '',
  salutationId: null,
  employeeNumber: '',
  agreementNumber: '',
  
  // Contact Information
  workEmail: '',
  personalEmail: '',
  phoneNumber: '',
  altPhoneNumber: '',
  phoneAreaCode: '',
  altAreaCode: '',
  
  // Employment Details
  designationId: null,
  departmentId: null,
  directorateId: null,
  functionalAreaId: null,
  salaryBasisId: null,
  employeeType: null,
  entityId: null,
  contractTypeId: null,
  reportingToId: null,
  
  // Contract Dates
  contractStartDate: null,
  contractEndDate: null,
  dateOfHire: null,
  joinDate: null,
  
  // Personal Information
  genderId: null,
  maritalStatusId: null,
  dateOfBirth: null,
  townOfBirth: '',
  countryOfBirth: '',
  nationalityId: null,
  languageId: null,
  
  // Identification
  nationalIdNumber: '',
  passportNumber: null,
  passportExpiry: null,
  civilIdExpiry: null,
  
  // Sponsorship
  sponsorshipId: '',
  sponsorshipName: '',
  
  // Flags
  managerFlag: 'N',
  administratorFlag: 'N',
})

const editForm = ref({
  id: null,
  // Basic Information
  firstName: '',
  middleName: '',
  lastName: '',
  salutationId: null,
  employeeNumber: '',
  agreementNumber: '',
  
  // Contact Information
  workEmail: '',
  personalEmail: '',
  phoneNumber: '',
  altPhoneNumber: '',
  phoneAreaCode: '',
  altAreaCode: '',
  
  // Employment Details
  designationId: null,
  departmentId: null,
  directorateId: null,
  functionalAreaId: null,
  salaryBasisId: null,
  employeeType: null,
  entityId: null,
  contractTypeId: null,
  reportingToId: null,
  
  // Contract Dates
  contractStartDate: null,
  contractEndDate: null,
  dateOfHire: null,
  joinDate: null,
  
  // Personal Information
  genderId: null,
  maritalStatusId: null,
  dateOfBirth: null,
  townOfBirth: '',
  countryOfBirth: '',
  nationalityId: null,
  languageId: null,
  
  // Identification
  nationalIdNumber: '',
  passportNumber: null,
  passportExpiry: null,
  civilIdExpiry: null,
  
  // Sponsorship
  sponsorshipId: '',
  sponsorshipName: '',
  
  // Flags
  managerFlag: 'N',
  administratorFlag: 'N',
})

const all  = computed(() => [...added.value, ...props.employees])
const depts = computed(() => ['All', ...new Set(all.value.map(p => p.dept))])
const filtered = computed(() =>
  all.value.filter(p =>
    (dept.value === 'All' || p.dept === dept.value) &&
    (q.value === '' || (
      p.name + 
      p.role + 
      p.email + 
      p.empNumber + 
      (p.personalEmail || '') + 
      (p.phone || '') + 
      (p.nationalId || '') + 
      (p.passportNumber || '')
    ).toLowerCase().includes(q.value.toLowerCase()))
  )
)

const visibleColumnsCount = computed(() => {
  const docsColumn = isAdmin.value ? 1 : 0
  return 1 + 1 + Object.values(visibleColumns.value).filter(Boolean).length + docsColumn + 1 // Checkbox + Employee name + visible cols + docs (if admin) + actions
})

const allSelected = computed(() => {
  return filtered.value.length > 0 && filtered.value.every(emp => selectedEmployees.value.has(emp.id))
})

// Close dropdown menu when filtered list changes to prevent DOM errors
watch([q, dept, () => props.employees], () => {
  openMenuId.value = null
})

const someSelected = computed(() => {
  return filtered.value.some(emp => selectedEmployees.value.has(emp.id)) && !allSelected.value
})

function toggleSelectAll() {
  if (allSelected.value) {
    // Deselect all
    filtered.value.forEach(emp => selectedEmployees.value.delete(emp.id))
  } else {
    // Select all
    filtered.value.forEach(emp => selectedEmployees.value.add(emp.id))
  }
}

function toggleSelect(id) {
  if (selectedEmployees.value.has(id)) {
    selectedEmployees.value.delete(id)
  } else {
    selectedEmployees.value.add(id)
  }
}

function exportSelected() {
  const selectedIds = Array.from(selectedEmployees.value)
  toast.value = `Exporting ${selectedIds.length} employee(s)...`
  setTimeout(() => { toast.value = null }, 3000)
  // TODO: Implement actual export functionality
  console.log('Exporting employees:', selectedIds)
}

function assignToEvent() {
  showAssignEventModal.value = true
}

function confirmAssignToEvent() {
  if (!assignEventId.value) {
    toast.value = 'Please select an event'
    setTimeout(() => { toast.value = null }, 3000)
    return
  }

  const selectedIds = Array.from(selectedEmployees.value)
  isAssigning.value = true

  router.post(route('hr.employee.assign-to-event'), {
    employee_ids: selectedIds,
    event_id: assignEventId.value
  }, {
    onSuccess: () => {
      isAssigning.value = false
      showAssignEventModal.value = false
      assignEventId.value = null
      selectedEmployees.value.clear()
      toast.value = `Successfully assigned ${selectedIds.length} employee(s) to event`
      setTimeout(() => { toast.value = null }, 3000)
    },
    onError: (errors) => {
      isAssigning.value = false
      console.error('Failed to assign employees:', errors)
      const firstError = Object.values(errors)[0]
      toast.value = firstError || 'Failed to assign employees to event'
      setTimeout(() => { toast.value = null }, 3000)
    }
  })
}

function deleteSelected() {
  const selectedIds = Array.from(selectedEmployees.value)
  if (confirm(`Are you sure you want to delete ${selectedIds.length} employee(s)?`)) {
    toast.value = `Deleting ${selectedIds.length} employee(s)...`
    setTimeout(() => { toast.value = null }, 3000)
    // TODO: Implement bulk delete functionality
    console.log('Deleting employees:', selectedIds)
    selectedEmployees.value.clear()
  }
}

const columnPreview = computed(() => {
  if (all.value.length === 0) return {}
  
  const first = all.value[0]
  return {
    employeeNumber: first.empNumber,
    role: first.role,
    department: first.dept,
    email: first.email,
    personalEmail: first.personalEmail || 'N/A',
    phone: first.phone || 'N/A',
    altPhone: first.altPhone || 'N/A',
    contractStart: fmtDate(first.contractStart),
    contractEnd: fmtDate(first.contractEnd),
    agreementNumber: first.agreementNumber || 'N/A',
    nationalId: first.nationalId || 'N/A',
    salutation: first.salutation || 'N/A',
    gender: first.gender || 'N/A',
    maritalStatus: first.maritalStatus || 'N/A',
    directorate: first.directorate || 'N/A',
    functionalArea: first.functionalArea || 'N/A',
    entity: first.entity || 'N/A',
    contractType: first.contractType || 'N/A',
    dateOfBirth: fmtDate(first.dateOfBirth),
    dateOfHire: fmtDate(first.dateOfHire),
    joinDate: fmtDate(first.joinDate),
    sponsorshipName: first.sponsorshipName || 'N/A',
    nationalityName: first.nationalityName || 'N/A',
    nationalityCode: first.nationalityCode || 'N/A',
    passportNumber: first.passportNumber || 'N/A',
    passportExpiry: fmtDate(first.passportExpiry),
    civilIdExpiry: fmtDate(first.civilIdExpiry),
    managerFlag: first.managerFlag || 'N',
    adminFlag: first.adminFlag || 'N',
  }
})

function fmtDate(s) {
  if (!s) return ''
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : s)
  return applyFormat(d, dateFormat.value)
}

// Convert Date object to MySQL-compatible YYYY-MM-DD format
function toMySQLDate(date) {
  if (!date) return null
  if (typeof date === 'string') return date
  if (date instanceof Date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }
  return null
}

// Convert date string to Date object for DatePicker
function parseDate(dateStr) {
  if (!dateStr) return null
  if (dateStr instanceof Date) return dateStr
  if (typeof dateStr === 'string') {
    const date = new Date(dateStr)
    return isNaN(date.getTime()) ? null : date
  }
  return null
}

function addEmployee() {
  router.post(route('hr.employee.store'), {
    first_name: form.value.firstName,
    middle_name: form.value.middleName || '',
    last_name: form.value.lastName,
    employee_number: form.value.employeeNumber,
    agreement_number: form.value.agreementNumber || '',
    salutation_id: form.value.salutationId || null,
    work_email_address: form.value.workEmail,
    personal_email_address: form.value.personalEmail || '',
    phone_number: form.value.phoneNumber || '',
    alt_phone_number: form.value.altPhoneNumber || '',
    phone_area_code: form.value.phoneAreaCode || '',
    alt_area_code: form.value.altAreaCode || '',
    designation_id: form.value.designationId || null,
    department_id: form.value.departmentId || null,
    directorate_id: form.value.directorateId || null,
    functional_area_id: form.value.functionalAreaId || null,
    salary_basis_id: form.value.salaryBasisId || null,
    employee_type: form.value.employeeType || null,
    entity_id: form.value.entityId || null,
    contract_type_id: form.value.contractTypeId || null,
    reporting_to_id: form.value.reportingToId || null,
    contract_start_date: toMySQLDate(form.value.contractStartDate),
    contract_end_date: toMySQLDate(form.value.contractEndDate),
    date_of_hire: toMySQLDate(form.value.dateOfHire),
    join_date: toMySQLDate(form.value.joinDate),
    gender_id: form.value.genderId || null,
    marital_status_id: form.value.maritalStatusId || null,
    date_of_birth: toMySQLDate(form.value.dateOfBirth),
    town_of_birth: form.value.townOfBirth || '',
    country_of_birth: form.value.countryOfBirth || null,
    nationality_id: form.value.nationalityId || null,
    language_id: form.value.languageId ? String(form.value.languageId) : '',
    national_identifier_number: form.value.nationalIdNumber || '',
    passport_number: form.value.passportNumber || '',
    passport_expiry: toMySQLDate(form.value.passportExpiry),
    civil_id_expiry: toMySQLDate(form.value.civilIdExpiry),
    sponsorship_id: form.value.sponsorshipId || '',
    sponsorship_name: form.value.sponsorshipName || '',
    manager_flag: form.value.managerFlag,
    administrator_flag: form.value.administratorFlag,
  }, {
    onSuccess: () => {
      showAddModal.value = false
      toast.value = 'Employee added successfully'
      setTimeout(() => { toast.value = null }, 3000)
      // Reset form
      form.value = {
        firstName: '', middleName: '', lastName: '', salutationId: null, employeeNumber: '', agreementNumber: '',
        workEmail: '', personalEmail: '', phoneNumber: '', altPhoneNumber: '', phoneAreaCode: '', altAreaCode: '',
        designationId: null, departmentId: null, directorateId: null, functionalAreaId: null, salaryBasisId: null,
        employeeType: null, entityId: null, contractTypeId: null, reportingToId: null,
        contractStartDate: null, contractEndDate: null, dateOfHire: null, joinDate: null,
        genderId: null, maritalStatusId: null, dateOfBirth: null, townOfBirth: '', countryOfBirth: '',
        nationalityId: null, languageId: null, nationalIdNumber: '', passportNumber: null, passportExpiry: null,
        civilIdExpiry: null, sponsorshipId: '', sponsorshipName: '', managerFlag: 'N', administratorFlag: 'N',
      }
    },
    onError: (errors) => {
      console.error('Failed to add employee:', errors)
      const firstError = Object.values(errors)[0]
      toast.value = firstError || 'Failed to add employee'
      setTimeout(() => { toast.value = null }, 3000)
    }
  })
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    openMenuId.value = null
    return
  }
  const rect = event.currentTarget.getBoundingClientRect()
  const estimatedHeight = 178
  const openUp = rect.bottom + 4 + estimatedHeight > window.innerHeight
  menuPosition.value = {
    top:    openUp ? null : rect.bottom + 4,
    bottom: openUp ? window.innerHeight - rect.top + 4 : null,
    right:  window.innerWidth - rect.right,
  }
  openMenuId.value = id
}

function editEmployee(emp) {
  editingEmployee.value = emp
  
  // Helper to validate ID exists in list
  const validId = (id, list) => id && list.some(item => item.id === id) ? id : null
  
  // Populate form with employee data
  editForm.value = {
    id: emp.id,
    
    // Basic Information
    firstName: emp.firstName || '',
    middleName: emp.middleName || '',
    lastName: emp.lastName || '',
    salutationId: validId(emp.salutation_id, props.salutations),
    employeeNumber: emp.empNumber || '',
    agreementNumber: emp.agreementNumber || '',
    
    // Contact Information
    workEmail: emp.email || '',
    personalEmail: emp.personalEmail || '',
    phoneAreaCode: emp.phone_area_code || '',
    phoneNumber: emp.phone || '',
    altAreaCode: emp.alt_area_code || '',
    altPhoneNumber: emp.altPhone || '',
    
    // Employment Details
    designationId: validId(emp.designation_id, props.designations),
    departmentId: validId(emp.department_id, props.departments),
    directorateId: validId(emp.directorate_id, props.directorates),
    functionalAreaId: validId(emp.functional_area_id, props.functionalAreas),
    salaryBasisId: validId(emp.salary_basis_id, props.salaryBases),
    employeeType: validId(emp.employee_type, props.employeeTypes),
    entityId: validId(emp.entityId, props.entities),
    contractTypeId: validId(emp.contractTypeId, props.contractTypes),
    reportingToId: validId(emp.reporting_to_id, props.reportingToOptions),
    
    // Contract Dates
    contractStartDate: parseDate(emp.contractStart),
    contractEndDate: parseDate(emp.contractEnd),
    dateOfHire: parseDate(emp.dateOfHire),
    joinDate: parseDate(emp.joinDate),
    
    // Personal Information
    genderId: validId(emp.gender_id, props.genders),
    maritalStatusId: validId(emp.marital_status_id, props.maritalStatuses),
    dateOfBirth: parseDate(emp.dateOfBirth),
    townOfBirth: emp.town_of_birth || '',
    countryOfBirth: validId(emp.country_of_birth, props.countries),
    nationalityId: validId(emp.nationality_id, props.nationalities),
    languageId: emp.language_id ? String(emp.language_id) : '',
    
    // Identification
    nationalIdNumber: emp.nationalId || '',
    passportNumber: emp.passportNumber || null,
    passportExpiry: parseDate(emp.passportExpiry),
    civilIdExpiry: parseDate(emp.civilIdExpiry),
    
    // Sponsorship
    sponsorshipId: emp.sponsorship_id || '',
    sponsorshipName: emp.sponsorshipName || '',
    
    // Flags
    managerFlag: emp.managerFlag || 'N',
    administratorFlag: emp.adminFlag || 'N',
  }
  showEditModal.value = true
  openMenuId.value = null
}

function duplicateEmployee(emp) {
  toast.value = `Duplicating ${emp.name}...`
  setTimeout(() => { toast.value = null }, 3000)
  openMenuId.value = null
}

function updateDocument(emp) {
  openMenuId.value = null
  router.get(route('hr.documents'), {
    employee_id: emp.id
  })
}

function viewEmployeeDocuments(emp) {
  router.get(route('hr.documents'), {
    employee_id: emp.id
  })
}

function deleteEmployee(emp) {
  employeeToDelete.value = emp
  showDeleteModal.value = true
  openMenuId.value = null
}

function confirmDelete() {
  if (!employeeToDelete.value) return
  
  router.delete(route('hr.employee.destroy', employeeToDelete.value.id), {
    onSuccess: () => {
      toast.value = `${employeeToDelete.value.name} archived successfully`
      setTimeout(() => { toast.value = null }, 3000)
      showDeleteModal.value = false
      employeeToDelete.value = null
    }
  })
}

function refreshEmployees() {
  isRefreshing.value = true
  router.reload({
    only: ['employees'],
    onFinish: () => {
      setTimeout(() => {
        isRefreshing.value = false
      }, 500)
    }
  })
}

function downloadTemplate() {
  window.location.href = route('hr.employee.template')
}

function openImportModal() {
  showImportModal.value = true
  importFile.value = null
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

async function importEmployees() {
  if (!importFile.value) {
    alert('Please select a file to import')
    return
  }

  isImporting.value = true
  const formData = new FormData()
  formData.append('file', importFile.value)

  try {
    const response = await fetch(route('hr.employee.import'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: formData,
    })

    const data = await response.json()

    if (data.success) {
      importStats.value = data.stats
      importErrors.value = data.errors || []
      hasFailures.value = data.hasFailures || false
      showImportModal.value = false
      showStatsModal.value = true
      importFile.value = null
      
      // Refresh employee list
      router.reload({ only: ['employees'] })
    } else {
      toast.value = data.message || 'Import failed'
      setTimeout(() => { toast.value = null }, 5000)
    }
  } catch (error) {
    toast.value = 'Import failed: ' + error.message
    setTimeout(() => { toast.value = null }, 5000)
  } finally {
    isImporting.value = false
  }
}

function exportFailedRows() {
  window.location.href = route('hr.employee.export.failed')
}

function updateEmployee() {
  isUpdating.value = true
  router.put(route('hr.employee.update', editForm.value.id), {
    // Basic Information
    first_name: editForm.value.firstName,
    middle_name: editForm.value.middleName || '',
    last_name: editForm.value.lastName,
    salutation_id: editForm.value.salutationId || null,
    employee_number: editForm.value.employeeNumber,
    agreement_number: editForm.value.agreementNumber || '',
    
    // Contact Information
    work_email_address: editForm.value.workEmail,
    personal_email_address: editForm.value.personalEmail || '',
    phone_area_code: editForm.value.phoneAreaCode || '',
    phone_number: editForm.value.phoneNumber || '',
    alt_area_code: editForm.value.altAreaCode || '',
    alt_phone_number: editForm.value.altPhoneNumber || '',
    
    // Employment Details
    designation_id: editForm.value.designationId || null,
    department_id: editForm.value.departmentId || null,
    directorate_id: editForm.value.directorateId || null,
    functional_area_id: editForm.value.functionalAreaId || null,
    salary_basis_id: editForm.value.salaryBasisId || null,
    employee_type: editForm.value.employeeType || null,
    entity_id: editForm.value.entityId || null,
    contract_type_id: editForm.value.contractTypeId || null,
    reporting_to_id: editForm.value.reportingToId || null,
    
    // Contract & Dates
    contract_start_date: toMySQLDate(editForm.value.contractStartDate),
    contract_end_date: toMySQLDate(editForm.value.contractEndDate),
    date_of_hire: toMySQLDate(editForm.value.dateOfHire),
    join_date: toMySQLDate(editForm.value.joinDate),
    
    // Personal Information
    gender_id: editForm.value.genderId || null,
    marital_status_id: editForm.value.maritalStatusId || null,
    date_of_birth: toMySQLDate(editForm.value.dateOfBirth),
    town_of_birth: editForm.value.townOfBirth || '',
    country_of_birth: editForm.value.countryOfBirth || null,
    nationality_id: editForm.value.nationalityId || null,
    language_id: editForm.value.languageId ? String(editForm.value.languageId) : '',
    
    // Identification
    national_identifier_number: editForm.value.nationalIdNumber || '',
    passport_number: editForm.value.passportNumber || '',
    passport_expiry: toMySQLDate(editForm.value.passportExpiry),
    civil_id_expiry: toMySQLDate(editForm.value.civilIdExpiry),
    
    // Sponsorship
    sponsorship_id: editForm.value.sponsorshipId || '',
    sponsorship_name: editForm.value.sponsorshipName || '',
    
    // Flags
    manager_flag: editForm.value.managerFlag,
    administrator_flag: editForm.value.administratorFlag,
  }, {
    onSuccess: () => {
      isUpdating.value = false
      showEditModal.value = false
      toast.value = 'Employee updated successfully'
      setTimeout(() => { toast.value = null }, 3000)
    },
    onError: (errors) => {
      isUpdating.value = false
      console.error('Failed to update employee:', errors)
      const firstError = Object.values(errors)[0]
      toast.value = firstError || 'Failed to update employee'
      setTimeout(() => { toast.value = null }, 3000)
    },
    onFinish: () => {
      isUpdating.value = false
    }
  })
}
</script>

<template>
  <div @click="openMenuId = null; showColumnMenu = false; showActionsMenu = false">
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">{{ hrPage === 'master-employee' ? 'Employee Master List' : 'Employees' }}</h1>
        <p class="mhr-page-head__sub">{{ hrPage === 'master-employee' ? 'Complete database of all employees' : `${filtered.length} of ${all.length} people` }}</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshEmployees" />
        <div v-if="selectedEmployees.size > 0" style="position:relative;">
          <button class="mhr-btn mhr-btn--accent" @click.stop="showActionsMenu = !showActionsMenu">
            <AppIcon name="check" :size="14" />
            <span>{{ selectedEmployees.size }} Selected</span>
            <AppIcon name="chevron-down" :size="12" />
          </button>
          <div v-if="showActionsMenu" @click.stop class="mhr-dropdown" style="position:absolute;left:0;top:100%;margin-top:4px;min-width:200px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;padding:8px;">
            <button @click="() => { exportSelected(); showActionsMenu = false }" class="mhr-dropdown-item">
              <AppIcon name="download" :size="14" style="color:var(--mhr-ink-2);" />
              <span>Export Selected</span>
            </button>
            <button @click="() => { assignToEvent(); showActionsMenu = false }" class="mhr-dropdown-item">
              <AppIcon name="calendar" :size="14" style="color:var(--mhr-ink-2);" />
              <span>Assign to Event</span>
            </button>
            <div style="height:1px;background:var(--mhr-line);margin:4px 0;"></div>
            <button @click="() => { deleteSelected(); showActionsMenu = false }" class="mhr-dropdown-item" style="color:var(--mhr-danger);">
              <AppIcon name="trash" :size="14" />
              <span>Delete Selected</span>
            </button>
          </div>
        </div>
        <button v-if="hrRole === 'admin' && hrPage === 'master-employee'" class="mhr-btn mhr-btn--outline" @click="downloadTemplate">
          <AppIcon name="download" :size="14" /> Download Template
        </button>
        <button v-if="hrRole === 'admin' && hrPage === 'master-employee'" class="mhr-btn mhr-btn--outline" @click="openImportModal">
          <AppIcon name="upload" :size="14" /> Import
        </button>
        <button v-if="hrRole === 'admin'" class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add employee
        </button>
      </div>
    </div>

    <!-- Event Context Banner (when viewing event-filtered employees) -->
    <div
      v-if="selectedEventData && hrPage !== 'master-employee'"
      class="mhr-card"
      style="margin-bottom:12px;background:linear-gradient(135deg,var(--green-700),var(--green-800));color:#fff;border:none;padding:8px 12px;"
    >
      <div style="display:flex;align-items:center;gap:8px;">
        <div
          v-if="selectedEventData.logo"
          style="width:24px;height:24px;border-radius:4px;background:rgba(255,255,255,0.15);padding:3px;flex-shrink:0;"
        >
          <img
            :src="selectedEventData.logo"
            :alt="selectedEventData.name"
            style="width:100%;height:100%;object-fit:contain;"
          />
        </div>
        <AppIcon
          v-else
          name="calendar"
          :size="14"
          style="opacity:0.8;"
        />
        <div style="flex:1;min-width:0;">
          <div style="font-size:10px;opacity:0.75;text-transform:uppercase;letter-spacing:0.5px;font-weight:500;line-height:1;">Viewing Event</div>
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">{{ selectedEventData.name }}</div>
        </div>
        <AppIcon name="check" :size="14" style="opacity:0.75;flex-shrink:0;" />
      </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div v-if="selectedEmployees.size > 0" class="mhr-card" style="margin-bottom:14px;padding:12px 16px;display:flex;align-items:center;gap:12px;background:var(--mhr-accent-soft);border:1px solid var(--mhr-accent);">
      <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--mhr-ink);">
        <AppIcon name="check" :size="16" style="color:var(--mhr-accent);" />
        <span>{{ selectedEmployees.size }} employee{{ selectedEmployees.size > 1 ? 's' : '' }} selected</span>
      </div>
      <div style="flex:1;"></div>
      <button class="mhr-btn mhr-btn--sm mhr-btn--outline" @click="selectedEmployees.clear()">
        Clear Selection
      </button>
      <button class="mhr-btn mhr-btn--sm mhr-btn--primary">
        <AppIcon name="download" :size="14" /> Export Selected
      </button>
    </div>

    <!-- Filters -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search employees by name, email, phone, ID…" v-model="q" />
      </div>
      <div style="position:relative;">
        <button class="mhr-btn mhr-btn--outline" @click.stop="showColumnMenu = !showColumnMenu" style="min-width:120px;">
          <AppIcon name="settings" :size="14" /> Columns
        </button>
        <div v-if="showColumnMenu" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:220px;max-height:400px;overflow-y:auto;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;padding:8px;">
          <div style="padding:8px 12px;font-size:11px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;position:sticky;top:0;background:white;z-index:1;">Toggle Columns</div>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.employeeNumber" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Employee #</span>
              <div v-if="visibleColumns.employeeNumber" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.employeeNumber }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.role" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Role</span>
              <div v-if="visibleColumns.role" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.role }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.department" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Department</span>
              <div v-if="visibleColumns.department" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.department }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.email" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Work Email</span>
              <div v-if="visibleColumns.email" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.email }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.personalEmail" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Personal Email</span>
              <div v-if="visibleColumns.personalEmail" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.personalEmail }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.phone" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Phone</span>
              <div v-if="visibleColumns.phone" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.phone }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.altPhone" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Alt Phone</span>
              <div v-if="visibleColumns.altPhone" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.altPhone }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.contractStart" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Contract Start</span>
              <div v-if="visibleColumns.contractStart" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.contractStart }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.contractEnd" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Contract End</span>
              <div v-if="visibleColumns.contractEnd" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.contractEnd }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.agreementNumber" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Agreement #</span>
              <div v-if="visibleColumns.agreementNumber" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.agreementNumber }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.nationalId" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>National ID</span>
              <div v-if="visibleColumns.nationalId" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.nationalId }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.salutation" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Salutation</span>
              <div v-if="visibleColumns.salutation" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.salutation }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.gender" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Gender</span>
              <div v-if="visibleColumns.gender" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.gender }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.maritalStatus" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Marital Status</span>
              <div v-if="visibleColumns.maritalStatus" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.maritalStatus }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.directorate" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Directorate</span>
              <div v-if="visibleColumns.directorate" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.directorate }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.functionalArea" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Functional Area</span>
              <div v-if="visibleColumns.functionalArea" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.functionalArea }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.entity" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Entity</span>
              <div v-if="visibleColumns.entity" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.entity }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.contractType" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Contract Type</span>
              <div v-if="visibleColumns.contractType" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.contractType }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.dateOfBirth" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Date of Birth</span>
              <div v-if="visibleColumns.dateOfBirth" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.dateOfBirth }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.dateOfHire" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Date of Hire</span>
              <div v-if="visibleColumns.dateOfHire" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.dateOfHire }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.joinDate" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Join Date</span>
              <div v-if="visibleColumns.joinDate" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.joinDate }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.sponsorshipName" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Sponsorship</span>
              <div v-if="visibleColumns.sponsorshipName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.sponsorshipName }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.nationalityName" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Nationality</span>
              <div v-if="visibleColumns.nationalityName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.nationalityName }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.nationalityCode" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Nationality Code</span>
              <div v-if="visibleColumns.nationalityCode" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.nationalityCode }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.passportNumber" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Passport #</span>
              <div v-if="visibleColumns.passportNumber" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.passportNumber }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.passportExpiry" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Passport Expiry</span>
              <div v-if="visibleColumns.passportExpiry" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.passportExpiry }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.civilIdExpiry" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Civil ID Expiry</span>
              <div v-if="visibleColumns.civilIdExpiry" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.civilIdExpiry }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.managerFlag" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Manager</span>
              <div v-if="visibleColumns.managerFlag" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.managerFlag }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <input type="checkbox" v-model="visibleColumns.adminFlag" style="cursor:pointer;" />
            <div style="flex:1;">
              <span>Administrator</span>
              <div v-if="visibleColumns.adminFlag" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ columnPreview.adminFlag }}</div>
            </div>
          </label>
        </div>
      </div>
      <div v-if="all.length > 0" style="display:flex;gap:4px;padding:3px;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:9px;overflow:auto;">
        <button v-for="d in depts" :key="d"
          class="mhr-btn mhr-btn--sm"
          :style="dept === d ? 'background:var(--green-700);color:#fff;' : 'background:transparent;color:var(--mhr-ink-2);'"
          @click="dept = d">
          {{ d }}
        </button>
      </div>
    </div>

    <div class="mhr-card">
      <div class="mhr-table-container">
        <table class="mhr-table">
          <thead>
            <tr>
              <th style="width: 40px;">
                <input 
                  type="checkbox" 
                  :checked="allSelected" 
                  :indeterminate="someSelected"
                  @change="toggleSelectAll"
                  class="mhr-checkbox"
                  style="cursor: pointer;"
                />
              </th>
              <th>Employee</th>
              <th v-if="visibleColumns.employeeNumber">Employee #</th>
              <th v-if="visibleColumns.role">Role</th>
              <th v-if="visibleColumns.department">Department</th>
            <th v-if="visibleColumns.email">Work Email</th>
            <th v-if="visibleColumns.personalEmail">Personal Email</th>
            <th v-if="visibleColumns.phone">Phone</th>
            <th v-if="visibleColumns.altPhone">Alt Phone</th>
            <th v-if="visibleColumns.contractStart">Contract Start</th>
            <th v-if="visibleColumns.contractEnd">Contract End</th>
            <th v-if="visibleColumns.agreementNumber">Agreement #</th>
            <th v-if="visibleColumns.nationalId">National ID</th>
            <th v-if="visibleColumns.salutation">Salutation</th>
            <th v-if="visibleColumns.gender">Gender</th>
            <th v-if="visibleColumns.maritalStatus">Marital Status</th>
            <th v-if="visibleColumns.directorate">Directorate</th>
            <th v-if="visibleColumns.functionalArea">Functional Area</th>
            <th v-if="visibleColumns.entity">Entity</th>
            <th v-if="visibleColumns.contractType">Contract Type</th>
            <th v-if="visibleColumns.dateOfBirth">Date of Birth</th>
            <th v-if="visibleColumns.dateOfHire">Date of Hire</th>
            <th v-if="visibleColumns.joinDate">Join Date</th>
            <th v-if="visibleColumns.sponsorshipName">Sponsorship</th>
            <th v-if="visibleColumns.nationalityName">Nationality</th>
            <th v-if="visibleColumns.nationalityCode">Nationality Code</th>
            <th v-if="visibleColumns.passportNumber">Passport #</th>
            <th v-if="visibleColumns.passportExpiry">Passport Expiry</th>
            <th v-if="visibleColumns.civilIdExpiry">Civil ID Expiry</th>
            <th v-if="visibleColumns.managerFlag">Manager</th>
            <th v-if="visibleColumns.adminFlag">Admin</th>
            <th v-if="isAdmin" style="width: 60px;text-align:center;">Docs</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td :colspan="visibleColumnsCount" style="text-align:center;padding:60px 20px;">
              <div v-if="!selectedEventData && hrPage !== 'master-employee'" style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                <AppIcon name="calendar" :size="48" style="opacity:0.2;" />
                <div>
                  <div style="font-size:15px;font-weight:600;color:var(--mhr-ink-2);margin-bottom:4px;">No Event Selected</div>
                  <div style="font-size:13px;color:var(--mhr-ink-3);">Please select an event from the sidebar to view its employees</div>
                </div>
              </div>
              <div v-else style="color:var(--mhr-ink-3);">No employees found</div>
            </td>
          </tr>
          <tr v-for="p in filtered" :key="p.id">
            <td style="width: 40px;">
              <input 
                type="checkbox" 
                :checked="selectedEmployees.has(p.id)" 
                @change="toggleSelect(p.id)"
                class="mhr-checkbox"
                style="cursor: pointer;"
              />
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:12px;">
                <AppAvatar :name="p.name" :c="p.c" />
                <div style="font-weight:500;">{{ p.name }}</div>
              </div>
            </td>
            <td v-if="visibleColumns.employeeNumber"><span class="mhr-mono" style="font-size:12px;color:var(--mhr-ink-2);">{{ p.empNumber }}</span></td>
            <td v-if="visibleColumns.role">{{ p.role }}</td>
            <td v-if="visibleColumns.department"><span class="mhr-pill mhr-pill--plain">{{ p.dept }}</span></td>
            <td v-if="visibleColumns.email" style="color:var(--mhr-ink-3);">{{ p.email }}</td>
            <td v-if="visibleColumns.personalEmail" style="color:var(--mhr-ink-3);">{{ p.personalEmail }}</td>
            <td v-if="visibleColumns.phone" style="color:var(--mhr-ink-3);">{{ p.phone }}</td>
            <td v-if="visibleColumns.altPhone" style="color:var(--mhr-ink-3);">{{ p.altPhone }}</td>
            <td v-if="visibleColumns.contractStart" style="color:var(--mhr-ink-3);">{{ fmtDate(p.contractStart) }}</td>
            <td v-if="visibleColumns.contractEnd" style="color:var(--mhr-ink-3);">{{ fmtDate(p.contractEnd) }}</td>
            <td v-if="visibleColumns.agreementNumber" style="color:var(--mhr-ink-3);">{{ p.agreementNumber }}</td>
            <td v-if="visibleColumns.nationalId" style="color:var(--mhr-ink-3);">{{ p.nationalId }}</td>
            <td v-if="visibleColumns.salutation" style="color:var(--mhr-ink-3);">{{ p.salutation }}</td>
            <td v-if="visibleColumns.gender" style="color:var(--mhr-ink-3);">{{ p.gender }}</td>
            <td v-if="visibleColumns.maritalStatus" style="color:var(--mhr-ink-3);">{{ p.maritalStatus }}</td>
            <td v-if="visibleColumns.directorate" style="color:var(--mhr-ink-3);">{{ p.directorate }}</td>
            <td v-if="visibleColumns.functionalArea" style="color:var(--mhr-ink-3);">{{ p.functionalArea }}</td>
            <td v-if="visibleColumns.entity" style="color:var(--mhr-ink-3);">{{ p.entity }}</td>
            <td v-if="visibleColumns.contractType" style="color:var(--mhr-ink-3);">{{ p.contractType }}</td>
            <td v-if="visibleColumns.dateOfBirth" style="color:var(--mhr-ink-3);">{{ fmtDate(p.dateOfBirth) }}</td>
            <td v-if="visibleColumns.dateOfHire" style="color:var(--mhr-ink-3);">{{ fmtDate(p.dateOfHire) }}</td>
            <td v-if="visibleColumns.joinDate" style="color:var(--mhr-ink-3);">{{ fmtDate(p.joinDate) }}</td>
            <td v-if="visibleColumns.sponsorshipName" style="color:var(--mhr-ink-3);">{{ p.sponsorshipName }}</td>
            <td v-if="visibleColumns.nationalityName" style="color:var(--mhr-ink-3);">{{ p.nationalityName }}</td>
            <td v-if="visibleColumns.nationalityCode" style="color:var(--mhr-ink-3);">{{ p.nationalityCode }}</td>
            <td v-if="visibleColumns.passportNumber" style="color:var(--mhr-ink-3);">{{ p.passportNumber }}</td>
            <td v-if="visibleColumns.passportExpiry" style="color:var(--mhr-ink-3);">{{ fmtDate(p.passportExpiry) }}</td>
            <td v-if="visibleColumns.civilIdExpiry" style="color:var(--mhr-ink-3);">{{ fmtDate(p.civilIdExpiry) }}</td>
            <td v-if="visibleColumns.managerFlag" style="color:var(--mhr-ink-3);">{{ p.managerFlag }}</td>
            <td v-if="visibleColumns.adminFlag" style="color:var(--mhr-ink-3);">{{ p.adminFlag }}</td>
            <td v-if="isAdmin" style="text-align:center;">
              <button 
                v-if="p.documentsCount > 0"
                @click.stop="viewEmployeeDocuments(p)"
                class="mhr-icon-btn" 
                style="width:28px;height:28px;position:relative;"
                :title="`View ${p.documentsCount} document${p.documentsCount > 1 ? 's' : ''}`"
              >
                <AppIcon name="doc" :size="13" />
                <span style="position:absolute;top:-6px;right:-6px;background:var(--green-600);color:white;font-size:9px;font-weight:600;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 4px;box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                  {{ p.documentsCount }}
                </span>
              </button>
              <span v-else style="color:var(--mhr-ink-4);font-size:11px;">—</span>
            </td>
            <td>
              <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(p.id, $event)">
                <AppIcon name="more" :size="13" />
              </button>
              <Teleport to=".meridian-app" v-if="openMenuId === p.id">
                <div @click.stop class="mhr-dropdown" :style="{ position:'fixed', top: menuPosition.top != null ? menuPosition.top+'px' : 'auto', bottom: menuPosition.bottom != null ? menuPosition.bottom+'px' : 'auto', right: menuPosition.right+'px', minWidth:'180px', background:'var(--mhr-surface)', border:'1px solid var(--mhr-line)', borderRadius:'8px', boxShadow:'0 4px 12px rgba(0,0,0,0.1)', zIndex:9999 }">
                  <button @click="editEmployee(p)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.target.style.background='var(--mhr-surface)'" @mouseleave="$event.target.style.background='transparent'">
                    <AppIcon name="edit" :size="14" />
                    <span>Edit</span>
                  </button>
                  <button @click="duplicateEmployee(p)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.target.style.background='var(--mhr-surface)'" @mouseleave="$event.target.style.background='transparent'">
                    <AppIcon name="copy" :size="14" />
                    <span>Duplicate</span>
                  </button>
                  <button @click="updateDocument(p)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.target.style.background='var(--mhr-surface)'" @mouseleave="$event.target.style.background='transparent'">
                    <AppIcon name="doc" :size="14" />
                    <span>Document management</span>
                  </button>
                  <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                  <button @click="deleteEmployee(p)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.target.style.background='var(--mhr-surface)'" @mouseleave="$event.target.style.background='transparent'">
                    <AppIcon name="trash" :size="14" />
                    <span>Delete</span>
                  </button>
                </div>
              </Teleport>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>

    <!-- Add Employee Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="showAddModal = false">
      <div class="mhr-modal mhr-modal--lg" style="max-height:90vh;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Add Employee</h2>
          <p class="mhr-modal__sub">Fill in comprehensive employee information</p>
        </div>
        <div class="mhr-modal__body" style="max-height:calc(90vh - 140px);overflow-y:auto;padding-right:8px;">
          
          <!-- Basic Information Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Basic Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Salutation</label>
                <select class="mhr-select" v-model="form.salutationId">
                  <option :value="null">Select...</option>
                  <option v-for="sal in salutations" :key="sal.id" :value="sal.id">{{ sal.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">First Name *</label>
                <input class="mhr-input" v-model="form.firstName" placeholder="Jane" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Middle Name</label>
                <input class="mhr-input" v-model="form.middleName" placeholder="Marie" />
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Last Name *</label>
                <input class="mhr-input" v-model="form.lastName" placeholder="Smith" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Employee Number *</label>
                <input class="mhr-input" v-model="form.employeeNumber" placeholder="EMP-00001" />
              </div>
            </div>
            <div class="mhr-field" style="margin-top:12px;">
              <label class="mhr-field__label">Agreement Number</label>
              <input class="mhr-input" v-model="form.agreementNumber" placeholder="AGR-2026-001" />
            </div>
          </div>

          <!-- Contact Information Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Contact Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Work Email *</label>
                <input class="mhr-input" type="email" v-model="form.workEmail" placeholder="jane.smith@company.com" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Personal Email</label>
                <input class="mhr-input" type="email" v-model="form.personalEmail" placeholder="jane@personal.com" />
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Area Code</label>
                <input class="mhr-input" v-model="form.phoneAreaCode" placeholder="123" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Phone Number</label>
                <input class="mhr-input" v-model="form.phoneNumber" placeholder="+1234567890" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Alt Area Code</label>
                <input class="mhr-input" v-model="form.altAreaCode" placeholder="098" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Alt Phone</label>
                <input class="mhr-input" v-model="form.altPhoneNumber" placeholder="+0987654321" />
              </div>
            </div>
          </div>

          <!-- Employment Details Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Employment Details</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Designation / Role</label>
                <select class="mhr-select" v-model="form.designationId">
                  <option :value="null">Select...</option>
                  <option v-for="d in designations" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Department</label>
                <select class="mhr-select" v-model="form.departmentId">
                  <option :value="null">Select...</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Directorate</label>
                <select class="mhr-select" v-model="form.directorateId">
                  <option :value="null">Select...</option>
                  <option v-for="d in directorates" :key="d.id" :value="d.id">{{ d.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Functional Area</label>
                <select class="mhr-select" v-model="form.functionalAreaId">
                  <option :value="null">Select...</option>
                  <option v-for="f in functionalAreas" :key="f.id" :value="f.id">{{ f.title }}</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Entity</label>
                <select class="mhr-select" v-model="form.entityId">
                  <option :value="null">Select...</option>
                  <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Employee Type</label>
                <select class="mhr-select" v-model="form.employeeType">
                  <option :value="null">Select...</option>
                  <option v-for="e in employeeTypes" :key="e.id" :value="e.id">{{ e.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Contract Type</label>
                <select class="mhr-select" v-model="form.contractTypeId">
                  <option :value="null">Select...</option>
                  <option v-for="c in contractTypes" :key="c.id" :value="c.id">{{ c.title }}</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Salary Basis</label>
                <select class="mhr-select" v-model="form.salaryBasisId">
                  <option :value="null">Select...</option>
                  <option v-for="s in salaryBases" :key="s.id" :value="s.id">{{ s.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Reporting To</label>
                <select class="mhr-select" v-model="form.reportingToId">
                  <option :value="null">Select...</option>
                  <option v-for="r in reportingToOptions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Contract & Dates Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Contract & Dates</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Contract Start Date</label>
                <DatePicker v-model="form.contractStartDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Contract End Date</label>
                <DatePicker v-model="form.contractEndDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Date of Hire</label>
                <DatePicker v-model="form.dateOfHire" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Join Date</label>
                <DatePicker v-model="form.joinDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
          </div>

          <!-- Personal Information Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Personal Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Gender</label>
                <select class="mhr-select" v-model="form.genderId">
                  <option :value="null">Select...</option>
                  <option v-for="g in genders" :key="g.id" :value="g.id">{{ g.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Marital Status</label>
                <select class="mhr-select" v-model="form.maritalStatusId">
                  <option :value="null">Select...</option>
                  <option v-for="m in maritalStatuses" :key="m.id" :value="m.id">{{ m.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Date of Birth</label>
                <DatePicker v-model="form.dateOfBirth" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Town of Birth</label>
                <input class="mhr-input" v-model="form.townOfBirth" placeholder="New York" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Country of Birth</label>
                <select class="mhr-select" v-model="form.countryOfBirth">
                  <option :value="null">Select...</option>
                  <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Nationality</label>
                <select class="mhr-select" v-model="form.nationalityId">
                  <option :value="null">Select...</option>
                  <option v-for="n in nationalities" :key="n.id" :value="n.id">{{ n.nationality }}</option>
                </select>
              </div>
            </div>
            <div class="mhr-field" style="margin-top:12px;">
              <label class="mhr-field__label">Language</label>
              <input class="mhr-input" v-model="form.languageId" placeholder="English" />
            </div>
          </div>

          <!-- Identification Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Identification</h3>
            <div class="mhr-field">
              <label class="mhr-field__label">National ID Number</label>
              <input class="mhr-input" v-model="form.nationalIdNumber" placeholder="123456789" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Passport Number</label>
                <input class="mhr-input" v-model="form.passportNumber" placeholder="P123456789" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Passport Expiry</label>
                <DatePicker v-model="form.passportExpiry" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
            <div class="mhr-field" style="margin-top:12px;">
              <label class="mhr-field__label">Civil ID Expiry</label>
              <DatePicker v-model="form.civilIdExpiry" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div style="position:relative;">
                    <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                    <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                  </div>
                </template>
              </DatePicker>
            </div>
          </div>

          <!-- Sponsorship Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Sponsorship</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Sponsorship ID</label>
                <input class="mhr-input" v-model="form.sponsorshipId" placeholder="SPON-001" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Sponsorship Name</label>
                <input class="mhr-input" v-model="form.sponsorshipName" placeholder="Company Sponsorship" />
              </div>
            </div>
          </div>

          <!-- Flags Section -->
          <div>
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Access Flags</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Manager Flag</label>
                <select class="mhr-select" v-model="form.managerFlag">
                  <option value="N">No</option>
                  <option value="Y">Yes</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Administrator Flag</label>
                <select class="mhr-select" v-model="form.administratorFlag">
                  <option value="N">No</option>
                  <option value="Y">Yes</option>
                </select>
              </div>
            </div>
          </div>

        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showAddModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="addEmployee">Add Employee</button>
        </div>
      </div>
    </div>

    <!-- Edit Employee Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="showEditModal = false">
      <div class="mhr-modal mhr-modal--lg" style="max-height:90vh;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Edit Employee</h2>
          <p class="mhr-modal__sub">Update comprehensive employee information</p>
        </div>
        <div class="mhr-modal__body" style="max-height:calc(90vh - 140px);overflow-y:auto;padding-right:8px;">
          
          <!-- Basic Information Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Basic Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Salutation</label>
                <select class="mhr-select" v-model="editForm.salutationId">
                  <option :value="null">Select...</option>
                  <option v-for="sal in salutations" :key="sal.id" :value="sal.id">{{ sal.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">First Name *</label>
                <input class="mhr-input" v-model="editForm.firstName" placeholder="Jane" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Middle Name</label>
                <input class="mhr-input" v-model="editForm.middleName" placeholder="Marie" />
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Last Name *</label>
                <input class="mhr-input" v-model="editForm.lastName" placeholder="Smith" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Employee Number *</label>
                <input class="mhr-input" v-model="editForm.employeeNumber" placeholder="EMP-00001" />
              </div>
            </div>
            <div class="mhr-field" style="margin-top:12px;">
              <label class="mhr-field__label">Agreement Number</label>
              <input class="mhr-input" v-model="editForm.agreementNumber" placeholder="AGR-2026-001" />
            </div>
          </div>

          <!-- Contact Information Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Contact Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Work Email *</label>
                <input class="mhr-input" type="email" v-model="editForm.workEmail" placeholder="jane.smith@company.com" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Personal Email</label>
                <input class="mhr-input" type="email" v-model="editForm.personalEmail" placeholder="jane@personal.com" />
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Area Code</label>
                <input class="mhr-input" v-model="editForm.phoneAreaCode" placeholder="123" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Phone Number</label>
                <input class="mhr-input" v-model="editForm.phoneNumber" placeholder="+1234567890" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Alt Area Code</label>
                <input class="mhr-input" v-model="editForm.altAreaCode" placeholder="098" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Alt Phone</label>
                <input class="mhr-input" v-model="editForm.altPhoneNumber" placeholder="+0987654321" />
              </div>
            </div>
          </div>

          <!-- Employment Details Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Employment Details</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Designation / Role</label>
                <select class="mhr-select" v-model="editForm.designationId">
                  <option :value="null">Select...</option>
                  <option v-for="d in designations" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Department</label>
                <select class="mhr-select" v-model="editForm.departmentId">
                  <option :value="null">Select...</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Directorate</label>
                <select class="mhr-select" v-model="editForm.directorateId">
                  <option :value="null">Select...</option>
                  <option v-for="d in directorates" :key="d.id" :value="d.id">{{ d.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Functional Area</label>
                <select class="mhr-select" v-model="editForm.functionalAreaId">
                  <option :value="null">Select...</option>
                  <option v-for="f in functionalAreas" :key="f.id" :value="f.id">{{ f.title }}</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Entity</label>
                <select class="mhr-select" v-model="editForm.entityId">
                  <option :value="null">Select...</option>
                  <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Employee Type</label>
                <select class="mhr-select" v-model="editForm.employeeType">
                  <option :value="null">Select...</option>
                  <option v-for="e in employeeTypes" :key="e.id" :value="e.id">{{ e.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Contract Type</label>
                <select class="mhr-select" v-model="editForm.contractTypeId">
                  <option :value="null">Select...</option>
                  <option v-for="c in contractTypes" :key="c.id" :value="c.id">{{ c.title }}</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Salary Basis</label>
                <select class="mhr-select" v-model="editForm.salaryBasisId">
                  <option :value="null">Select...</option>
                  <option v-for="s in salaryBases" :key="s.id" :value="s.id">{{ s.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Reporting To</label>
                <select class="mhr-select" v-model="editForm.reportingToId">
                  <option :value="null">Select...</option>
                  <option v-for="r in reportingToOptions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Contract & Dates Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Contract & Dates</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Contract Start Date</label>
                <DatePicker v-model="editForm.contractStartDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Contract End Date</label>
                <DatePicker v-model="editForm.contractEndDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Date of Hire</label>
                <DatePicker v-model="editForm.dateOfHire" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Join Date</label>
                <DatePicker v-model="editForm.joinDate" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
          </div>

          <!-- Personal Information Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Personal Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Gender</label>
                <select class="mhr-select" v-model="editForm.genderId">
                  <option :value="null">Select...</option>
                  <option v-for="g in genders" :key="g.id" :value="g.id">{{ g.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Marital Status</label>
                <select class="mhr-select" v-model="editForm.maritalStatusId">
                  <option :value="null">Select...</option>
                  <option v-for="m in maritalStatuses" :key="m.id" :value="m.id">{{ m.title }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Date of Birth</label>
                <DatePicker v-model="editForm.dateOfBirth" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Town of Birth</label>
                <input class="mhr-input" v-model="editForm.townOfBirth" placeholder="New York" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Country of Birth</label>
                <select class="mhr-select" v-model="editForm.countryOfBirth">
                  <option :value="null">Select...</option>
                  <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Nationality</label>
                <select class="mhr-select" v-model="editForm.nationalityId">
                  <option :value="null">Select...</option>
                  <option v-for="n in nationalities" :key="n.id" :value="n.id">{{ n.nationality }}</option>
                </select>
              </div>
            </div>
            <div class="mhr-field" style="margin-top:12px;">
              <label class="mhr-field__label">Language</label>
              <input class="mhr-input" v-model="editForm.languageId" placeholder="English" />
            </div>
          </div>

          <!-- Identification Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Identification</h3>
            <div class="mhr-field">
              <label class="mhr-field__label">National ID Number</label>
              <input class="mhr-input" v-model="editForm.nationalIdNumber" placeholder="123456789" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Passport Number</label>
                <input class="mhr-input" v-model="editForm.passportNumber" placeholder="P123456789" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Passport Expiry</label>
                <DatePicker v-model="editForm.passportExpiry" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                  <template #default="{ inputValue, inputEvents }">
                    <div style="position:relative;">
                      <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                      <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
            <div class="mhr-field" style="margin-top:12px;">
              <label class="mhr-field__label">Civil ID Expiry</label>
              <DatePicker v-model="editForm.civilIdExpiry" :masks="{ input: dateFormat }" :popover="{ placement: 'bottom-start' }">
                <template #default="{ inputValue, inputEvents }">
                  <div style="position:relative;">
                    <input class="mhr-input" :value="inputValue" v-on="inputEvents" readonly placeholder="Select date…" style="padding-right:35px;" />
                    <AppIcon name="calendar" :size="14" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--mhr-ink-3);" />
                  </div>
                </template>
              </DatePicker>
            </div>
          </div>

          <!-- Sponsorship Section -->
          <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--mhr-line-2);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Sponsorship</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Sponsorship ID</label>
                <input class="mhr-input" v-model="editForm.sponsorshipId" placeholder="SPON-001" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Sponsorship Name</label>
                <input class="mhr-input" v-model="editForm.sponsorshipName" placeholder="Company Sponsorship" />
              </div>
            </div>
          </div>

          <!-- Flags Section -->
          <div>
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--mhr-ink);">Access Flags</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">Manager Flag</label>
                <select class="mhr-select" v-model="editForm.managerFlag">
                  <option value="N">No</option>
                  <option value="Y">Yes</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">Administrator Flag</label>
                <select class="mhr-select" v-model="editForm.administratorFlag">
                  <option value="N">No</option>
                  <option value="Y">Yes</option>
                </select>
              </div>
            </div>
          </div>

        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showEditModal = false" :disabled="isUpdating">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="updateEmployee"
            :disabled="isUpdating"
            :style="isUpdating ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isUpdating" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              Saving...
            </span>
            <span v-else>Save changes</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal mhr-modal--sm">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Archive employee</h2>
          <p class="mhr-modal__sub">This action will archive the employee record.</p>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);font-size:14px;line-height:1.5;">
            Are you sure you want to archive <strong>{{ employeeToDelete?.name }}</strong>?
            This will remove them from the active employee list.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="confirmDelete">Archive employee</button>
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
            <div>Validating and importing employees...</div>
            <div style="margin-top:4px;font-size:12px;color:var(--mhr-ink-3);">This may take a few moments</div>
          </div>
        </div>

        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Import Employees</h2>
          <p class="mhr-modal__sub">Upload an Excel file to import multiple employees at once</p>
        </div>
        <div class="mhr-modal__body">
          <div style="margin-bottom:20px;padding:14px;background:var(--mhr-surface);border-radius:8px;border:1px solid var(--mhr-line);">
            <div style="display:flex;align-items:start;gap:10px;margin-bottom:8px;">
              <AppIcon name="info" :size="16" style="color:var(--blue-600);margin-top:2px;" />
              <div style="flex:1;">
                <p style="font-weight:500;font-size:13px;color:var(--mhr-ink);margin-bottom:4px;">Before importing:</p>
                <ol style="font-size:13px;color:var(--mhr-ink-2);line-height:1.6;margin:0;padding-left:20px;">
                  <li>Download the template file using the button below</li>
                  <li>Fill in your employee data following the sample format</li>
                  <li>Save and upload the completed file</li>
                </ol>
              </div>
            </div>
          </div>

          <div style="margin-bottom:20px;">
            <button class="mhr-btn mhr-btn--outline" @click="downloadTemplate" style="width:100%;">
              <AppIcon name="download" :size="14" /> Download Template with Sample Data
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
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showImportModal = false" :disabled="isImporting">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="importEmployees" :disabled="!importFile || isImporting">
            <AppIcon v-if="!isImporting" name="upload" :size="14" />
            <span v-if="isImporting">Importing...</span>
            <span v-else>Import Employees</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Import Stats Modal -->
    <div v-if="showStatsModal" class="mhr-modal__scrim" @click.self="showStatsModal = false">
      <div class="mhr-modal mhr-modal--md">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Import Complete</h2>
          <p class="mhr-modal__sub">Summary of the import operation</p>
        </div>
        <div class="mhr-modal__body">
          <!-- Stats Grid -->
          <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;margin-bottom:20px;">
            <div style="background:var(--mhr-surface);border-radius:8px;padding:16px;text-align:center;border:1px solid var(--mhr-line);">
              <div style="font-size:28px;font-weight:700;color:var(--mhr-ink);margin-bottom:4px;">
                {{ importStats?.total || 0 }}
              </div>
              <div style="font-size:12px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;">
                Total
              </div>
            </div>
            <div style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);border-radius:8px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(16, 185, 129, 0.2);">
              <div style="font-size:28px;font-weight:700;color:white;margin-bottom:4px;">
                {{ importStats?.success || 0 }}
              </div>
              <div style="font-size:12px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.5px;">
                Success
              </div>
            </div>
            <div style="background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%);border-radius:8px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(239, 68, 68, 0.2);">
              <div style="font-size:28px;font-weight:700;color:white;margin-bottom:4px;">
                {{ importStats?.failed || 0 }}
              </div>
              <div style="font-size:12px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.5px;">
                Failed
              </div>
            </div>
            <div style="background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%);border-radius:8px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(245, 158, 11, 0.2);">
              <div style="font-size:28px;font-weight:700;color:white;margin-bottom:4px;">
                {{ importStats?.skipped || 0 }}
              </div>
              <div style="font-size:12px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.5px;">
                Skipped
              </div>
            </div>
          </div>

          <!-- Success Message -->
          <div v-if="importStats?.success > 0" style="background:var(--green-50);border:1px solid var(--green-200);border-radius:8px;padding:12px;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <AppIcon name="check" :size="16" style="color:var(--green-600);" />
              <span style="color:var(--green-700);font-weight:500;font-size:14px;">
                {{ importStats.success }} employee(s) imported successfully
              </span>
            </div>
          </div>

          <!-- Error Messages -->
          <div v-if="importErrors.length > 0" style="background:var(--red-50);border:1px solid var(--red-200);border-radius:8px;padding:12px;">
            <div style="display:flex;align-items:start;gap:8px;margin-bottom:8px;">
              <AppIcon name="alert" :size="16" style="color:var(--red-600);margin-top:2px;" />
              <div style="flex:1;">
                <div style="color:var(--red-700);font-weight:500;font-size:14px;margin-bottom:8px;">
                  {{ importErrors.length }} row(s) failed to import:
                </div>
                <div style="max-height:120px;overflow-y:auto;font-size:13px;color:var(--red-600);margin-bottom:8px;">
                  <div v-for="(error, idx) in importErrors" :key="idx" style="margin-bottom:4px;padding:4px 0;">
                    • {{ error }}
                  </div>
                </div>
                <div style="padding:8px;background:rgba(255,255,255,0.6);border-radius:4px;font-size:12px;color:var(--red-700);border-left:3px solid var(--red-600);">
                  <strong>💡 Tip:</strong> Click "Export Failed Rows" below to download an Excel file with the errors. Fix the issues and re-import.
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button v-if="hasFailures" class="mhr-btn mhr-btn--outline" @click="exportFailedRows" style="margin-right:auto;">
            <AppIcon name="download" :size="14" /> Export Failed Rows
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="showStatsModal = false">Done</button>
        </div>
      </div>
    </div>

    <!-- Assign to Event Modal -->
    <div v-if="showAssignEventModal" class="mhr-modal__scrim" @click.self="showAssignEventModal = false">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Assign to Event</h2>
          <p class="mhr-modal__sub">Select an event to assign {{ selectedEmployees.size }} employee(s)</p>
        </div>
        <div class="mhr-modal__body">
          <!-- Event Selection -->
          <div class="mhr-field">
            <label class="mhr-field__label">Event</label>
            <select v-model="assignEventId" class="mhr-select" required>
              <option :value="null">Select an event...</option>
              <option v-for="event in availableEvents" :key="event.id" :value="event.id">
                {{ event.name }}
              </option>
            </select>
          </div>

          <!-- Selected Employees Preview -->
          <div v-if="selectedEmployees.size > 0" style="margin-top:16px;padding:12px;background:var(--mhr-surface-2);border-radius:8px;">
            <div style="font-size:12px;font-weight:600;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
              Selected Employees ({{ selectedEmployees.size }})
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;max-height:120px;overflow-y:auto;">
              <span v-for="empId in Array.from(selectedEmployees)" :key="empId" class="mhr-badge mhr-badge--neutral" style="font-size:12px;">
                {{ all.find(e => e.id === empId)?.name || `Employee #${empId}` }}
              </span>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showAssignEventModal = false; assignEventId = null" :disabled="isAssigning">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="confirmAssignToEvent" :disabled="!assignEventId || isAssigning">
            <AppIcon v-if="isAssigning" name="refresh" :size="14" style="animation: spin 1s linear infinite;" />
            <span v-else>Assign {{ selectedEmployees.size }} Employee{{ selectedEmployees.size > 1 ? 's' : '' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast">
        <AppIcon name="check" /> {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
