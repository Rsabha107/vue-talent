<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import { router, useForm } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:   { type: String, default: 'admin' },
  settings: { type: Array,  default: () => [] },
})

const q = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingSetting = ref(null)
const settingToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)

const form = useForm({
  key: '',
  value: '',
})

const editForm = useForm({
  key: '',
  value: '',
})

const filtered = computed(() =>
  props.settings.filter(setting =>
    q.value === '' || 
    setting.key.toLowerCase().includes(q.value.toLowerCase()) ||
    setting.value.toLowerCase().includes(q.value.toLowerCase())
  )
)

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function openAddModal() {
  form.reset()
  form.clearErrors()
  showAddModal.value = true
}

function addSetting() {
  form.post(route('hr.settings.store'), {
    onSuccess: () => {
      showAddModal.value = false
      showToast('Setting created successfully')
      form.reset()
    },
    onError: () => {
      const firstError = Object.values(form.errors)[0]
      if (firstError) showToast(firstError, true)
    },
  })
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function editSetting(setting) {
  editingSetting.value = setting
  editForm.key = setting.key
  editForm.value = setting.value
  editForm.clearErrors()
  showEditModal.value = true
  openMenuId.value = null
}

function updateSetting() {
  editForm.put(route('hr.settings.update', editingSetting.value.id), {
    onSuccess: () => {
      showEditModal.value = false
      showToast('Setting updated successfully')
      editForm.reset()
    },
    onError: () => {
      const firstError = Object.values(editForm.errors)[0]
      if (firstError) showToast(firstError, true)
    },
  })
}

function confirmDelete(setting) {
  settingToDelete.value = setting
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteSetting() {
  router.delete(route('hr.settings.destroy', settingToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      showToast('Setting deleted successfully')
      settingToDelete.value = null
    },
    onError: () => showToast('Failed to delete setting', true),
  })
}

function refreshData() {
  isRefreshing.value = true
  router.reload({
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      isRefreshing.value = false
    },
  })
}

function closeAddModal() {
  showAddModal.value = false
  form.reset()
  form.clearErrors()
}

function closeEditModal() {
  showEditModal.value = false
  editForm.reset()
  editForm.clearErrors()
}

function closeDeleteModal() {
  showDeleteModal.value = false
  settingToDelete.value = null
}
</script>

<template>
  <div>
    <!-- Toast notification -->
    <div v-if="toast" 
      class="mhr-toast"
      :class="{ 'mhr-toast--error': toast.isError }">
      {{ toast.msg }}
    </div>

    <!-- Page Header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Application Settings</h1>
        <p class="mhr-page-head__sub">{{ filtered.length }} setting{{ filtered.length !== 1 ? 's' : '' }}</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="ghost" :is-refreshing="isRefreshing" @refresh="refreshData" />
        <button class="mhr-btn mhr-btn--primary" @click="openAddModal">
          <AppIcon name="plus" :size="14" /> Add Setting
        </button>
      </div>
    </div>

    <!-- Search Filter -->
    <div style="display:flex;gap:10px;margin-bottom:16px;">
      <div style="position:relative;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:30px;" placeholder="Search by key or value…" v-model="q" />
      </div>
    </div>

    <!-- Settings Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>KEY</th>
              <th>VALUE</th>
              <th>LAST UPDATED</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="4" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No settings found
              </td>
            </tr>
            <tr v-for="setting in filtered" :key="setting.id">
              <td style="font-family:monospace;font-weight:500;">{{ setting.key }}</td>
              <td style="max-width:400px;word-break:break-word;">{{ setting.value }}</td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">
                {{ new Date(setting.updated_at).toLocaleDateString() }}
              </td>
              <td style="text-align:right;">
                <div style="position:relative;">
                  <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="toggleMenu(setting.id)">
                    <AppIcon name="more" :size="16" />
                  </button>
                  <div v-if="openMenuId === setting.id" 
                    class="mhr-dropdown-menu" 
                    style="position:absolute;right:0;top:100%;margin-top:4px;min-width:140px;z-index:10;">
                    <button @click="editSetting(setting)">
                      <AppIcon name="edit" :size="14" /> Edit
                    </button>
                    <button @click="confirmDelete(setting)" style="color:var(--mhr-danger);">
                      <AppIcon name="trash" :size="14" /> Delete
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Setting Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <h2 class="mhr-modal__title">Add Setting</h2>
            <button type="button" class="mhr-icon-btn" @click.stop="closeAddModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-label">Key <span style="color:var(--mhr-danger);">*</span></label>
            <input class="mhr-input" v-model="form.key" placeholder="e.g. app_name, date_format, timezone" />
            <div v-if="form.errors.key" style="color:var(--mhr-danger);font-size:13px;margin-top:4px;">
              {{ form.errors.key }}
            </div>
            <p v-else style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">
              Use lowercase with underscores (e.g. max_upload_size)
            </p>
          </div>
          <div class="mhr-field">
            <label class="mhr-label">Value <span style="color:var(--mhr-danger);">*</span></label>
            <textarea class="mhr-input" v-model="form.value" rows="4" placeholder="Setting value"></textarea>
            <div v-if="form.errors.value" style="color:var(--mhr-danger);font-size:13px;margin-top:4px;">
              {{ form.errors.value }}
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button type="button" class="mhr-btn mhr-btn--ghost" @click.stop="closeAddModal">Cancel</button>
          <button type="button" class="mhr-btn mhr-btn--primary" @click.stop="addSetting" :disabled="form.processing">
            {{ form.processing ? 'Adding...' : 'Add Setting' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Setting Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <h2 class="mhr-modal__title">Edit Setting</h2>
            <button type="button" class="mhr-icon-btn" @click.stop="closeEditModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-label">Key <span style="color:var(--mhr-danger);">*</span></label>
            <input class="mhr-input" v-model="editForm.key" placeholder="e.g. app_name, date_format, timezone" />
            <div v-if="editForm.errors.key" style="color:var(--mhr-danger);font-size:13px;margin-top:4px;">
              {{ editForm.errors.key }}
            </div>
          </div>
          <div class="mhr-field">
            <label class="mhr-label">Value <span style="color:var(--mhr-danger);">*</span></label>
            <textarea class="mhr-input" v-model="editForm.value" rows="4" placeholder="Setting value"></textarea>
            <div v-if="editForm.errors.value" style="color:var(--mhr-danger);font-size:13px;margin-top:4px;">
              {{ editForm.errors.value }}
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button type="button" class="mhr-btn mhr-btn--ghost" @click.stop="closeEditModal">Cancel</button>
          <button type="button" class="mhr-btn mhr-btn--primary" @click.stop="updateSetting" :disabled="editForm.processing">
            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="closeDeleteModal">
      <div class="mhr-modal" style="max-width:450px;">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <h2 class="mhr-modal__title">Delete Setting</h2>
            <button type="button" class="mhr-icon-btn" @click.stop="closeDeleteModal" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <p style="margin-bottom:12px;">Are you sure you want to delete this setting?</p>
          <div style="padding:12px;background:var(--mhr-surface-2);border-radius:var(--mhr-r);font-family:monospace;">
            <div style="font-weight:500;">{{ settingToDelete?.key }}</div>
            <div style="font-size:13px;color:var(--mhr-ink-3);margin-top:4px;">{{ settingToDelete?.value }}</div>
          </div>
          <p style="margin-top:12px;color:var(--mhr-danger);font-size:13px;">
            This action cannot be undone.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button type="button" class="mhr-btn mhr-btn--ghost" @click.stop="closeDeleteModal">Cancel</button>
          <button type="button" class="mhr-btn mhr-btn--danger" @click.stop="deleteSetting">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mhr-dropdown-menu {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  overflow: hidden;
}

.mhr-dropdown-menu button {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 10px 14px;
  background: transparent;
  border: none;
  font-size: 14px;
  color: var(--mhr-ink);
  cursor: pointer;
  text-align: left;
}

.mhr-dropdown-menu button:hover {
  background: var(--mhr-surface-2);
}
</style>
