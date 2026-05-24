<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  event: { type: Object, required: true },
  assignedEmployees: { type: Array, default: () => [] },
  availableEmployees: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'refresh'])

const showAssignModal  = ref(false)
const showRemoveModal  = ref(false)
const showCopyModal    = ref(false)
const showTemplateModal = ref(false)
const showDropdown     = ref(false)
const removingEmployee = ref(null)
const selectedIds      = ref([])
const assignmentDate   = ref(new Date().toISOString().split('T')[0])
const eventRole        = ref('')
const loading          = ref(false)
const empSearch        = ref('')

// Copy from event state
const sourceEvents     = ref([])
const selectedSourceEvent = ref(null)
const includeRoles     = ref(true)
const loadingEvents    = ref(false)

// Template state
const templates        = ref([])
const selectedTemplate = ref(null)
const templateAssignments = ref({})
const loadingTemplates = ref(false)

// CSV import state
const showImportModal  = ref(false)
const csvFile          = ref(null)
const csvFileName      = ref('')

const filteredAvailable = computed(() => {
  const q = empSearch.value.toLowerCase()
  if (!q) return props.availableEmployees
  return props.availableEmployees.filter(e =>
    e.name?.toLowerCase().includes(q) ||
    e.empNumber?.toLowerCase().includes(q) ||
    e.department?.toLowerCase().includes(q)
  )
})

const allSelected = computed(() =>
  filteredAvailable.value.length > 0 &&
  filteredAvailable.value.every(e => selectedIds.value.includes(e.id))
)

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = selectedIds.value.filter(
      id => !filteredAvailable.value.find(e => e.id === id)
    )
  } else {
    const toAdd = filteredAvailable.value.map(e => e.id)
    selectedIds.value = [...new Set([...selectedIds.value, ...toAdd])]
  }
}

function toggleEmp(id) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter(i => i !== id)
  } else {
    selectedIds.value = [...selectedIds.value, id]
  }
}

function assignEmployees() {
  if (!selectedIds.value.length) return
  loading.value = true
  router.post(
    route('hr.events.assign-employees', props.event.id),
    {
      employee_ids: selectedIds.value,
      assigned_at: assignmentDate.value,
      event_role: eventRole.value || null,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showAssignModal.value = false
        selectedIds.value = []
        eventRole.value = ''
        emit('refresh')
      },
      onFinish: () => { loading.value = false },
    }
  )
}

function openAssignModal() {
  selectedIds.value = []
  eventRole.value = ''
  empSearch.value = ''
  assignmentDate.value = new Date().toISOString().split('T')[0]
  showAssignModal.value = true
  showDropdown.value = false
}

function openCopyModal() {
  showCopyModal.value = true
  showDropdown.value = false
  loadSourceEvents()
}

function openTemplateModal() {
  showTemplateModal.value = true
  showDropdown.value = false
  loadTemplates()
}

function loadSourceEvents() {
  loadingEvents.value = true
  fetch(route('hr.events.source-events', props.event.id))
    .then(res => res.json())
    .then(data => {
      sourceEvents.value = data.events || []
    })
    .finally(() => {
      loadingEvents.value = false
    })
}

function loadTemplates() {
  loadingTemplates.value = true
  fetch(route('hr.event-templates.list'))
    .then(res => res.json())
    .then(data => {
      templates.value = data.templates || []
    })
    .finally(() => {
      loadingTemplates.value = false
    })
}

function selectTemplate(template) {
  selectedTemplate.value = template
  // Initialize assignments object for each role
  template.roles.forEach(role => {
    templateAssignments.value[role.role_name] = []
  })
}

function copyTeam() {
  if (!selectedSourceEvent.value) return
  
  loading.value = true
  router.post(
    route('hr.events.copy-team', props.event.id),
    {
      source_event_id: selectedSourceEvent.value,
      include_roles: includeRoles.value,
      assigned_at: assignmentDate.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showCopyModal.value = false
        selectedSourceEvent.value = null
        emit('refresh')
      },
      onFinish: () => { loading.value = false },
    }
  )
}

function applyTemplate() {
  if (!selectedTemplate.value) return
  
  const assignments = Object.entries(templateAssignments.value)
    .filter(([_, ids]) => ids.length > 0)
    .map(([roleName, employeeIds]) => ({
      role_name: roleName,
      employee_ids: employeeIds,
    }))
  
  if (assignments.length === 0) {
    alert('Please assign at least one employee to a role')
    return
  }
  
  loading.value = true
  router.post(
    route('hr.events.apply-template', props.event.id),
    {
      template_id: selectedTemplate.value.id,
      employee_assignments: assignments,
      assigned_at: assignmentDate.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showTemplateModal.value = false
        selectedTemplate.value = null
        templateAssignments.value = {}
        emit('refresh')
      },
      onFinish: () => { loading.value = false },
    }
  )
}

function confirmRemove(emp) {
  removingEmployee.value = emp
  showRemoveModal.value = true
}

function removeEmployee() {
  router.delete(
    route('hr.events.remove-employee', {
      event: props.event.id,
      employee: removingEmployee.value.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        showRemoveModal.value = false
        removingEmployee.value = null
        emit('refresh')
      },
    }
  )
}

function openImportModal() {
  showImportModal.value = true
  showDropdown.value = false
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    csvFile.value = file
    csvFileName.value = file.name
  }
}

function importCsv() {
  if (!csvFile.value) return
  
  loading.value = true
  const formData = new FormData()
  formData.append('csv_file', csvFile.value)
  formData.append('assigned_at', assignmentDate.value)
  
  router.post(
    route('hr.events.import-csv', props.event.id),
    formData,
    {
      preserveScroll: true,
      onSuccess: () => {
        showImportModal.value = false
        csvFile.value = null
        csvFileName.value = ''
        emit('refresh')
      },
      onFinish: () => { loading.value = false },
    }
  )
}

const AVATAR_COLORS = [
  '#3b6f43','#3a6c8c','#7a5c3b','#6b3b6f','#3b5c6f','#6f3b3b','#3b6f60'
]
function avatarColor(id) {
  return AVATAR_COLORS[id % AVATAR_COLORS.length]
}
function initials(name) {
  return (name || '').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s.length === 10 ? s + 'T00:00:00' : s)
    .toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div class="mhr-card">
    <!-- Card header -->
    <div class="mhr-card__header">
      <div style="display:flex;align-items:center;gap:10px;">
        <h3 style="font-size:15px;font-weight:600;">Event Team</h3>
        <span v-if="assignedEmployees.length" class="mhr-badge mhr-badge--neutral">
          {{ assignedEmployees.length }}
        </span>
      </div>
      
      <!-- Split button dropdown -->
      <div style="position:relative;">
        <div style="display:flex;">
          <button
            @click="openAssignModal"
            class="mhr-btn mhr-btn--primary mhr-btn--sm"
            :disabled="!availableEmployees.length"
            :title="!availableEmployees.length ? 'All employees already assigned' : 'Assign employees to this event'"
            style="border-top-right-radius:0;border-bottom-right-radius:0;"
          >
            <AppIcon name="plus" :size="14" />
            Assign Employees
          </button>
          <button
            @click="showDropdown = !showDropdown"
            class="mhr-btn mhr-btn--primary mhr-btn--sm"
            style="border-top-left-radius:0;border-bottom-left-radius:0;border-left:1px solid rgba(255,255,255,0.2);padding-left:6px;padding-right:6px;"
            :title="'More options'"
          >
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
          </button>
        </div>
        
        <!-- Dropdown menu -->
        <div
          v-if="showDropdown"
          @click.stop
          style="position:absolute;right:0;top:100%;margin-top:4px;min-width:200px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;overflow:hidden;"
        >
          <button
            @click="openCopyModal"
            style="width:100%;display:flex;align-items:center;gap:10px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);"
            @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
            @mouseleave="$event.currentTarget.style.background='transparent'"
          >
            <AppIcon name="copy" :size="14" />
            <span>Copy from Event</span>
          </button>
          <button
            @click="openTemplateModal"
            style="width:100%;display:flex;align-items:center;gap:10px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);"
            @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
            @mouseleave="$event.currentTarget.style.background='transparent'"
          >
            <AppIcon name="doc" :size="14" />
            <span>Apply Template</span>
          </button>
          <button
            @click="openImportModal"
            style="width:100%;display:flex;align-items:center;gap:10px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);"
            @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
            @mouseleave="$event.currentTarget.style.background='transparent'"
          >
            <AppIcon name="upload" :size="14" />
            <span>Import CSV</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Populated table -->
    <div v-if="assignedEmployees.length" class="mhr-table-wrap">
      <table class="mhr-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Event Role</th>
            <th>Assigned</th>
            <th style="width:52px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in assignedEmployees" :key="emp.id">
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div
                  :style="{
                    width:'34px', height:'34px', borderRadius:'50%',
                    background: avatarColor(emp.id),
                    color:'#fff', display:'grid', placeItems:'center',
                    fontSize:'12px', fontWeight:'600', flexShrink:0,
                  }"
                >{{ initials(emp.name) }}</div>
                <div>
                  <div style="font-weight:500;font-size:13px;color:var(--mhr-ink);">{{ emp.name }}</div>
                  <div style="font-size:11px;color:var(--mhr-ink-3);margin-top:1px;">{{ emp.email }}</div>
                </div>
              </div>
            </td>
            <td>
              <span v-if="emp.department" style="font-size:13px;color:var(--mhr-ink-2);">{{ emp.department }}</span>
              <span v-else style="color:var(--mhr-ink-4);">—</span>
            </td>
            <td>
              <span v-if="emp.eventRole" class="mhr-badge mhr-badge--neutral" style="font-size:11px;">
                {{ emp.eventRole }}
              </span>
              <span v-else style="color:var(--mhr-ink-4);">—</span>
            </td>
            <td style="font-size:13px;color:var(--mhr-ink-3);white-space:nowrap;">
              {{ fmtDate(emp.assignedAt) }}
            </td>
            <td>
              <button
                @click="confirmRemove(emp)"
                class="mhr-icon-btn"
                style="width:28px;height:28px;color:var(--mhr-danger);"
                title="Remove from event"
              >
                <AppIcon name="trash" :size="13" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-else class="team-empty">
      <div class="team-empty__icon">
        <AppIcon name="users" :size="28" />
      </div>
      <p class="team-empty__title">No team members yet</p>
      <p class="team-empty__sub">Assign employees to start building the event team.</p>
      <button
        @click="openAssignModal"
        class="mhr-btn mhr-btn--outline"
        style="margin-top:16px;"
        :disabled="!availableEmployees.length"
      >
        <AppIcon name="plus" :size="14" />
        {{ availableEmployees.length ? 'Assign Employees' : 'No Employees Available' }}
      </button>
    </div>
  </div>

  <!-- Assign Modal -->
  <div v-if="showAssignModal" class="mhr-modal__scrim" @click.self="showAssignModal = false">
    <div class="mhr-modal mhr-modal--lg">
      <div class="mhr-modal__hd">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <h2 class="mhr-modal__title">Add Team Members</h2>
            <p class="mhr-modal__sub" style="margin-top:2px;">{{ event.name }}</p>
          </div>
          <button class="mhr-icon-btn" @click="showAssignModal = false" style="margin-top:-4px;">
            <AppIcon name="x" :size="16" />
          </button>
        </div>
      </div>

      <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
        <div style="display:grid;gap:16px;">

          <!-- Employee picker -->
          <div class="mhr-field">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <label class="mhr-field__label" style="margin-bottom:0;">SELECT EMPLOYEES *</label>
              <span v-if="selectedIds.length" style="font-size:12px;color:var(--mhr-accent);font-weight:500;">
                {{ selectedIds.length }} selected
              </span>
            </div>

            <!-- Search -->
            <div style="position:relative;margin-bottom:8px;">
              <AppIcon name="search" :size="13" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
              <input
                v-model="empSearch"
                class="mhr-input"
                style="padding-left:30px;font-size:13px;"
                placeholder="Search by name, ID or department…"
              />
            </div>

            <!-- Checklist -->
            <div class="emp-list">
              <!-- Select all -->
              <label
                v-if="filteredAvailable.length > 1"
                class="emp-row emp-row--all"
                @click.prevent="toggleAll"
              >
                <div class="emp-check" :class="{ 'emp-check--on': allSelected }">
                  <AppIcon v-if="allSelected" name="check" :size="10" />
                </div>
                <span style="font-size:12px;font-weight:600;color:var(--mhr-ink-3);">
                  {{ allSelected ? 'Deselect all' : 'Select all' }} ({{ filteredAvailable.length }})
                </span>
              </label>

              <div v-if="!filteredAvailable.length" style="padding:16px;text-align:center;color:var(--mhr-ink-3);font-size:13px;">
                No employees match your search
              </div>

              <label
                v-for="emp in filteredAvailable"
                :key="emp.id"
                class="emp-row"
                @click.prevent="toggleEmp(emp.id)"
              >
                <div class="emp-check" :class="{ 'emp-check--on': selectedIds.includes(emp.id) }">
                  <AppIcon v-if="selectedIds.includes(emp.id)" name="check" :size="10" />
                </div>
                <div
                  :style="{
                    width:'28px', height:'28px', borderRadius:'50%', flexShrink:0,
                    background: avatarColor(emp.id),
                    color:'#fff', display:'grid', placeItems:'center',
                    fontSize:'11px', fontWeight:'600',
                  }"
                >{{ initials(emp.name) }}</div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:500;color:var(--mhr-ink);">{{ emp.name }}</div>
                  <div style="font-size:11px;color:var(--mhr-ink-3);">{{ emp.empNumber }}<span v-if="emp.department"> · {{ emp.department }}</span></div>
                </div>
              </label>
            </div>
            <p v-if="!availableEmployees.length" style="color:var(--mhr-warn);font-size:12px;margin-top:4px;">
              All employees have already been assigned to this event.
            </p>
          </div>

          <!-- Date + Role row -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="mhr-field">
              <label class="mhr-field__label">ASSIGNMENT DATE *</label>
              <input v-model="assignmentDate" type="date" class="mhr-input" />
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">EVENT ROLE <span style="font-weight:400;color:var(--mhr-ink-3);">(optional)</span></label>
              <input
                v-model="eventRole"
                type="text"
                class="mhr-input"
                placeholder="e.g. Coordinator, Volunteer"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="mhr-modal__ft">
        <button @click="showAssignModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
        <button
          @click="assignEmployees"
          class="mhr-btn mhr-btn--primary"
          :disabled="!selectedIds.length || loading"
          :style="(!selectedIds.length || loading) ? 'opacity:0.6;cursor:not-allowed;' : ''"
        >
          <span v-if="loading" style="display:flex;align-items:center;gap:8px;">
            <svg style="animation:spin 1s linear infinite;width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
            </svg>
            Assigning…
          </span>
          <span v-else style="display:flex;align-items:center;gap:6px;">
            <AppIcon name="check" :size="14" />
            Add {{ selectedIds.length || '' }} Member{{ selectedIds.length !== 1 ? 's' : '' }}
          </span>
        </button>
      </div>
    </div>
  </div>

  <!-- Remove confirmation modal -->
  <div v-if="showRemoveModal" class="mhr-modal__scrim" @click.self="showRemoveModal = false">
    <div class="mhr-modal mhr-modal--sm">
      <div class="mhr-modal__hd">
        <h2 class="mhr-modal__title">Remove Team Member</h2>
        <p class="mhr-modal__sub">This cannot be undone.</p>
      </div>
      <div class="mhr-modal__body">
        <p style="color:var(--mhr-ink-2);font-size:14px;line-height:1.5;">
          Remove <strong>{{ removingEmployee?.name }}</strong> from <strong>{{ event.name }}</strong>?
        </p>
      </div>
      <div class="mhr-modal__ft">
        <button class="mhr-btn mhr-btn--ghost" @click="showRemoveModal = false">Cancel</button>
        <button class="mhr-btn mhr-btn--danger" @click="removeEmployee">Remove</button>
      </div>
    </div>
  </div>

  <!-- Copy from Event Modal -->
  <div v-if="showCopyModal" class="mhr-modal__scrim" @click.self="showCopyModal = false">
    <div class="mhr-modal mhr-modal--md">
      <div class="mhr-modal__hd">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <h2 class="mhr-modal__title">Copy Team from Event</h2>
            <p class="mhr-modal__sub" style="margin-top:2px;">Select a source event to copy employees from</p>
          </div>
          <button class="mhr-icon-btn" @click="showCopyModal = false" style="margin-top:-4px;">
            <AppIcon name="x" :size="16" />
          </button>
        </div>
      </div>
      
      <div class="mhr-modal__body">
        <div v-if="loadingEvents" style="padding:40px;text-align:center;color:var(--mhr-ink-3);">
          <svg style="animation:spin 1s linear infinite;width:24px;height:24px;margin:0 auto 12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
          </svg>
          <p>Loading events...</p>
        </div>
        
        <div v-else style="display:grid;gap:16px;">
          <div class="mhr-field">
            <label class="mhr-field__label">SOURCE EVENT *</label>
            <select v-model="selectedSourceEvent" class="mhr-select">
              <option :value="null">Select an event...</option>
              <option v-for="evt in sourceEvents" :key="evt.id" :value="evt.id">
                {{ evt.name }} ({{ evt.teamCount }} employees) - {{ evt.createdAt }}
              </option>
            </select>
            <small v-if="!sourceEvents.length" style="color:var(--mhr-warn);margin-top:4px;display:block;">
              No other events available to copy from
            </small>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">ASSIGNMENT DATE *</label>
            <input v-model="assignmentDate" type="date" class="mhr-input" />
          </div>

          <div class="mhr-field">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--mhr-ink-2);">
              <input type="checkbox" v-model="includeRoles" style="cursor:pointer;" />
              <span>Include event roles from source</span>
            </label>
          </div>
        </div>
      </div>
      
      <div class="mhr-modal__ft">
        <button @click="showCopyModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
        <button
          @click="copyTeam"
          class="mhr-btn mhr-btn--primary"
          :disabled="!selectedSourceEvent || loading"
          :style="(!selectedSourceEvent || loading) ? 'opacity:0.6;cursor:not-allowed;' : ''"
        >
          <span v-if="loading" style="display:flex;align-items:center;gap:8px;">
            <svg style="animation:spin 1s linear infinite;width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
            </svg>
            Copying...
          </span>
          <span v-else style="display:flex;align-items:center;gap:8px;">
            <AppIcon name="copy" :size="14" />
            Copy Team
          </span>
        </button>
      </div>
    </div>
  </div>

  <!-- Apply Template Modal -->
  <div v-if="showTemplateModal" class="mhr-modal__scrim" @click.self="showTemplateModal = false">
    <div class="mhr-modal mhr-modal--lg">
      <div class="mhr-modal__hd">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <h2 class="mhr-modal__title">Apply Team Template</h2>
            <p class="mhr-modal__sub" style="margin-top:2px;">Select a template and assign employees to roles</p>
          </div>
          <button class="mhr-icon-btn" @click="showTemplateModal = false; selectedTemplate = null" style="margin-top:-4px;">
            <AppIcon name="x" :size="16" />
          </button>
        </div>
      </div>
      
      <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
        <div v-if="loadingTemplates" style="padding:40px;text-align:center;color:var(--mhr-ink-3);">
          <svg style="animation:spin 1s linear infinite;width:24px;height:24px;margin:0 auto 12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
          </svg>
          <p>Loading templates...</p>
        </div>

        <div v-else-if="!selectedTemplate" style="display:grid;gap:12px;">
          <div v-if="!templates.length" style="padding:40px;text-align:center;color:var(--mhr-ink-3);">
            <AppIcon name="doc" :size="32" style="opacity:0.3;margin-bottom:12px;" />
            <p>No templates available</p>
            <p style="font-size:12px;margin-top:4px;">Create templates in Settings → Event Templates</p>
          </div>
          <button
            v-for="template in templates"
            :key="template.id"
            @click="selectTemplate(template)"
            style="padding:14px 16px;border:1px solid var(--mhr-line);border-radius:8px;background:white;cursor:pointer;text-align:left;transition:all 0.15s;"
            @mouseenter="$event.currentTarget.style.borderColor='var(--mhr-accent)'; $event.currentTarget.style.background='var(--mhr-surface)'"
            @mouseleave="$event.currentTarget.style.borderColor='var(--mhr-line)'; $event.currentTarget.style.background='white'"
          >
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
              <strong style="font-size:14px;color:var(--mhr-ink);">{{ template.name }}</strong>
              <span class="mhr-badge mhr-badge--neutral">{{ template.roleCount }} roles</span>
            </div>
            <p v-if="template.description" style="font-size:12px;color:var(--mhr-ink-3);margin-bottom:6px;">{{ template.description }}</p>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <span
                v-for="role in template.roles.slice(0, 5)"
                :key="role.id"
                style="font-size:11px;padding:2px 8px;background:var(--mhr-surface);border-radius:4px;color:var(--mhr-ink-3);"
              >
                {{ role.role_name }} ({{ role.suggested_count }})
              </span>
              <span v-if="template.roles.length > 5" style="font-size:11px;color:var(--mhr-ink-3);">
                +{{ template.roles.length - 5 }} more
              </span>
            </div>
          </button>
        </div>

        <div v-else style="display:grid;gap:16px;">
          <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid var(--mhr-line-2);">
            <div>
              <h3 style="font-size:15px;font-weight:600;color:var(--mhr-ink);">{{ selectedTemplate.name }}</h3>
              <p v-if="selectedTemplate.description" style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ selectedTemplate.description }}</p>
            </div>
            <button @click="selectedTemplate = null" class="mhr-btn mhr-btn--ghost mhr-btn--sm">
              <AppIcon name="chevronLeft" :size="14" />
              Back
            </button>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">ASSIGNMENT DATE *</label>
            <input v-model="assignmentDate" type="date" class="mhr-input" />
          </div>

          <div
            v-for="role in selectedTemplate.roles"
            :key="role.id"
            style="padding:14px;border:1px solid var(--mhr-line);border-radius:8px;background:var(--mhr-surface);"
          >
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
              <strong style="font-size:13px;color:var(--mhr-ink);">{{ role.role_name }}</strong>
              <span class="mhr-badge mhr-badge--neutral" style="font-size:10px;">{{ role.suggested_count }} suggested</span>
              <span v-if="role.is_required" class="mhr-badge mhr-badge--warn" style="font-size:10px;">Required</span>
            </div>
            <select
              v-model="templateAssignments[role.role_name]"
              multiple
              class="mhr-select"
              style="height:120px;font-size:12px;"
            >
              <option v-for="emp in availableEmployees" :key="emp.id" :value="emp.id">
                {{ emp.name }} — {{ emp.empNumber }} ({{ emp.department || 'No dept' }})
              </option>
            </select>
            <small style="color:var(--mhr-ink-3);font-size:11px;margin-top:4px;display:block;">
              Hold Ctrl/Cmd to select multiple · {{ (templateAssignments[role.role_name] || []).length }} selected
            </small>
          </div>
        </div>
      </div>
      
      <div class="mhr-modal__ft">
        <button @click="showTemplateModal = false; selectedTemplate = null" class="mhr-btn mhr-btn--ghost">Cancel</button>
        <button
          v-if="selectedTemplate"
          @click="applyTemplate"
          class="mhr-btn mhr-btn--primary"
          :disabled="loading"
          :style="loading ? 'opacity:0.6;cursor:not-allowed;' : ''"
        >
          <span v-if="loading" style="display:flex;align-items:center;gap:8px;">
            <svg style="animation:spin 1s linear infinite;width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
            </svg>
            Applying...
          </span>
          <span v-else style="display:flex;align-items:center;gap:8px;">
            <AppIcon name="check" :size="14" />
            Apply Template
          </span>
        </button>
      </div>
    </div>
  </div>

  <!-- Import CSV Modal -->
  <div v-if="showImportModal" class="mhr-modal__scrim" @click.self="showImportModal = false">
    <div class="mhr-modal">
      <div class="mhr-modal__hd">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <h2 class="mhr-modal__title">Import from CSV</h2>
            <p class="mhr-modal__sub">Upload a CSV file to assign employees</p>
          </div>
          <button class="mhr-icon-btn" @click="showImportModal = false">
            <AppIcon name="x" :size="16" />
          </button>
        </div>
      </div>
      
      <div class="mhr-modal__body">
        <div style="display:grid;gap:16px;">
          <!-- File upload -->
          <div class="mhr-field">
            <label class="mhr-field__label">CSV FILE *</label>
            <div style="position:relative;">
              <input
                type="file"
                accept=".csv,.txt"
                @change="handleFileSelect"
                style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;"
              />
              <div class="mhr-input" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <AppIcon name="upload" :size="14" />
                <span v-if="csvFileName" style="flex:1;">{{ csvFileName }}</span>
                <span v-else style="flex:1;color:var(--mhr-ink-3);">Choose a CSV file...</span>
              </div>
            </div>
            <small style="color:var(--mhr-ink-3);font-size:12px;margin-top:4px;display:block;">
              CSV format: employee_number, event_role (optional)
            </small>
          </div>

          <!-- Assignment date -->
          <div class="mhr-field">
            <label class="mhr-field__label">ASSIGNMENT DATE *</label>
            <input v-model="assignmentDate" type="date" class="mhr-input" />
          </div>

          <!-- CSV format info -->
          <div style="padding:10px 12px;background:var(--mhr-surface);border-radius:6px;border-left:3px solid var(--mhr-accent);">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
              <AppIcon name="info" :size="13" style="color:var(--mhr-accent);" />
              <strong style="font-size:12px;color:var(--mhr-ink-2);">CSV Format</strong>
            </div>
            <p style="font-size:11px;color:var(--mhr-ink-3);">
              employee_number, event_role (optional)
            </p>
          </div>
        </div>
      </div>
      
      <div class="mhr-modal__ft">
        <button @click="showImportModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
        <button
          @click="importCsv"
          class="mhr-btn mhr-btn--primary"
          :disabled="!csvFile || loading"
          :style="(!csvFile || loading) ? 'opacity:0.6;cursor:not-allowed;' : ''"
        >
          <span v-if="loading" style="display:flex;align-items:center;gap:8px;">
            <svg style="animation:spin 1s linear infinite;width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
            </svg>
            Importing...
          </span>
          <span v-else style="display:flex;align-items:center;gap:8px;">
            <AppIcon name="upload" :size="14" />
            Import CSV
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

/* Give the card header the same padding as .mhr-card__hd */
.mhr-card__header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--mhr-line-2);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Empty state */
.team-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 56px 24px;
  text-align: center;
}
.team-empty__icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--mhr-accent-soft);
  color: var(--mhr-accent);
  display: grid;
  place-items: center;
  margin-bottom: 14px;
}
.team-empty__title {
  font-size: 15px;
  font-weight: 600;
  color: var(--mhr-ink-2);
  margin-bottom: 4px;
}
.team-empty__sub {
  font-size: 13px;
  color: var(--mhr-ink-3);
  max-width: 280px;
}

/* Employee checklist */
.emp-list {
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r);
  max-height: 220px;
  overflow-y: auto;
}
.emp-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  cursor: pointer;
  border-bottom: 1px solid var(--mhr-line-2);
  transition: background 0.1s;
}
.emp-row:last-child {
  border-bottom: none;
}
.emp-row:hover {
  background: var(--mhr-surface-2);
}
.emp-row--all {
  background: var(--mhr-surface-2);
  border-bottom: 1px solid var(--mhr-line);
}
.emp-check {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1.5px solid var(--mhr-line);
  background: var(--mhr-surface);
  flex-shrink: 0;
  display: grid;
  place-items: center;
  transition: border-color 0.15s, background 0.15s;
}
.emp-check--on {
  border-color: var(--mhr-accent);
  background: var(--mhr-accent);
  color: #fff;
}
</style>
