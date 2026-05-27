<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  permissions: { type: Array, default: () => [] },
  roles:       { type: Array, default: () => [] },
  hrRole:      { type: String, default: 'admin' },
})

// ── Sections ───────────────────────────────────────────────────────────────
const SECTIONS = [
  { id: 'permissions',      label: 'Permissions',          icon: 'lock',   desc: 'Individual access rights' },
  { id: 'roles',            label: 'Roles',                icon: 'users',  desc: 'Named groups of permissions' },
  { id: 'role-permissions', label: 'Permissions in Role',  icon: 'cog',    desc: 'Assign permissions to a role' },
]

// ── State ──────────────────────────────────────────────────────────────────
const activeSection  = ref('permissions')
const selectedItem   = ref(null)
const isCreating     = ref(false)
const form           = ref({ name: '' })
const isSaving       = ref(false)
const showDeleteModal = ref(false)
const itemToDelete   = ref(null)
const isDeleting     = ref(false)
const toast          = ref(null)
const checkedPermIds = ref([])
const isSyncing      = ref(false)

// ── Computed ───────────────────────────────────────────────────────────────
const currentList = computed(() =>
  activeSection.value === 'permissions' ? props.permissions : props.roles
)

const rightTitle = computed(() => {
  if (activeSection.value === 'role-permissions') {
    return selectedItem.value ? selectedItem.value.name : ''
  }
  if (isCreating.value) return activeSection.value === 'permissions' ? 'New Permission' : 'New Role'
  if (selectedItem.value) return activeSection.value === 'permissions' ? 'Edit Permission' : 'Edit Role'
  return ''
})

const isPermChecked = (id) => checkedPermIds.value.includes(id)

const assignedLabel = computed(() => {
  const total = props.permissions.length
  const count = checkedPermIds.value.length
  return `${count} of ${total} permission${total !== 1 ? 's' : ''} assigned`
})

// ── Section helpers ────────────────────────────────────────────────────────
function selectSection(id) {
  activeSection.value = id
  selectedItem.value  = null
  isCreating.value    = false
  form.value          = { name: '' }
  checkedPermIds.value = []
}

function selectItem(item) {
  isCreating.value = false
  selectedItem.value = item
  if (activeSection.value === 'role-permissions') {
    checkedPermIds.value = [...(item.permission_ids || [])]
  } else {
    form.value = { name: item.name }
  }
}

function startCreate() {
  selectedItem.value   = null
  isCreating.value     = true
  checkedPermIds.value = []
  form.value           = { name: '' }
}

function cancelForm() {
  isCreating.value   = false
  selectedItem.value = null
  form.value         = { name: '' }
}

function togglePerm(permId) {
  const idx = checkedPermIds.value.indexOf(permId)
  if (idx >= 0) checkedPermIds.value.splice(idx, 1)
  else checkedPermIds.value.push(permId)
}

// ── CRUD ───────────────────────────────────────────────────────────────────
function showToast(msg) {
  toast.value = msg
  setTimeout(() => { toast.value = null }, 3000)
}

function submitForm() {
  if (!form.value.name.trim()) return showToast('Name is required')
  isSaving.value = true

  const isPerms = activeSection.value === 'permissions'
  const submittedName = form.value.name

  if (isCreating.value) {
    const routeName = isPerms ? 'hr.permissions.store' : 'hr.roles.store'
    router.post(route(routeName), { name: submittedName }, {
      onSuccess: () => {
        const list = isPerms ? props.permissions : props.roles
        const created = list.find(x => x.name === submittedName)
        selectedItem.value = created || null
        if (created) form.value = { name: created.name }
        isCreating.value = false
        showToast(isPerms ? 'Permission created' : 'Role created')
      },
      onError: (e) => showToast(Object.values(e)[0] || 'Failed to create'),
      onFinish: () => { isSaving.value = false },
    })
  } else {
    const routeName = isPerms ? 'hr.permissions.update' : 'hr.roles.update'
    router.put(route(routeName, selectedItem.value.id), { name: submittedName }, {
      onSuccess: () => {
        const list = isPerms ? props.permissions : props.roles
        const updated = list.find(x => x.id === selectedItem.value?.id)
        if (updated) { selectedItem.value = updated; form.value = { name: updated.name } }
        showToast(isPerms ? 'Permission updated' : 'Role updated')
      },
      onError: (e) => showToast(Object.values(e)[0] || 'Failed to update'),
      onFinish: () => { isSaving.value = false },
    })
  }
}

function confirmDelete(item) {
  itemToDelete.value   = item
  showDeleteModal.value = true
}

function doDelete() {
  if (!itemToDelete.value) return
  isDeleting.value = true
  const isPerms    = activeSection.value === 'permissions'
  const routeName  = isPerms ? 'hr.permissions.destroy' : 'hr.roles.destroy'

  router.delete(route(routeName, itemToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      selectedItem.value    = null
      itemToDelete.value    = null
      isCreating.value      = false
      form.value            = { name: '' }
      showToast('Deleted successfully')
    },
    onError: (e) => showToast(Object.values(e)[0] || 'Failed to delete'),
    onFinish: () => { isDeleting.value = false },
  })
}

function syncPermissions() {
  if (!selectedItem.value) return
  isSyncing.value = true
  router.put(route('hr.roles.permissions', selectedItem.value.id), {
    permission_ids: checkedPermIds.value,
  }, {
    onSuccess: () => {
      const updated = props.roles.find(r => r.id === selectedItem.value?.id)
      if (updated) {
        selectedItem.value   = updated
        checkedPermIds.value = [...(updated.permission_ids || [])]
      }
      showToast('Permissions updated')
    },
    onError: (e) => showToast(Object.values(e)[0] || 'Failed to sync'),
    onFinish: () => { isSyncing.value = false },
  })
}
</script>

<template>
  <div class="rp-page">

    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Roles &amp; Permissions</h1>
        <p class="mhr-page-head__sub">Manage access control — roles, permissions, and their assignments</p>
      </div>
    </div>

    <!-- Three-column layout -->
    <div class="rp-layout">

      <!-- LEFT: Section selector -->
      <div class="rp-sidebar-card">
        <div class="rp-sidebar-hd">
          <span class="rp-sidebar-hd-label">Categories</span>
        </div>
        <nav class="rp-cats">
          <button
            v-for="sec in SECTIONS"
            :key="sec.id"
            class="rp-cat-item"
            :class="{ active: activeSection === sec.id }"
            @click="selectSection(sec.id)"
          >
            <AppIcon :name="sec.icon" :size="15" class="rp-cat-icon" />
            <div class="rp-cat-text">
              <span class="rp-cat-label">{{ sec.label }}</span>
              <span class="rp-cat-desc">{{ sec.desc }}</span>
            </div>
            <span class="rp-cat-badge">
              {{ sec.id === 'permissions' ? permissions.length : roles.length }}
            </span>
          </button>
        </nav>
      </div>

      <!-- MIDDLE: Item list -->
      <div class="rp-list-card">
        <div class="rp-list-hd">
          <div>
            <h3 class="rp-list-title">
              {{ activeSection === 'permissions' ? 'Permissions' : 'Roles' }}
            </h3>
            <p class="rp-list-sub">{{ currentList.length }} item{{ currentList.length !== 1 ? 's' : '' }}</p>
          </div>
          <button
            v-if="activeSection !== 'role-permissions'"
            class="rp-icon-btn"
            title="Create new"
            @click="startCreate"
          >
            <AppIcon name="plus" :size="14" />
          </button>
        </div>

        <div class="rp-list-body">
          <div
            v-for="item in currentList"
            :key="item.id"
            class="rp-item"
            :class="{ active: selectedItem?.id === item.id }"
            @click="selectItem(item)"
          >
            <div class="rp-item-icon-wrap">
              <AppIcon
                :name="activeSection === 'permissions' ? 'lock' : 'users'"
                :size="14"
              />
            </div>
            <div class="rp-item-info">
              <div class="rp-item-name">{{ item.name }}</div>
              <div class="rp-item-meta">
                <template v-if="activeSection === 'role-permissions'">
                  {{ item.permissions_count }} permission{{ item.permissions_count !== 1 ? 's' : '' }}
                </template>
                <template v-else>
                  {{ item.guard_name }} · {{ item.created_at }}
                </template>
              </div>
            </div>
            <AppIcon name="chevron" :size="13" class="rp-item-chevron" />
          </div>

          <div v-if="currentList.length === 0" class="rp-list-empty">
            <AppIcon :name="activeSection === 'permissions' ? 'lock' : 'users'" :size="32" style="color:var(--mhr-ink-4);" />
            <p>No {{ activeSection === 'permissions' ? 'permissions' : 'roles' }} yet</p>
            <button
              v-if="activeSection !== 'role-permissions'"
              class="mhr-btn mhr-btn--outline mhr-btn--sm"
              @click="startCreate"
            >
              <AppIcon name="plus" :size="14" /> Create first
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT: CRUD / detail panel -->
      <div class="rp-panel-card">

        <!-- ── ROLE-PERMISSIONS section ── -->
        <template v-if="activeSection === 'role-permissions'">
          <template v-if="selectedItem">
            <div class="rp-panel-hd">
              <div>
                <h3 class="rp-panel-title">{{ selectedItem.name }}</h3>
                <p class="rp-panel-sub">{{ assignedLabel }}</p>
              </div>
            </div>
            <div class="rp-perm-list">
              <label
                v-for="perm in permissions"
                :key="perm.id"
                class="rp-perm-row"
                :class="{ checked: isPermChecked(perm.id) }"
              >
                <input
                  type="checkbox"
                  class="rp-perm-check"
                  :checked="isPermChecked(perm.id)"
                  @change="togglePerm(perm.id)"
                />
                <div class="rp-perm-info">
                  <span class="rp-perm-name">{{ perm.name }}</span>
                  <span class="rp-perm-guard">{{ perm.guard_name }}</span>
                </div>
                <AppIcon v-if="isPermChecked(perm.id)" name="check" :size="13" class="rp-perm-tick" />
              </label>
              <div v-if="permissions.length === 0" class="rp-list-empty" style="min-height:160px;">
                <AppIcon name="lock" :size="28" style="color:var(--mhr-ink-4);" />
                <p>No permissions defined yet</p>
              </div>
            </div>
            <div class="rp-panel-ft">
              <button class="mhr-btn mhr-btn--primary" @click="syncPermissions" :disabled="isSyncing">
                <AppIcon v-if="isSyncing" name="refresh" :size="14" class="icon-spin" />
                <template v-else><AppIcon name="check" :size="14" /> Save Changes</template>
              </button>
            </div>
          </template>
          <div v-else class="rp-panel-empty">
            <AppIcon name="cog" :size="40" style="color:var(--mhr-ink-4);" />
            <p>Select a role to manage its permissions</p>
          </div>
        </template>

        <!-- ── PERMISSIONS / ROLES section ── -->
        <template v-else>

          <!-- Create / Edit form -->
          <template v-if="isCreating || selectedItem">
            <div class="rp-panel-hd">
              <div>
                <h3 class="rp-panel-title">{{ rightTitle }}</h3>
                <p v-if="selectedItem && !isCreating" class="rp-panel-sub">
                  {{ selectedItem.guard_name }} · Created {{ selectedItem.created_at }}
                </p>
                <p v-else class="rp-panel-sub">
                  Enter a unique name for the {{ activeSection === 'permissions' ? 'permission' : 'role' }}
                </p>
              </div>
              <button v-if="isCreating" class="rp-icon-btn" @click="cancelForm" title="Cancel">
                <AppIcon name="x" :size="14" />
              </button>
            </div>

            <div class="rp-panel-body">
              <div class="mhr-field">
                <label class="mhr-field__label">
                  Name <span style="color:var(--mhr-danger);">*</span>
                </label>
                <input
                  v-model="form.name"
                  class="mhr-input"
                  :placeholder="activeSection === 'permissions' ? 'e.g. edit-employees' : 'e.g. manager'"
                  maxlength="255"
                  @keydown.enter="submitForm"
                />
              </div>

              <div v-if="selectedItem && !isCreating" class="rp-meta-grid">
                <div class="rp-meta-block">
                  <span class="rp-meta-label">ID</span>
                  <span class="rp-meta-val">{{ selectedItem.id }}</span>
                </div>
                <div class="rp-meta-block">
                  <span class="rp-meta-label">Guard</span>
                  <span class="rp-meta-val">{{ selectedItem.guard_name }}</span>
                </div>
                <div v-if="selectedItem.permissions_count != null" class="rp-meta-block">
                  <span class="rp-meta-label">Permissions</span>
                  <span class="rp-meta-val">{{ selectedItem.permissions_count }}</span>
                </div>
                <div class="rp-meta-block">
                  <span class="rp-meta-label">Created</span>
                  <span class="rp-meta-val">{{ selectedItem.created_at }}</span>
                </div>
              </div>
            </div>

            <div class="rp-panel-ft">
              <button
                v-if="selectedItem && !isCreating"
                class="mhr-btn mhr-btn--danger"
                @click="confirmDelete(selectedItem)"
              >
                <AppIcon name="trash" :size="14" /> Delete
              </button>
              <div style="flex:1;" />
              <button class="mhr-btn mhr-btn--ghost" @click="cancelForm">Cancel</button>
              <button class="mhr-btn mhr-btn--primary" @click="submitForm" :disabled="isSaving">
                <AppIcon v-if="isSaving" name="refresh" :size="14" class="icon-spin" />
                <template v-else>
                  <AppIcon name="check" :size="14" />
                  {{ isCreating ? 'Create' : 'Save' }}
                </template>
              </button>
            </div>
          </template>

          <!-- Empty state -->
          <div v-else class="rp-panel-empty">
            <AppIcon :name="activeSection === 'permissions' ? 'lock' : 'users'" :size="40" style="color:var(--mhr-ink-4);" />
            <p>Select a {{ activeSection === 'permissions' ? 'permission' : 'role' }} to edit, or create a new one</p>
            <button class="mhr-btn mhr-btn--outline mhr-btn--sm" @click="startCreate">
              <AppIcon name="plus" :size="14" />
              New {{ activeSection === 'permissions' ? 'Permission' : 'Role' }}
            </button>
          </div>
        </template>

      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal" style="max-width:480px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete {{ activeSection === 'permissions' ? 'Permission' : 'Role' }}</h2>
          <p class="mhr-modal__sub">This action cannot be undone</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-warn-bg, #fff7ed);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--mhr-warn, #b45309);display:flex;gap:10px;align-items:flex-start;">
            <AppIcon name="alert" :size="16" style="margin-top:2px;flex-shrink:0;" />
            <div>
              <strong>Are you sure you want to delete "{{ itemToDelete?.name }}"?</strong>
              <p style="margin-top:4px;opacity:0.85;">
                {{ activeSection === 'permissions'
                  ? 'This will remove the permission from all roles that use it.'
                  : 'This will remove the role from all users assigned to it.' }}
              </p>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false" :disabled="isDeleting">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="doDelete" :disabled="isDeleting">
            <AppIcon v-if="isDeleting" name="refresh" :size="14" class="icon-spin" />
            <template v-else><AppIcon name="trash" :size="14" /> Delete</template>
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
.rp-page {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* ── Three-column layout ── */
.rp-layout {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: 220px 280px 1fr;
  gap: 14px;
  align-items: stretch;
}

/* ── Left sidebar ── */
.rp-sidebar-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.rp-sidebar-hd {
  padding: 12px 14px 6px;
}

.rp-sidebar-hd-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--mhr-ink-4);
}

.rp-cats {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  padding: 6px 8px;
  gap: 2px;
}

.rp-cat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: transparent;
  border: none;
  border-radius: var(--mhr-r);
  color: var(--mhr-ink-2);
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
  text-align: left;
  width: 100%;
}

.rp-cat-item:hover {
  background: var(--mhr-surface-2);
  color: var(--mhr-ink);
}

.rp-cat-item.active {
  background: var(--mhr-accent-soft);
  color: var(--mhr-accent-ink);
}

.rp-cat-icon {
  flex-shrink: 0;
  color: var(--mhr-ink-3);
}

.rp-cat-item.active .rp-cat-icon {
  color: var(--mhr-accent);
}

.rp-cat-text {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.rp-cat-label {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rp-cat-desc {
  font-size: 11px;
  color: var(--mhr-ink-4);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rp-cat-item.active .rp-cat-desc {
  color: var(--mhr-accent);
  opacity: 0.7;
}

.rp-cat-badge {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 600;
  color: var(--mhr-ink-3);
  background: var(--mhr-line-2);
  padding: 2px 7px;
  border-radius: 10px;
  min-width: 22px;
  text-align: center;
}

.rp-cat-item.active .rp-cat-badge {
  background: var(--mhr-accent);
  color: #fff;
}

/* ── Middle list ── */
.rp-list-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.rp-list-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 18px 12px;
  border-bottom: 1px solid var(--mhr-line);
}

.rp-list-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--mhr-ink);
}

.rp-list-sub {
  font-size: 12px;
  color: var(--mhr-ink-3);
  margin-top: 2px;
}

.rp-list-body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

.rp-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--mhr-line-2);
  cursor: pointer;
  transition: background 0.15s;
}

.rp-item:last-child {
  border-bottom: none;
}

.rp-item:hover {
  background: var(--mhr-surface-2);
}

.rp-item.active {
  background: var(--mhr-accent-soft);
}

.rp-item-icon-wrap {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border-radius: var(--mhr-r-sm);
  background: var(--mhr-surface-2);
  border: 1px solid var(--mhr-line);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--mhr-ink-3);
}

.rp-item.active .rp-item-icon-wrap {
  background: var(--mhr-accent-soft);
  border-color: var(--mhr-accent);
  color: var(--mhr-accent);
}

.rp-item-info {
  flex: 1;
  min-width: 0;
}

.rp-item-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--mhr-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rp-item-meta {
  font-size: 11.5px;
  color: var(--mhr-ink-4);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rp-item-chevron {
  flex-shrink: 0;
  color: var(--mhr-ink-4);
}

.rp-list-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 20px;
  color: var(--mhr-ink-3);
  gap: 10px;
}

.rp-list-empty p {
  font-size: 13px;
}

/* ── Right panel ── */
.rp-panel-card {
  background: var(--mhr-surface);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.rp-panel-hd {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 22px 14px;
  border-bottom: 1px solid var(--mhr-line);
}

.rp-panel-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--mhr-ink);
}

.rp-panel-sub {
  font-size: 12px;
  color: var(--mhr-ink-3);
  margin-top: 3px;
}

.rp-panel-body {
  flex: 1;
  overflow-y: auto;
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.rp-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: var(--mhr-line);
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r);
  overflow: hidden;
}

.rp-meta-block {
  background: var(--mhr-surface);
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rp-meta-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--mhr-ink-4);
}

.rp-meta-val {
  font-size: 13px;
  font-weight: 500;
  color: var(--mhr-ink);
  font-family: var(--mhr-font-mono, monospace);
}

.rp-panel-ft {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 22px;
  border-top: 1px solid var(--mhr-line);
}

/* ── Permissions checkbox list ── */
.rp-perm-list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 8px 0;
}

.rp-perm-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  border-bottom: 1px solid var(--mhr-line-2);
  cursor: pointer;
  transition: background 0.12s;
}

.rp-perm-row:last-child {
  border-bottom: none;
}

.rp-perm-row:hover {
  background: var(--mhr-surface-2);
}

.rp-perm-row.checked {
  background: var(--mhr-accent-soft);
}

.rp-perm-check {
  flex-shrink: 0;
  width: 15px;
  height: 15px;
  accent-color: var(--mhr-accent);
  cursor: pointer;
}

.rp-perm-info {
  flex: 1;
  min-width: 0;
}

.rp-perm-name {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--mhr-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rp-perm-guard {
  display: block;
  font-size: 11px;
  color: var(--mhr-ink-4);
  margin-top: 1px;
}

.rp-perm-tick {
  flex-shrink: 0;
  color: var(--mhr-accent);
}

/* ── Empty panel state ── */
.rp-panel-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  min-height: 300px;
  color: var(--mhr-ink-3);
  gap: 12px;
  padding: 40px;
  text-align: center;
}

.rp-panel-empty p {
  font-size: 13.5px;
  line-height: 1.5;
  max-width: 240px;
}

/* ── Icon buttons ── */
.rp-icon-btn {
  width: 30px;
  height: 30px;
  border-radius: var(--mhr-r-sm);
  border: 1px solid var(--mhr-line);
  background: var(--mhr-surface);
  color: var(--mhr-ink-3);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}

.rp-icon-btn:hover {
  background: var(--mhr-surface-2);
  color: var(--mhr-ink);
  border-color: var(--mhr-ink-4);
}

/* ── Animation ── */
.icon-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

.mhr-toast-anim-enter-active,
.mhr-toast-anim-leave-active { transition: all 0.3s ease; }
.mhr-toast-anim-enter-from { opacity: 0; transform: translateY(20px); }
.mhr-toast-anim-leave-to   { opacity: 0; transform: translateY(-10px); }

/* ── Responsive ── */
@media (max-width: 1100px) {
  .rp-layout {
    grid-template-columns: 200px 240px 1fr;
  }
}

@media (max-width: 860px) {
  .rp-layout {
    grid-template-columns: 180px 1fr;
  }
  .rp-panel-card {
    display: none;
  }
}

@media (max-width: 600px) {
  .rp-layout {
    grid-template-columns: 1fr;
  }
}
</style>
