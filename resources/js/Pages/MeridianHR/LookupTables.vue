<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  entityType: { type: String, required: true },
  entityConfig: { type: Object, required: true },
  items: { type: Array, default: () => [] },
  dropdownOptions: { type: Object, default: () => ({}) },
})

// Modal state
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const currentItem = ref(null)
const itemToDelete = ref(null)
const toast = ref(null)
const isSaving = ref(false)
const openMenuId = ref(null)
const menuPosition = ref({ top: 0, right: 0 })

// Search state
const searchQuery = ref('')

// Selection state
const selectedItems = ref(new Set())
const allSelected = computed(() => filteredItems.value.length > 0 && filteredItems.value.every(i => selectedItems.value.has(i.id)))
const someSelected = computed(() => filteredItems.value.some(i => selectedItems.value.has(i.id)) && !allSelected.value)

function toggleSelectAll() {
  if (allSelected.value) {
    filteredItems.value.forEach(i => selectedItems.value.delete(i.id))
  } else {
    filteredItems.value.forEach(i => selectedItems.value.add(i.id))
  }
  selectedItems.value = new Set(selectedItems.value)
}

function toggleSelect(id) {
  selectedItems.value.has(id) ? selectedItems.value.delete(id) : selectedItems.value.add(id)
  selectedItems.value = new Set(selectedItems.value)
}

function exportSelected() {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('hr.lookup.export.selected', props.entityType)
  const csrf = document.createElement('input')
  csrf.type = 'hidden'
  csrf.name = '_token'
  csrf.value = document.querySelector('meta[name="csrf-token"]').content
  form.appendChild(csrf)
  selectedItems.value.forEach(id => {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = 'ids[]'
    input.value = id
    form.appendChild(input)
  })
  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)
}

// Form data (dynamic based on entity fields)
const form = ref({})

// Computed
const columns = computed(() => props.entityConfig.columns)
const fields = computed(() => props.entityConfig.fields)

// Filtered items based on search
const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.items
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  
  return props.items.filter(item => {
    // Search across all column values
    return columns.value.some(column => {
      const value = item[column.key]
      if (value === null || value === undefined) return false
      return String(value).toLowerCase().includes(query)
    })
  })
})

// Initialize form
function initForm() {
  const formData = {}
  fields.value.forEach(field => {
    if (field.type === 'select') {
      formData[field.name] = null
    } else if (field.type === 'status' || field.name === 'active_flag') {
      formData[field.name] = 1 // Default to Active
    } else {
      formData[field.name] = ''
    }
  })
  return formData
}

// Methods
function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    openMenuId.value = null
    return
  }
  const rect = event.currentTarget.getBoundingClientRect()
  const estimatedHeight = 120
  const openUp = rect.bottom + 4 + estimatedHeight > window.innerHeight
  menuPosition.value = {
    top: openUp ? null : rect.bottom + 4,
    bottom: openUp ? window.innerHeight - rect.top + 4 : null,
    right: window.innerWidth - rect.right,
  }
  openMenuId.value = id
}

function openCreateModal() {
  isEditing.value = false
  currentItem.value = null
  form.value = initForm()
  showFormModal.value = true
}

function openEditModal(item) {
  isEditing.value = true
  currentItem.value = item
  openMenuId.value = null
  
  // Populate form with item data
  form.value = {}
  fields.value.forEach(field => {
    const fieldName = field.name
    // Check if item has this field
    if (fieldName === 'active_flag') {
      // Map active_flag for editing - check both active_flag and status
      form.value[fieldName] = item.active_flag !== undefined ? item.active_flag : (item.status ?? 1)
    } else if (fieldName.includes('_id')) {
      // For foreign keys, need to extract the ID
      form.value[fieldName] = item[fieldName] || null
    } else {
      form.value[fieldName] = item[fieldName] || ''
    }
  })
  
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  form.value = initForm()
  currentItem.value = null
}

function submitForm() {
  isSaving.value = true
  
  const data = { ...form.value }
  
  if (isEditing.value && currentItem.value) {
    router.put(route(`hr.lookup.update`, { type: props.entityType, id: currentItem.value.id }), data, {
      onSuccess: () => { 
        closeFormModal()
        showToast(`${props.entityConfig.singular} updated successfully`)
      },
      onError: (errors) => showToast(Object.values(errors)[0] || 'Failed to update'),
      onFinish: () => { isSaving.value = false }
    })
  } else {
    router.post(route(`hr.lookup.store`, props.entityType), data, {
      onSuccess: () => { 
        closeFormModal()
        showToast(`${props.entityConfig.singular} created successfully`)
      },
      onError: (errors) => showToast(Object.values(errors)[0] || 'Failed to create'),
      onFinish: () => { isSaving.value = false }
    })
  }
}

function confirmDelete(item) {
  itemToDelete.value = item
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteItem() {
  if (!itemToDelete.value) return
  
  router.delete(route(`hr.lookup.destroy`, { type: props.entityType, id: itemToDelete.value.id }), {
    onSuccess: () => {
      showDeleteModal.value = false
      itemToDelete.value = null
      showToast(`${props.entityConfig.singular} deleted successfully`)
    },
    onError: (errors) => showToast(Object.values(errors)[0] || 'Failed to delete')
  })
}

function showToast(msg) {
  toast.value = msg
  setTimeout(() => toast.value = null, 3000)
}

function getColumnValue(item, column) {
  if (column.key === 'status') {
    return item.status === 1 ? 'Active' : 'Inactive'
  }
  return item[column.key] || '—'
}

function getFieldLabel(field) {
  return field.label.toUpperCase()
}

function isFormValid() {
  // Check if all required fields are filled
  for (const field of fields.value) {
    if (field.required && !form.value[field.name]) {
      return false
    }
  }
  return true
}
</script>

<template>
  <div class="lookup-page" @click="openMenuId = null">
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">{{ entityConfig.title }}</h1>
        <p class="mhr-page-head__sub">{{ entityConfig.description }}</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--ghost" @click="router.visit(route('hr.setup'))">
          <AppIcon name="arrow-left" :size="14" /> Back to Setup
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="openCreateModal">
          <AppIcon name="plus" :size="15" /> New {{ entityConfig.singular }}
        </button>
      </div>
    </div>

    <!-- Search -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input 
          v-model="searchQuery"
          type="text" 
          class="mhr-input" 
          :placeholder="'Search ' + entityConfig.title.toLowerCase() + '...'"
          style="padding-left:32px;"
        />
        <button 
          v-if="searchQuery" 
          @click="searchQuery = ''" 
          class="mhr-icon-btn" 
          style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:24px;height:24px;"
        >
          <AppIcon name="x" :size="12" />
        </button>
      </div>
    </div>

    <!-- Selection Banner -->
    <div v-if="selectedItems.size > 0" style="display:flex;align-items:center;gap:12px;background:var(--mhr-accent-soft);border:1px solid var(--mhr-accent);border-radius:8px;padding:10px 16px;margin-bottom:10px;">
      <span style="font-size:13px;font-weight:600;color:var(--mhr-accent);">{{ selectedItems.size }} selected</span>
      <button class="mhr-btn mhr-btn--primary mhr-btn--sm" @click="exportSelected">
        <AppIcon name="download" :size="13" /> Export Selected
      </button>
      <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="selectedItems = new Set()" style="margin-left:auto;">
        Clear selection
      </button>
    </div>

    <!-- Table Card -->
    <div class="lookup-content-card">
      <div class="lookup-content-hd">
        <div style="display:flex;align-items:center;gap:8px;">
          <AppIcon :name="entityConfig.icon" :size="18" style="color:var(--mhr-ink-2);" />
          <h2 style="font-size:16px;font-weight:600;color:var(--mhr-ink);margin:0;">{{ entityConfig.title }}</h2>
        </div>
        <div style="color:var(--mhr-ink-3);font-size:13px;">
          <span v-if="searchQuery.trim()">{{ filteredItems.length }} of {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}</span>
          <span v-else>{{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}</span>
        </div>
      </div>

      <!-- Table -->
      <div class="lookup-table-wrapper">
        <div v-if="filteredItems.length > 0" class="mhr-table-wrap">
          <table class="mhr-table">
            <thead>
              <tr>
                <th style="width:40px;">
                  <input type="checkbox" :checked="allSelected" :indeterminate="someSelected" @change="toggleSelectAll" class="mhr-checkbox" style="cursor:pointer;" />
                </th>
                <th v-for="column in columns" :key="column.key" :style="column.width ? `width:${column.width}` : ''">
                  {{ column.label }}
                </th>
                <th style="width:60px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.id" :style="selectedItems.has(item.id) ? 'background:var(--mhr-accent-soft);' : ''">
                <td>
                  <input type="checkbox" :checked="selectedItems.has(item.id)" @change="toggleSelect(item.id)" class="mhr-checkbox" style="cursor:pointer;" />
                </td>
                <td v-for="column in columns" :key="column.key" :style="column.key === 'id' ? 'color:var(--mhr-ink-3);font-size:13px;' : column.key !== 'status' && column.key === columns.find(c => c.key !== 'id' && c.key !== 'status')?.key ? 'font-weight:500;color:var(--mhr-ink);' : 'color:var(--mhr-ink-2);'">
                  <span v-if="column.key === 'status'">
                    <span v-if="item.status === 1" class="mhr-badge mhr-badge--success">Active</span>
                    <span v-else class="mhr-badge mhr-badge--neutral">Inactive</span>
                  </span>
                  <span v-else>{{ getColumnValue(item, column) }}</span>
                </td>
                <td>
                  <div style="position:relative;">
                    <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(item.id, $event)">
                      <AppIcon name="more" :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Empty state: No items at all -->
        <div v-else-if="items.length === 0" class="lookup-empty">
          <AppIcon :name="entityConfig.icon" :size="48" style="color:var(--mhr-ink-4);margin-bottom:12px;" />
          <p style="color:var(--mhr-ink-3);margin:0;">No {{ entityConfig.title.toLowerCase() }} yet</p>
          <button class="mhr-btn mhr-btn--outline" @click="openCreateModal" style="margin-top:16px;">
            <AppIcon name="plus" :size="14" /> Create {{ entityConfig.singular }}
          </button>
        </div>
        
        <!-- Empty state: No search results -->
        <div v-else class="lookup-empty">
          <AppIcon name="search" :size="48" style="color:var(--mhr-ink-4);margin-bottom:12px;" />
          <p style="color:var(--mhr-ink-3);margin:0 0 8px 0;font-weight:500;">No results found</p>
          <p style="color:var(--mhr-ink-3);font-size:13px;margin:0;">Try adjusting your search terms</p>
          <button class="mhr-btn mhr-btn--outline" @click="searchQuery = ''" style="margin-top:16px;">
            <AppIcon name="x" :size="14" /> Clear Search
          </button>
        </div>
      </div>
    </div>

    <!-- Dropdown Menu (Fixed Position) -->
    <div v-if="openMenuId" @click="openMenuId = null">
      <div v-for="item in items" :key="'menu-'+item.id" v-show="openMenuId === item.id">
        <div @click.stop class="mhr-dropdown" :style="{ position:'fixed', top: menuPosition.top != null ? menuPosition.top+'px' : 'auto', bottom: menuPosition.bottom != null ? menuPosition.bottom+'px' : 'auto', right: menuPosition.right+'px', minWidth:'180px', background:'var(--mhr-surface)', border:'1px solid var(--mhr-line)', borderRadius:'8px', boxShadow:'0 4px 12px rgba(0,0,0,0.1)', zIndex:9999 }">
          <button @click="openEditModal(item)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <AppIcon name="edit" :size="14" />
            <span>Edit</span>
          </button>
          <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
          <button @click="confirmDelete(item)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
            <AppIcon name="trash" :size="14" />
            <span>Delete</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showFormModal" class="mhr-modal__scrim" @click.self="closeFormModal">
      <div class="mhr-modal mhr-modal--md">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">{{ isEditing ? 'Edit' : 'Add' }} {{ entityConfig.singular }}</h2>
            </div>
            <button class="mhr-icon-btn" @click="closeFormModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div class="lookup-form-grid">
            <div v-for="field in fields" :key="field.name" class="mhr-field">
              <label class="mhr-field__label">{{ getFieldLabel(field) }} {{ field.required ? '*' : '' }}</label>
              
              <!-- Text input -->
              <input 
                v-if="field.type === 'text'" 
                class="mhr-input" 
                v-model="form[field.name]" 
                :placeholder="'Enter ' + field.label.toLowerCase()"
                :maxlength="field.maxlength"
              />
              
              <!-- Select input -->
              <select 
                v-else-if="field.type === 'select'" 
                class="mhr-select" 
                v-model="form[field.name]"
              >
                <option :value="null">Select {{ field.label.toLowerCase() }}...</option>
                <option 
                  v-for="option in dropdownOptions[field.options]" 
                  :key="option.id" 
                  :value="option.id"
                >
                  {{ option.label }}
                </option>
              </select>
              
              <!-- Status toggle (active/inactive) -->
              <select 
                v-else-if="field.type === 'status'" 
                class="mhr-select" 
                v-model.number="form[field.name]"
              >
                <option :value="1">Active</option>
                <option :value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeFormModal" :disabled="isSaving">Cancel</button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="submitForm"
            :disabled="isSaving || !isFormValid()"
            :style="isSaving ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isSaving" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              {{ isEditing ? 'Saving…' : 'Creating…' }}
            </span>
            <span v-else>{{ isEditing ? 'Save Changes' : 'Create ' + entityConfig.singular }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal mhr-modal--sm">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete {{ entityConfig.singular }}?</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="margin:0;color:var(--mhr-ink-2);">
            Are you sure you want to delete this {{ entityConfig.singular.toLowerCase() }}? This action cannot be undone.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteItem">Delete</button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast" class="mhr-toast">{{ toast }}</div>
  </div>
</template>

<style scoped>
/* Lookup Tables Page */
.lookup-page {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Content card */
.lookup-content-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
  flex: 1;
}

.lookup-content-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--mhr-line);
}

/* Table */
.lookup-table-wrapper {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 0;
}

.mhr-table-wrap {
  width: 100%;
  overflow-x: auto;
}

/* Empty state */
.lookup-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--mhr-ink-3);
  gap: 12px;
}

/* Form Grid */
.lookup-form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
}

/* Spinner Animation */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
