<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: { type: String, default: 'admin' },
  templates: { type: Array, default: () => [] },
})

const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingTemplate = ref(null)
const templateToDelete = ref(null)
const openMenuId = ref(null)

const form = useForm({
  name: '',
  description: '',
  expected_team_size: null,
  roles: [{ role_name: '', suggested_count: 1, is_required: false }],
})

const editForm = useForm({
  id: null,
  name: '',
  description: '',
  expected_team_size: null,
  is_active: true,
  roles: [],
})

function addRole() {
  form.roles.push({ role_name: '', suggested_count: 1, is_required: false })
}

function removeRole(index) {
  form.roles.splice(index, 1)
}

function addEditRole() {
  editForm.roles.push({ role_name: '', suggested_count: 1, is_required: false })
}

function removeEditRole(index) {
  editForm.roles.splice(index, 1)
}

function createTemplate() {
  form.post(route('hr.event-templates.store'), {
    onSuccess: () => {
      showAddModal.value = false
      form.reset()
      form.roles = [{ role_name: '', suggested_count: 1, is_required: false }]
    },
  })
}

function openEdit(template) {
  editingTemplate.value = template
  editForm.id = template.id
  editForm.name = template.name
  editForm.description = template.description || ''
  editForm.expected_team_size = template.expectedTeamSize
  editForm.is_active = template.isActive
  editForm.roles = template.roles.map(r => ({
    role_name: r.roleName,
    suggested_count: r.suggestedCount,
    is_required: r.isRequired,
  }))
  showEditModal.value = true
  openMenuId.value = null
}

function updateTemplate() {
  editForm.put(route('hr.event-templates.update', editForm.id), {
    onSuccess: () => { showEditModal.value = false },
  })
}

function confirmDelete(template) {
  templateToDelete.value = template
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteTemplate() {
  router.delete(route('hr.event-templates.destroy', templateToDelete.value.id), {
    onSuccess: () => { showDeleteModal.value = false },
  })
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}
</script>

<template>
  <div @click="openMenuId = null">
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Event Templates</h1>
        <p class="mhr-page-head__sub">Manage reusable event team templates</p>
      </div>
      <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
        <AppIcon name="plus" /> Add Template
      </button>
    </div>

    <div class="mhr-table-wrap">
      <table class="mhr-table">
        <thead>
          <tr>
            <th>NAME</th>
            <th>ROLES</th>
            <th>EXPECTED SIZE</th>
            <th>STATUS</th>
            <th style="width:60px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!templates.length">
            <td colspan="5" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
              No templates found
            </td>
          </tr>
          <tr v-for="template in templates" :key="template.id">
            <td>
              <div style="font-weight:500;">{{ template.name }}</div>
              <div v-if="template.description" style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ template.description }}</div>
            </td>
            <td>
              <div style="display:flex;gap:4px;flex-wrap:wrap;">
                <span
                  v-for="role in template.roles.slice(0, 3)"
                  :key="role.id"
                  class="mhr-badge mhr-badge--neutral"
                  style="font-size:11px;"
                >{{ role.roleName }} ({{ role.suggestedCount }})</span>
                <span v-if="template.roleCount > 3" class="mhr-badge mhr-badge--neutral" style="font-size:11px;">
                  +{{ template.roleCount - 3 }} more
                </span>
              </div>
            </td>
            <td>{{ template.expectedTeamSize || '—' }}</td>
            <td>
              <span class="mhr-badge" :class="template.isActive ? 'mhr-badge--success' : 'mhr-badge--neutral'">
                {{ template.isActive ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div style="position:relative;">
                <button class="mhr-icon-btn" @click.stop="toggleMenu(template.id)">
                  <AppIcon name="more" :size="13" />
                </button>
                <div
                  v-if="openMenuId === template.id"
                  @click.stop
                  style="position:absolute;right:0;top:100%;margin-top:4px;min-width:140px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;"
                >
                  <button
                    @click="openEdit(template)"
                    style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);"
                  >
                    <AppIcon name="edit" :size="14" />Edit
                  </button>
                  <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                  <button
                    @click="confirmDelete(template)"
                    style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);"
                  >
                    <AppIcon name="trash" :size="14" />Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="showAddModal = false">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Create Template</h2>
              <p class="mhr-modal__sub">Define roles and structure for event teams</p>
            </div>
            <button class="mhr-icon-btn" @click="showAddModal = false">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        
        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;gap:16px;">
            <div class="mhr-field">
              <label class="mhr-field__label">TEMPLATE NAME *</label>
              <input v-model="form.name" class="mhr-input" placeholder="e.g., Standard Conference" />
            </div>
            
            <div class="mhr-field">
              <label class="mhr-field__label">DESCRIPTION</label>
              <textarea v-model="form.description" class="mhr-input" rows="2" placeholder="Brief description of this template..."></textarea>
            </div>
            
            <div class="mhr-field">
              <label class="mhr-field__label">EXPECTED TEAM SIZE</label>
              <input v-model.number="form.expected_team_size" type="number" min="1" class="mhr-input" placeholder="e.g., 50" />
            </div>

            <div style="border-top:1px solid var(--mhr-line-2);padding-top:16px;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <label class="mhr-field__label" style="margin:0;">ROLES *</label>
                <button @click="addRole" class="mhr-btn mhr-btn--outline mhr-btn--sm">
                  <AppIcon name="plus" :size="14" /> Add Role
                </button>
              </div>
              
              <div
                v-for="(role, index) in form.roles"
                :key="index"
                style="padding:12px;border:1px solid var(--mhr-line);border-radius:8px;margin-bottom:12px;background:var(--mhr-surface);"
              >
                <div style="display:grid;grid-template-columns:1fr 100px auto;gap:10px;align-items:start;">
                  <input
                    v-model="role.role_name"
                    class="mhr-input"
                    placeholder="Role name (e.g., Coordinator)"
                  />
                  <input
                    v-model.number="role.suggested_count"
                    type="number"
                    min="1"
                    class="mhr-input"
                    placeholder="Count"
                  />
                  <div style="display:flex;gap:6px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;white-space:nowrap;">
                      <input type="checkbox" v-model="role.is_required" />
                      Required
                    </label>
                    <button
                      v-if="form.roles.length > 1"
                      @click="removeRole(index)"
                      class="mhr-icon-btn"
                      style="color:var(--mhr-danger);"
                    >
                      <AppIcon name="trash" :size="14" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mhr-modal__ft">
          <button @click="showAddModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
          <button @click="createTemplate" class="mhr-btn mhr-btn--primary" :disabled="form.processing">
            {{ form.processing ? 'Creating...' : 'Create Template' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="showEditModal = false">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Template</h2>
              <p class="mhr-modal__sub">{{ editingTemplate?.name }}</p>
            </div>
            <button class="mhr-icon-btn" @click="showEditModal = false">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        
        <div class="mhr-modal__body" style="max-height:70vh;overflow-y:auto;">
          <div style="display:grid;gap:16px;">
            <div class="mhr-field">
              <label class="mhr-field__label">TEMPLATE NAME *</label>
              <input v-model="editForm.name" class="mhr-input" />
            </div>
            
            <div class="mhr-field">
              <label class="mhr-field__label">DESCRIPTION</label>
              <textarea v-model="editForm.description" class="mhr-input" rows="2"></textarea>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="mhr-field">
                <label class="mhr-field__label">EXPECTED TEAM SIZE</label>
                <input v-model.number="editForm.expected_team_size" type="number" min="1" class="mhr-input" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">STATUS</label>
                <select v-model="editForm.is_active" class="mhr-select">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive</option>
                </select>
              </div>
            </div>

            <div style="border-top:1px solid var(--mhr-line-2);padding-top:16px;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <label class="mhr-field__label" style="margin:0;">ROLES *</label>
                <button @click="addEditRole" class="mhr-btn mhr-btn--outline mhr-btn--sm">
                  <AppIcon name="plus" :size="14" /> Add Role
                </button>
              </div>
              
              <div
                v-for="(role, index) in editForm.roles"
                :key="index"
                style="padding:12px;border:1px solid var(--mhr-line);border-radius:8px;margin-bottom:12px;background:var(--mhr-surface);"
              >
                <div style="display:grid;grid-template-columns:1fr 100px auto;gap:10px;align-items:start;">
                  <input v-model="role.role_name" class="mhr-input" />
                  <input v-model.number="role.suggested_count" type="number" min="1" class="mhr-input" />
                  <div style="display:flex;gap:6px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;white-space:nowrap;">
                      <input type="checkbox" v-model="role.is_required" />
                      Required
                    </label>
                    <button
                      v-if="editForm.roles.length > 1"
                      @click="removeEditRole(index)"
                      class="mhr-icon-btn"
                      style="color:var(--mhr-danger);"
                    >
                      <AppIcon name="trash" :size="14" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mhr-modal__ft">
          <button @click="showEditModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
          <button @click="updateTemplate" class="mhr-btn mhr-btn--primary" :disabled="editForm.processing">
            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete Template</h2>
        </div>
        <div class="mhr-modal__body">
          <p>Delete template <strong>{{ templateToDelete?.name }}</strong>?</p>
        </div>
        <div class="mhr-modal__ft">
          <button @click="showDeleteModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
          <button @click="deleteTemplate" class="mhr-btn mhr-btn--danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>
