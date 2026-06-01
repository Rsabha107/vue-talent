<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import EmployeeSelector from '@/Components/MeridianHR/EmployeeSelector.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  documents: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  events: { type: Array, default: () => null },
  hrRole: { type: String, default: 'employee' },
  currentEmployee: { type: Object, default: () => null },
})

const isAdmin = computed(() => props.hrRole === 'admin')
const isManager = computed(() => props.hrRole === 'manager')

const activeCat = ref(null)
const activeDoc = ref(null)
const showUploadModal = ref(false)
const showDeleteModal = ref(false)
const showCatModal = ref(false)
const isSavingCat = ref(false)
const catForm = ref({ title: '', description: '', icon: 'doc' })
const docToDelete = ref(null)
const isUploading = ref(false)
const toast = ref(null)
const sortAsc = ref(false)
const filterEmployeeId = ref(null)
const filterEventId = ref(null)

const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

const documentsByCategory = computed(() => {
  const grouped = {}
  props.categories.forEach(cat => {
    // Apply active filters to category document counts
    const filteredDocs = props.documents.filter(doc => {
      let matches = doc.category_id === cat.id
      if (filterEmployeeId.value) matches = matches && doc.employee_id === filterEmployeeId.value
      if (filterEventId.value) matches = matches && doc.event_id === filterEventId.value
      return matches
    })
    grouped[cat.id] = {
      ...cat,
      docs: filteredDocs
    }
  })
  return grouped
})

const currentCategoryDocs = computed(() => {
  if (!activeCat.value) return []
  return documentsByCategory.value[activeCat.value]?.docs || []
})

const sortedDocs = computed(() => {
  // Documents are already filtered by documentsByCategory
  const docs = currentCategoryDocs.value
  return [...docs].sort((a, b) => {
    const cmp = a.file_name.localeCompare(b.file_name)
    return sortAsc.value ? cmp : -cmp
  })
})

const currentCategoryName = computed(() => {
  if (!activeCat.value) return ''
  return documentsByCategory.value[activeCat.value]?.title || ''
})

const totalUsedBytes = computed(() => props.documents.reduce((s, d) => s + (d.file_size || 0), 0))
const storagePercent = computed(() => Math.min((totalUsedBytes.value / (100 * 1024 * 1024)) * 100, 100))
const storageMBUsed = computed(() => (totalUsedBytes.value / (1024 * 1024)).toFixed(0))

if (props.categories.length > 0) {
  activeCat.value = props.categories[0].id
  if (props.documents.length > 0) {
    const firstDocInCat = props.documents.find(d => d.category_id === activeCat.value)
    if (firstDocInCat) activeDoc.value = firstDocInCat
  }
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const empId = params.get('employee_id')
  if (empId) {
    filterEmployeeId.value = parseInt(empId)
  }
  
  // Default to selected event from session
  const selectedEvent = usePage().props.selectedEvent
  if (selectedEvent) {
    filterEventId.value = selectedEvent
  }
})

// Watch for global event selector changes and sync local filter
watch(
  () => usePage().props.selectedEvent,
  (newEventId) => {
    filterEventId.value = newEventId
  }
)

// Watch for filter changes and update active document/category
watch(
  [filterEventId, filterEmployeeId],
  () => {
    // Check if current active doc is still in filtered results
    if (activeDoc.value) {
      const stillExists = sortedDocs.value.some(d => d.id === activeDoc.value.id)
      if (!stillExists) {
        // Select first doc in current category if available
        activeDoc.value = sortedDocs.value.length > 0 ? sortedDocs.value[0] : null
      }
    }
    
    // If current category has no docs after filtering, switch to first category with docs
    if (activeCat.value && sortedDocs.value.length === 0) {
      // Find first category with documents (documentsByCategory is already filtered)
      const catWithDocs = props.categories.find(cat => {
        const catDocs = documentsByCategory.value[cat.id]?.docs || []
        return catDocs.length > 0
      })
      
      if (catWithDocs) {
        activeCat.value = catWithDocs.id
        // Set first doc in this category as active
        activeDoc.value = documentsByCategory.value[catWithDocs.id]?.docs[0] || null
      } else {
        activeDoc.value = null
      }
    }
  }
)

function docTitle(doc) {
  return doc.file_name.replace(/\.[^/.]+$/, '').replace(/_/g, ' ')
}

function shortDate(s) {
  if (!s) return ''
  const parts = s.split(' ')
  if (parts.length === 3) return `${parts[1]} ${parts[0]}`
  return s
}

function openUploadModal() {
  uploadForm.value = {
    file: null,
    employee_id: props.hrRole === 'admin' ? null : (props.currentEmployee?.id || null),
    category_id: activeCat.value || props.categories[0]?.id || null,
    event_id: filterEventId.value || usePage().props.selectedEvent || null,
    description: '',
  }
  showUploadModal.value = true
}

function closeUploadModal() {
  showUploadModal.value = false
  uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    if (file.type !== 'application/pdf') {
      showToast('Only PDF files are allowed')
      event.target.value = ''
      return
    }
    if (file.size > 10 * 1024 * 1024) {
      showToast('File size must not exceed 10MB')
      event.target.value = ''
      return
    }
    uploadForm.value.file = file
  }
}

function submitUpload() {
  if (!uploadForm.value.file) return showToast('Please select a file')
  if (!uploadForm.value.category_id) return showToast('Please select a category')
  if (isAdmin.value && !uploadForm.value.employee_id) return showToast('Please select an employee')
  if (!isAdmin.value && !uploadForm.value.employee_id) return showToast('Employee information is missing')

  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.employee_id) formData.append('employee_id', uploadForm.value.employee_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)

  isUploading.value = true
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => { closeUploadModal(); showToast('Document uploaded successfully') },
    onError: (errors) => showToast(Object.values(errors)[0] || 'Failed to upload document'),
    onFinish: () => { isUploading.value = false }
  })
}

function viewDocument(doc) { window.open(route('hr.documents.view', doc.id), '_blank') }
function downloadDocument(doc) { window.location.href = route('hr.documents.download', doc.id) }

function confirmDelete(doc) {
  docToDelete.value = doc
  showDeleteModal.value = true
}

function deleteDocument() {
  if (!docToDelete.value) return
  const deletedId = docToDelete.value.id
  router.delete(route('hr.documents.destroy', deletedId), {
    onSuccess: () => {
      showDeleteModal.value = false
      if (activeDoc.value?.id === deletedId) activeDoc.value = null
      docToDelete.value = null
      showToast('Document deleted successfully')
    },
    onError: (errors) => showToast(Object.values(errors)[0] || 'Failed to delete document')
  })
}

function openCatModal() {
  catForm.value = { title: '', description: '', icon: 'doc' }
  showCatModal.value = true
}

function submitCategory() {
  if (!catForm.value.title.trim()) return showToast('Category name is required')
  isSavingCat.value = true
  router.post(route('hr.document-categories.store'), catForm.value, {
    onSuccess: () => { showCatModal.value = false; showToast('Category created') },
    onError: (errors) => showToast(Object.values(errors)[0] || 'Failed to create category'),
    onFinish: () => { isSavingCat.value = false },
  })
}

function showToast(msg) {
  toast.value = msg
  setTimeout(() => toast.value = null, 3000)
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B'
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB'
  if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB'
  return bytes + ' B'
}
</script>

<template>
  <div class="docs-page">
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Documents</h1>
        <p class="mhr-page-head__sub">Contracts, identity, certificates and policies</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--primary" @click="openUploadModal">
          <AppIcon name="upload" :size="15" /> Upload
        </button>
      </div>
    </div>

    <!-- Three-column layout -->
    <div class="docs-layout">

      <!-- LEFT: Category sidebar -->
      <div class="docs-sidebar-card">
        <div v-if="isAdmin" class="docs-sidebar-hd">
          <span class="docs-sidebar-hd-label">Categories</span>
          <button class="docs-icon-btn" title="New category" @click="openCatModal">
            <AppIcon name="plus" :size="14" />
          </button>
        </div>
        <nav class="docs-cats">
          <button
            v-for="cat in categories"
            :key="cat.id"
            class="docs-cat-item"
            :class="{ active: activeCat === cat.id }"
            @click="activeCat = cat.id; activeDoc = null"
          >
            <AppIcon :name="cat.icon || 'doc'" :size="15" class="docs-cat-icon" />
            <span class="docs-cat-label">{{ cat.title }}</span>
            <span class="docs-cat-badge" :class="{ 'has-docs': (documentsByCategory[cat.id]?.docs.length || 0) > 0 }">{{ documentsByCategory[cat.id]?.docs.length || 0 }}</span>
          </button>
        </nav>

        <div class="docs-storage">
          <span class="docs-storage-heading">STORAGE</span>
          <div class="docs-storage-bar">
            <div class="docs-storage-fill" :style="`width:${storagePercent}%`" />
          </div>
          <span class="docs-storage-used">{{ storageMBUsed }} of 100 MB used</span>
        </div>
      </div>

      <!-- MIDDLE: Document list -->
      <div class="docs-list-card">
        <div class="docs-list-hd">
          <div>
            <h3 class="docs-list-title">{{ currentCategoryName }}</h3>
            <p class="docs-list-sub">{{ sortedDocs.length }}{{ sortedDocs.length !== currentCategoryDocs.length ? ` of ${currentCategoryDocs.length}` : '' }} document{{ currentCategoryDocs.length !== 1 ? 's' : '' }}</p>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="openUploadModal" title="Upload document">
              <AppIcon name="upload" :size="14" />
            </button>
            <button class="docs-sort-btn" @click="sortAsc = !sortAsc">
              <AppIcon name="filter" :size="13" /> Sort
            </button>
          </div>
        </div>

        <div v-if="isAdmin && (employees || events)" class="docs-filter-bar">
          <AppIcon name="filter" :size="14" class="docs-filter-icon" />
          <div v-if="employees" class="docs-filter-selector">
            <EmployeeSelector
              v-model="filterEmployeeId"
              :employees="employees"
              placeholder="All employees"
            />
          </div>
          <div v-if="events" class="docs-filter-selector">
            <select v-model="filterEventId" class="mhr-select">
              <option :value="null">All events</option>
              <option v-for="evt in events" :key="evt.id" :value="evt.id">{{ evt.name }}</option>
            </select>
          </div>
        </div>

        <div class="docs-list-body">
          <div
            v-for="doc in sortedDocs"
            :key="doc.id"
            class="docs-item"
            :class="{ active: activeDoc?.id === doc.id }"
            @click="activeDoc = doc"
          >
            <div class="docs-pdf-badge">PDF</div>
            <div class="docs-item-info">
              <div class="docs-item-name">{{ docTitle(doc) }}</div>
              <div class="docs-item-meta">
                {{ shortDate(doc.uploaded_at) }}
                <span class="docs-item-dot">·</span>
                {{ doc.file_size_human }}
                <template v-if="isAdmin && doc.employee_name">
                  <span class="docs-item-dot">·</span>
                  {{ doc.employee_name }}
                  <template v-if="doc.event_name">
                    <span style="color:var(--mhr-ink-4);"> ({{ doc.event_name }})</span>
                  </template>
                </template>
              </div>
            </div>
            <span class="docs-status-pill">
              <span class="docs-status-dot" />
              Stored
            </span>
          </div>

          <div v-if="currentCategoryDocs.length === 0" class="docs-list-empty">
            <AppIcon name="doc" :size="32" style="color:var(--mhr-ink-4);" />
            <p>No documents in this category</p>
            <button class="mhr-btn mhr-btn--outline mhr-btn--sm" @click="openUploadModal">
              <AppIcon name="upload" :size="14" /> Upload
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT: Preview panel -->
      <div class="docs-preview-card">
        <template v-if="activeDoc">
          <!-- Preview header -->
          <div class="docs-preview-hd">
            <div class="docs-preview-hd-text">
              <h3 class="docs-preview-title">{{ docTitle(activeDoc) }}</h3>
              <p class="docs-preview-subtitle">PDF · {{ activeDoc.file_size_human }} · {{ activeDoc.uploaded_at }}</p>
            </div>
            <div class="docs-preview-actions">
              <button class="docs-icon-btn" @click="downloadDocument(activeDoc)" title="Download">
                <AppIcon name="download" :size="15" />
              </button>
              <button class="docs-icon-btn" @click="viewDocument(activeDoc)" title="View">
                <AppIcon name="eye" :size="15" />
              </button>
              <button v-if="isAdmin" class="docs-icon-btn docs-icon-btn--danger" @click="confirmDelete(activeDoc)" title="Delete">
                <AppIcon name="trash" :size="15" />
              </button>
            </div>
          </div>

          <!-- Paper mockup -->
          <div class="docs-paper-wrap">
            <div class="docs-paper">
              <div class="docs-paper-top">
                <span class="docs-paper-brand">Meridian</span>
                <span class="docs-paper-badge">HR · CONFIDENTIAL</span>
              </div>
              <hr class="docs-paper-rule" />
              <p class="docs-paper-doc-title">{{ docTitle(activeDoc) }}</p>
              <p class="docs-paper-issued">ISSUED {{ activeDoc.uploaded_at.toUpperCase() }}</p>
              <div class="docs-paper-lines">
                <div class="docs-paper-line" style="width:95%" />
                <div class="docs-paper-line" style="width:90%" />
                <div class="docs-paper-line" style="width:85%" />
                <div class="docs-paper-line" style="width:95%" />
                <div class="docs-paper-line" style="width:70%" />
                <div class="docs-paper-line" style="width:88%" />
                <div class="docs-paper-line" style="width:60%" />
              </div>
              <div class="docs-paper-footer">
                <span class="docs-paper-code">{{ activeDoc.category_name.slice(0,3).toUpperCase() }}-{{ String(activeDoc.id).padStart(6, '0') }}</span>
                <span class="docs-paper-page">Page 1 / 1</span>
              </div>
            </div>
          </div>

          <!-- Metadata footer -->
          <div class="docs-preview-meta">
            <div class="docs-meta-col">
              <span class="docs-meta-label">STATUS</span>
              <span class="docs-status-pill">
                <span class="docs-status-dot" />
                Stored
              </span>
            </div>
            <div class="docs-meta-col">
              <span class="docs-meta-label">DOCUMENT ID</span>
              <span class="docs-meta-id">DOC-{{ String(activeDoc.id).padStart(6, '0') }}</span>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="docs-preview-empty">
          <AppIcon name="doc" :size="40" style="color:var(--mhr-ink-4);" />
          <p>Select a document to preview</p>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <div v-if="showUploadModal" class="mhr-modal__scrim" @click.self="closeUploadModal">
      <div class="mhr-modal" style="max-width:600px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Upload Document</h2>
          <p class="mhr-modal__sub">Upload a PDF document (max 10 MB)</p>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">File <span style="color:var(--mhr-danger);">*</span></label>
            <input type="file" accept=".pdf,application/pdf" @change="handleFileSelect" class="mhr-input" style="padding:8px;" />
            <p v-if="uploadForm.file" style="font-size:12px;color:var(--mhr-ink-3);margin-top:6px;">
              Selected: {{ uploadForm.file.name }} ({{ formatFileSize(uploadForm.file.size) }})
            </p>
          </div>

          <div v-if="isAdmin" class="mhr-field">
            <label class="mhr-field__label">Employee <span style="color:var(--mhr-danger);">*</span></label>
            <EmployeeSelector v-model="uploadForm.employee_id" :employees="employees" :required="true" placeholder="Select employee..." />
          </div>

          <div v-else class="mhr-field">
            <label class="mhr-field__label">Employee</label>
            <div class="mhr-input" style="background:var(--mhr-surface);cursor:not-allowed;opacity:0.7;">
              {{ currentEmployee?.name || 'N/A' }}
            </div>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Category <span style="color:var(--mhr-danger);">*</span></label>
            <select v-model="uploadForm.category_id" class="mhr-select">
              <option :value="null">Select category...</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.title }}</option>
            </select>
          </div>

          <div v-if="isAdmin && events" class="mhr-field">
            <label class="mhr-field__label">Event (Optional)</label>
            <select v-model="uploadForm.event_id" class="mhr-select">
              <option :value="null">None</option>
              <option v-for="evt in events" :key="evt.id" :value="evt.id">{{ evt.name }}</option>
            </select>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Description (Optional)</label>
            <textarea v-model="uploadForm.description" class="mhr-input" rows="3" placeholder="Add notes about this document..." style="resize:vertical;" />
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeUploadModal" :disabled="isUploading">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="submitUpload" :disabled="isUploading">
            <AppIcon v-if="isUploading" name="refresh" :size="14" class="icon-spin" />
            <template v-else><AppIcon name="upload" :size="14" /> Upload</template>
          </button>
        </div>
      </div>
    </div>

    <!-- New Category Modal -->
    <div v-if="showCatModal" class="mhr-modal__scrim" @click.self="showCatModal = false">
      <div class="mhr-modal" style="max-width:480px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">New Category</h2>
          <p class="mhr-modal__sub">Add a document category to the sidebar</p>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">Name <span style="color:var(--mhr-danger);">*</span></label>
            <input v-model="catForm.title" class="mhr-input" placeholder="e.g. Contracts & offers" maxlength="100" />
          </div>
          <div class="mhr-field">
            <label class="mhr-field__label">Description (Optional)</label>
            <input v-model="catForm.description" class="mhr-input" placeholder="Brief description of this category" maxlength="500" />
          </div>
          <div class="mhr-field">
            <label class="mhr-field__label">Icon</label>
            <div class="docs-icon-picker">
              <button
                v-for="ico in ['doc','file-signature','id-card','award','wallet','book','briefcase','lock','image','inbox','history']"
                :key="ico"
                type="button"
                class="docs-icon-pick-btn"
                :class="{ active: catForm.icon === ico }"
                :title="ico"
                @click="catForm.icon = ico"
              >
                <AppIcon :name="ico" :size="16" />
              </button>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showCatModal = false" :disabled="isSavingCat">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="submitCategory" :disabled="isSavingCat">
            <AppIcon v-if="isSavingCat" name="refresh" :size="14" class="icon-spin" />
            <template v-else><AppIcon name="plus" :size="14" /> Create</template>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete Document</h2>
          <p class="mhr-modal__sub">This action cannot be undone</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--mhr-warn);display:flex;gap:10px;align-items:flex-start;">
            <AppIcon name="alert" :size="16" style="margin-top:2px;flex-shrink:0;" />
            <div>
              <strong>Are you sure you want to delete this document?</strong>
              <p style="margin-top:6px;opacity:0.9;">{{ docToDelete?.file_name }}</p>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteDocument">
            <AppIcon name="trash" :size="14" /> Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast">
        <AppIcon name="check" :size="14" /> {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* ── Page root ── */
.docs-page {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* ── Layout ── */
.docs-layout {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: 220px 1fr 1fr;
  gap: 14px;
  align-items: stretch;
}

/* ── Left sidebar card ── */
.docs-sidebar-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.docs-sidebar-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px 4px;
}

.docs-sidebar-hd-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--mhr-ink-4);
}

.docs-icon-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 4px 0;
}

.docs-icon-pick-btn {
  width: 36px;
  height: 36px;
  border-radius: var(--mhr-r-sm);
  border: 1.5px solid var(--mhr-line);
  background: var(--mhr-surface);
  color: var(--mhr-ink-3);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}

.docs-icon-pick-btn:hover {
  background: var(--mhr-surface-2);
  color: var(--mhr-ink);
}

.docs-icon-pick-btn.active {
  background: var(--mhr-accent-soft);
  border-color: var(--mhr-accent);
  color: var(--mhr-accent);
}

.docs-cats {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  padding: 8px;
  gap: 1px;
}

.docs-cat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  background: transparent;
  border: none;
  border-radius: var(--mhr-r);
  color: var(--mhr-ink-2);
  font-size: 13.5px;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
  text-align: left;
  width: 100%;
}

.docs-cat-item:hover {
  background: var(--mhr-surface-2);
  color: var(--mhr-ink);
}

.docs-cat-item.active {
  background: var(--mhr-accent-soft);
  color: var(--mhr-accent-ink);
  font-weight: 500;
}

.docs-cat-icon {
  flex-shrink: 0;
  color: var(--mhr-ink-3);
}

.docs-cat-item.active .docs-cat-icon {
  color: var(--mhr-accent);
}

.docs-cat-label {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.docs-cat-badge {
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  background: var(--mhr-line-2);
  padding: 2px 7px;
  border-radius: 10px;
  min-width: 22px;
  text-align: center;
  flex-shrink: 0;
}

.docs-cat-badge.has-docs {
  background: var(--mhr-accent);
  color: #fff;
}

.docs-cat-item.active .docs-cat-badge {
  background: var(--mhr-accent);
  color: #fff;
}

/* Storage section */
.docs-storage {
  border-top: 1px solid var(--mhr-line);
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.docs-storage-heading {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--mhr-ink-4);
  text-transform: uppercase;
}

.docs-storage-bar {
  height: 6px;
  background: var(--mhr-line-2);
  border-radius: 3px;
  overflow: hidden;
}

.docs-storage-fill {
  height: 100%;
  background: var(--mhr-accent);
  border-radius: 3px;
  transition: width 0.4s ease;
}

.docs-storage-used {
  font-size: 11.5px;
  color: var(--mhr-ink-3);
}

/* ── Middle list card ── */
.docs-list-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.docs-list-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--mhr-line);
}

.docs-list-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--mhr-ink);
}

.docs-list-sub {
  font-size: 12px;
  color: var(--mhr-ink-3);
  margin-top: 2px;
}

.docs-sort-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-sm);
  color: var(--mhr-ink-2);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}

.docs-sort-btn:hover {
  background: var(--mhr-surface-2);
  border-color: var(--mhr-ink-4);
}

.docs-list-body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

/* Employee filter bar */
.docs-filter-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--mhr-line);
  background: var(--mhr-surface-2);
}

.docs-filter-icon {
  color: var(--mhr-ink-4);
  flex-shrink: 0;
}

.docs-filter-selector {
  flex: 1;
  min-width: 0;
}

/* Document item */
.docs-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  border-bottom: 1px solid var(--mhr-line-2);
  cursor: pointer;
  transition: background 0.15s;
}

.docs-item:last-child {
  border-bottom: none;
}

.docs-item:hover {
  background: var(--mhr-surface-2);
}

.docs-item.active {
  background: var(--mhr-accent-soft);
}

.docs-pdf-badge {
  flex-shrink: 0;
  width: 40px;
  height: 44px;
  background: #f6bfb2;
  border-radius: var(--mhr-r-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 800;
  color: #a84230;
  letter-spacing: 0.04em;
}

.docs-item-info {
  flex: 1;
  min-width: 0;
}

.docs-item-name {
  font-size: 13.5px;
  font-weight: 500;
  color: var(--mhr-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.docs-item-meta {
  font-size: 12px;
  color: var(--mhr-ink-3);
  margin-top: 3px;
}

.docs-item-dot {
  margin: 0 4px;
  color: var(--mhr-ink-4);
}

/* Status pill */
.docs-status-pill {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  background: var(--mhr-accent-soft);
  color: var(--mhr-accent-ink);
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 500;
}

.docs-status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--mhr-accent);
  flex-shrink: 0;
}

/* Empty state */
.docs-list-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--mhr-ink-3);
  gap: 12px;
}

.docs-list-empty p {
  font-size: 14px;
}

/* ── Right preview card ── */
.docs-preview-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.docs-preview-hd {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--mhr-line);
}

.docs-preview-hd-text {
  flex: 1;
  min-width: 0;
}

.docs-preview-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--mhr-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.docs-preview-subtitle {
  font-size: 12px;
  color: var(--mhr-ink-3);
  margin-top: 3px;
}

.docs-preview-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.docs-icon-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--mhr-r-sm);
  border: 1px solid var(--mhr-line);
  background: var(--mhr-surface);
  color: var(--mhr-ink-3);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}

.docs-icon-btn:hover {
  background: var(--mhr-surface-2);
  color: var(--mhr-ink);
  border-color: var(--mhr-ink-4);
}

.docs-icon-btn--danger {
  background: var(--mhr-danger-bg);
  color: var(--mhr-danger);
  border-color: var(--mhr-danger);
}

.docs-icon-btn--danger:hover {
  background: var(--mhr-danger);
  color: #fff;
  border-color: var(--mhr-danger);
}

/* Paper preview area */
.docs-paper-wrap {
  flex: 1;
  min-height: 0;
  background: var(--mhr-surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28px 24px;
  overflow-y: auto;
}

.docs-paper {
  background: #fff;
  border: 1px solid var(--mhr-line);
  border-radius: 4px;
  box-shadow: 0 2px 12px rgba(20,41,26,0.09), 0 1px 3px rgba(20,41,26,0.05);
  padding: 22px 20px 18px;
  width: 100%;
  max-width: 290px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.docs-paper-top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}

.docs-paper-brand {
  font-size: 14px;
  font-weight: 700;
  color: var(--mhr-ink);
  font-family: var(--mhr-font-display);
}

.docs-paper-badge {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.06em;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
}

.docs-paper-rule {
  border: none;
  border-top: 1.5px solid var(--mhr-ink);
  margin: 0;
}

.docs-paper-doc-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--mhr-ink);
  line-height: 1.3;
}

.docs-paper-issued {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.06em;
  color: var(--mhr-ink-3);
  text-transform: uppercase;
}

.docs-paper-lines {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 4px;
}

.docs-paper-line {
  height: 7px;
  background: var(--mhr-line-2);
  border-radius: 3px;
}

.docs-paper-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--mhr-line-2);
}

.docs-paper-code {
  font-size: 9px;
  color: var(--mhr-ink-4);
  font-family: var(--mhr-font-mono);
}

.docs-paper-page {
  font-size: 9px;
  color: var(--mhr-ink-4);
}

/* Metadata footer */
.docs-preview-meta {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border-top: 1px solid var(--mhr-line);
}

.docs-meta-col {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 16px 20px;
}

.docs-meta-col:first-child {
  border-right: 1px solid var(--mhr-line);
}

.docs-meta-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--mhr-ink-4);
  text-transform: uppercase;
}

.docs-meta-id {
  font-size: 13px;
  font-weight: 600;
  color: var(--mhr-ink);
  font-family: var(--mhr-font-mono);
}

/* Empty preview */
.docs-preview-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  color: var(--mhr-ink-3);
  gap: 12px;
}

.docs-preview-empty p {
  font-size: 14px;
}

/* ── Animations ── */
.icon-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.mhr-toast-anim-enter-active,
.mhr-toast-anim-leave-active { transition: all 0.3s ease; }
.mhr-toast-anim-enter-from { opacity: 0; transform: translateY(20px); }
.mhr-toast-anim-leave-to { opacity: 0; transform: translateY(-10px); }

/* ── Responsive ── */
@media (max-width: 1024px) {
  .docs-layout {
    grid-template-columns: 200px 1fr;
  }
  .docs-preview-card {
    display: none;
  }
}

@media (max-width: 768px) {
  .docs-layout {
    grid-template-columns: 1fr;
  }
}
</style>
