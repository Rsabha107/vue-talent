<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: { type: String, default: 'admin' },
  hrPage: { type: String, default: 'manager-users' },
  users: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
})

const q = ref('')
const toast = ref(null)
const isRefreshing = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingUser = ref(null)
const userToDelete = ref(null)
const openMenuId = ref(null)
const menuPosition = ref({ top: 0, right: 0 })

const addForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_ids: [],
})

const editForm = useForm({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_ids: [],
})

const filtered = computed(() => {
  if (!q.value) return props.users
  const query = q.value.toLowerCase()
  return props.users.filter(user =>
    user.name?.toLowerCase().includes(query) ||
    user.email?.toLowerCase().includes(query)
  )
})

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function refreshUsers() {
  isRefreshing.value = true
  router.reload({
    preserveScroll: true,
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    openMenuId.value = null
    return
  }
  const rect = event.currentTarget.getBoundingClientRect()
  const estimatedHeight = 100
  const openUp = rect.bottom + 4 + estimatedHeight > window.innerHeight
  menuPosition.value = {
    top:    openUp ? null : rect.bottom + 4,
    bottom: openUp ? window.innerHeight - rect.top + 4 : null,
    right:  window.innerWidth - rect.right,
  }
  openMenuId.value = id
}

function openAddModal() {
  addForm.reset()
  showAddModal.value = true
}

function closeAddModal() {
  showAddModal.value = false
  addForm.reset()
}

function submitAdd() {
  addForm.post(route('hr.manager-users.store'), {
    onSuccess: () => {
      closeAddModal()
      showToast('User created successfully.')
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to create user.', true)
    },
  })
}

function openEditModal(user) {
  editingUser.value = user
  editForm.id = user.id
  editForm.name = user.name
  editForm.email = user.email
  editForm.password = ''
  editForm.password_confirmation = ''
  editForm.role_ids = user.role_ids || []
  showEditModal.value = true
  openMenuId.value = null
}

function closeEditModal() {
  showEditModal.value = false
  editForm.reset()
  editingUser.value = null
}

function submitEdit() {
  editForm.put(route('hr.manager-users.update', editForm.id), {
    onSuccess: () => {
      closeEditModal()
      showToast('User updated successfully.')
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to update user.', true)
    },
  })
}

function confirmDelete(user) {
  userToDelete.value = user
  showDeleteModal.value = true
  openMenuId.value = null
}

function closeDeleteModal() {
  showDeleteModal.value = false
  userToDelete.value = null
}

function deleteUser() {
  if (!userToDelete.value) return
  
  router.delete(route('hr.manager-users.destroy', userToDelete.value.id), {
    onSuccess: () => {
      closeDeleteModal()
      showToast('User deleted successfully.')
    },
    onError: (errors) => {
      showToast(errors.error || 'Failed to delete user.', true)
      closeDeleteModal()
    },
  })
}

function toggleRole(form, roleId) {
  const index = form.role_ids.indexOf(roleId)
  if (index > -1) {
    form.role_ids.splice(index, 1)
  } else {
    form.role_ids.push(roleId)
  }
}
</script>

<template>
  <div @click="openMenuId = null">
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">User Management</h1>
        <p class="mhr-page-head__sub">Manage system users and permissions</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshUsers" />
        <button class="mhr-btn mhr-btn--primary" @click="openAddModal">
          <AppIcon name="plus" /> Add User
        </button>
      </div>
    </div>

    <!-- Search Filter -->
    <div style="display:flex;gap:10px;margin-bottom:14px;align-items:center;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search users by name or email…" v-model="q" />
      </div>
      <div class="user-count-badge">
        <AppIcon name="users" :size="14" />
        <span class="fw-semibold">{{ filtered.length }}</span>
        <span>{{ filtered.length === 1 ? 'user' : 'users' }}</span>
      </div>
    </div>

    <!-- Users Table -->
    <div class="mhr-card">
      <div class="mhr-table-container">
      <!-- <div class="mhr-table-wrap"> -->
        <table class="mhr-table">
          <thead>
            <tr>
              <th>NAME</th>
              <th>EMAIL</th>
              <th>ROLES</th>
              <th>STATUS</th>
              <th>CREATED</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="6" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No users found
              </td>
            </tr>
            <tr v-for="user in filtered" :key="user.id">
              <td>
                <div style="font-weight:500;color:var(--mhr-ink);">{{ user.name }}</div>
              </td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ user.email }}</td>
              <td>
                <span class="mhr-badge mhr-badge--neutral">{{ user.roles || 'No roles' }}</span>
              </td>
              <td>
                <span class="mhr-badge mhr-badge--success">Active</span>
              </td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">
                {{ user.created_at || '—' }}
              </td>
              <td>
                <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(user.id, $event)">
                  <AppIcon name="more" :size="13" />
                </button>
                <Teleport to=".meridian-app" v-if="openMenuId === user.id">
                  <div @click.stop class="mhr-dropdown" :style="{ position:'fixed', top: menuPosition.top != null ? menuPosition.top+'px' : 'auto', bottom: menuPosition.bottom != null ? menuPosition.bottom+'px' : 'auto', right: menuPosition.right+'px', minWidth:'160px', background:'var(--mhr-surface)', border:'1px solid var(--mhr-line)', borderRadius:'8px', boxShadow:'0 4px 12px rgba(0,0,0,0.1)', zIndex:9999 }">
                    <button @click="openEditModal(user)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.target.style.background='var(--mhr-surface)'" @mouseleave="$event.target.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <button @click="confirmDelete(user)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.target.style.background='var(--mhr-surface)'" @mouseleave="$event.target.style.background='transparent'">
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

    <!-- Add User Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal" style="position:relative;">
        <button class="mhr-icon-btn" @click="closeAddModal" style="position:absolute;top:16px;right:16px;z-index:10;">
          <AppIcon name="x" :size="16" />
        </button>
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Add User</h2>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">Name *</label>
            <input class="mhr-input" v-model="addForm.name" placeholder="Full name" />
            <p v-if="addForm.errors.name" class="mhr-field__error">{{ addForm.errors.name }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Email *</label>
            <input class="mhr-input" type="email" v-model="addForm.email" placeholder="user@example.com" />
            <p v-if="addForm.errors.email" class="mhr-field__error">{{ addForm.errors.email }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Password *</label>
            <input class="mhr-input" type="password" v-model="addForm.password" placeholder="••••••••" />
            <p v-if="addForm.errors.password" class="mhr-field__error">{{ addForm.errors.password }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Confirm Password *</label>
            <input class="mhr-input" type="password" v-model="addForm.password_confirmation" placeholder="••••••••" />
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Roles</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
              <label v-for="role in roles" :key="role.id" style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--mhr-line);border-radius:6px;cursor:pointer;user-select:none;font-size:13px;" :style="addForm.role_ids.includes(role.id) ? 'background:var(--mhr-accent);color:white;border-color:var(--mhr-accent);' : ''">
                <input type="checkbox" :checked="addForm.role_ids.includes(role.id)" @change="toggleRole(addForm, role.id)" style="margin:0;" />
                <span>{{ role.name }}</span>
              </label>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeAddModal">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="submitAdd" :disabled="addForm.processing">
            <AppIcon name="check" :size="14" />
            {{ addForm.processing ? 'Creating...' : 'Create User' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal" style="position:relative;">
        <button class="mhr-icon-btn" @click="closeEditModal" style="position:absolute;top:16px;right:16px;z-index:10;">
          <AppIcon name="x" :size="16" />
        </button>
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Edit User</h2>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">Name *</label>
            <input class="mhr-input" v-model="editForm.name" placeholder="Full name" />
            <p v-if="editForm.errors.name" class="mhr-field__error">{{ editForm.errors.name }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Email *</label>
            <input class="mhr-input" type="email" v-model="editForm.email" placeholder="user@example.com" />
            <p v-if="editForm.errors.email" class="mhr-field__error">{{ editForm.errors.email }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">New Password (leave blank to keep current)</label>
            <input class="mhr-input" type="password" v-model="editForm.password" placeholder="••••••••" />
            <p v-if="editForm.errors.password" class="mhr-field__error">{{ editForm.errors.password }}</p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Confirm New Password</label>
            <input class="mhr-input" type="password" v-model="editForm.password_confirmation" placeholder="••••••••" />
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Roles</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
              <label v-for="role in roles" :key="role.id" style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--mhr-line);border-radius:6px;cursor:pointer;user-select:none;font-size:13px;" :style="editForm.role_ids.includes(role.id) ? 'background:var(--mhr-accent);color:white;border-color:var(--mhr-accent);' : ''">
                <input type="checkbox" :checked="editForm.role_ids.includes(role.id)" @change="toggleRole(editForm, role.id)" style="margin:0;" />
                <span>{{ role.name }}</span>
              </label>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeEditModal">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="submitEdit" :disabled="editForm.processing">
            <AppIcon name="check" :size="14" />
            {{ editForm.processing ? 'Updating...' : 'Update User' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="closeDeleteModal">
      <div class="mhr-modal" style="max-width:480px;position:relative;">
        <button class="mhr-icon-btn" @click="closeDeleteModal" style="position:absolute;top:16px;right:16px;z-index:10;">
          <AppIcon name="x" :size="16" />
        </button>
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete User?</h2>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);line-height:1.5;">
            Are you sure you want to delete <strong>{{ userToDelete?.name }}</strong>? This action cannot be undone.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="closeDeleteModal">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteUser">
            <AppIcon name="trash" :size="14" />
            Delete User
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast" class="mhr-toast" :class="toast.isError ? 'mhr-toast--error' : ''">
      <AppIcon :name="toast.isError ? 'alert' : 'check'" :size="16" />
      <span>{{ toast.msg }}</span>
    </div>
  </div>
</template>

<style scoped>
.user-count-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--mhr-ink-2);
  font-size: 13px;
  font-weight: 500;
}

.user-count-badge .fw-semibold {
  font-weight: 600;
  color: var(--mhr-ink);
}
</style>
