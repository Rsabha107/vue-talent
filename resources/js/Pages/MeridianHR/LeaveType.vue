<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:           { type: String, default: 'admin' },
  leaveTypes:       { type: Array,  default: () => [] },
  contractTypes:    { type: Array,  default: () => [] },
  genders:          { type: Array,  default: () => [] },
  departments:      { type: Array,  default: () => [] },
  designations:     { type: Array,  default: () => [] },
})

const q = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingLeaveType = ref(null)
const leaveTypeToDelete = ref(null)
const toast = ref(null)
const openMenuId = ref(null)
const isRefreshing = ref(false)
const addTab = ref('general')
const editTab = ref('general')

const form = ref({
  title: '',
  activeFlag: 1,
  accrualFrequency: '',
  numberOfLeaves: null,
  eligible: true,
  eligibleContractTypes: [],
  eligibleGenders: [],
  eligibleDepartments: [],
  eligibleDesignations: [],
})

const editForm = ref({
  id: null,
  title: '',
  activeFlag: 1,
  accrualFrequency: '',
  numberOfLeaves: null,
  eligible: true,
  eligibleContractTypes: [],
  eligibleGenders: [],
  eligibleDepartments: [],
  eligibleDesignations: [],
})

const filtered = computed(() =>
  props.leaveTypes.filter(type =>
    q.value === '' || type.title.toLowerCase().includes(q.value.toLowerCase())
  )
)

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function addLeaveType() {
  router.post(route('hr.leave-types.store'), {
    title:             form.value.title,
    active_flag:       form.value.activeFlag,
    accrual_frequency: form.value.accrualFrequency,
    number_of_leaves:  form.value.numberOfLeaves,
    eligible:          form.value.eligible,
    eligible_contract_types: form.value.eligibleContractTypes,
    eligible_genders:        form.value.eligibleGenders,
    eligible_departments:    form.value.eligibleDepartments,
    eligible_designations:   form.value.eligibleDesignations,
  }, {
    onSuccess: () => {
      showAddModal.value = false
      showToast('Leave type added successfully')
      form.value = {
        title: '',
        activeFlag: 1,
        accrualFrequency: '',
        numberOfLeaves: null,
        eligible: true,
        eligibleContractTypes: [],
        eligibleGenders: [],
        eligibleDepartments: [],
        eligibleDesignations: [],
      }
      addTab.value = 'general'
    },
    onError: () => showToast('Failed to add leave type', true),
  })
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function editLeaveType(type) {
  editingLeaveType.value = type
  editForm.value = {
    id:                type.id,
    title:             type.title || '',
    activeFlag:        type.activeFlag ?? 1,
    accrualFrequency:  type.accrualFrequency
      ? type.accrualFrequency.charAt(0).toUpperCase() + type.accrualFrequency.slice(1).toLowerCase()
      : '',
    numberOfLeaves:    type.numberOfLeaves || null,
    eligible:          type.eligible ?? true,
    eligibleContractTypes: (type.eligibleContractTypes || []).map(Number),
    eligibleGenders:       (type.eligibleGenders || []).map(Number),
    eligibleDepartments:   (type.eligibleDepartments || []).map(Number),
    eligibleDesignations:  (type.eligibleDesignations || []).map(Number),
  }
  editTab.value = 'general'
  showEditModal.value = true
  openMenuId.value = null
}

function updateLeaveType() {
  router.put(route('hr.leave-types.update', editForm.value.id), {
    title:             editForm.value.title,
    active_flag:       editForm.value.activeFlag,
    accrual_frequency: editForm.value.accrualFrequency,
    number_of_leaves:  editForm.value.numberOfLeaves,
    eligible:          editForm.value.eligible,
    eligible_contract_types: editForm.value.eligibleContractTypes,
    eligible_genders:        editForm.value.eligibleGenders,
    eligible_departments:    editForm.value.eligibleDepartments,
    eligible_designations:   editForm.value.eligibleDesignations,
  }, {
    onSuccess: () => {
      showEditModal.value = false
      showToast('Leave type updated successfully')
    },
    onError: () => showToast('Failed to update leave type', true),
  })
}

function confirmDelete(type) {
  leaveTypeToDelete.value = type
  showDeleteModal.value = true
  openMenuId.value = null
}

function deleteLeaveType() {
  router.delete(route('hr.leave-types.destroy', leaveTypeToDelete.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      showToast('Leave type deactivated successfully')
    }
  })
}

function refreshLeaveTypes() {
  isRefreshing.value = true
  router.get(route('hr.leave-types'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}
</script>

<template>
  <div @click="openMenuId = null">
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Leave Types</h1>
        <p class="mhr-page-head__sub">Manage leave type configurations</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshLeaveTypes" />
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Leave Type
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search leave types by name…" v-model="q" />
      </div>
    </div>

    <!-- Leave Types Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>TITLE</th>
              <th>ACCRUAL FREQUENCY</th>
              <th># OF MONTHLY LEAVES</th>
              <th>STATUS</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="5" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No leave types found
              </td>
            </tr>
            <tr v-for="type in filtered" :key="type.id">
              <td>
                <div style="font-weight:500;color:var(--mhr-ink);">{{ type.title }}</div>
              </td>
              <td>
                <span style="color:var(--mhr-ink-2);">{{ type.accrualFrequency || '—' }}</span>
              </td>
              <td>
                <span style="color:var(--mhr-ink-2);">{{ type.numberOfLeaves || '—' }}</span>
              </td>
              <td>
                <span class="mhr-badge" :class="type.activeFlag ? 'mhr-badge--success' : 'mhr-badge--neutral'">
                  {{ type.activeFlag ? 'ACTIVE' : 'INACTIVE' }}
                </span>
              </td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(type.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div v-if="openMenuId === type.id" @click.stop class="mhr-dropdown" style="position:absolute;right:0;top:100%;margin-top:4px;min-width:180px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;">
                    <button @click="editLeaveType(type)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="edit" :size="14" />
                      <span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button @click="confirmDelete(type)" class="mhr-dropdown-item" style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                      <AppIcon name="trash" :size="14" />
                      <span>Deactivate</span>
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

    <!-- Add Leave Type Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="showAddModal = false">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd" style="padding-bottom:0;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Leave Type</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new leave type configuration</p>
            </div>
            <button class="mhr-icon-btn" @click="showAddModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
          <!-- Tabs -->
          <div style="display:flex;gap:0;margin-top:16px;border-bottom:2px solid var(--mhr-line);">
            <button
              v-for="tab in ['general','entitlement','eligibility']"
              :key="tab"
              @click="addTab = tab"
              style="padding:8px 18px;border:none;background:transparent;cursor:pointer;font-size:14px;font-weight:500;text-transform:capitalize;position:relative;bottom:-2px;"
              :style="addTab === tab ? 'color:var(--mhr-brand);border-bottom:2px solid var(--mhr-brand);' : 'color:var(--mhr-ink-2);'"
            >{{ tab }}</button>
          </div>
        </div>

        <div class="mhr-modal__body" style="padding-top:20px;min-height:200px;">
          <!-- General Tab -->
          <template v-if="addTab === 'general'">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
              <div class="mhr-field" style="grid-column:1/-1;">
                <label class="mhr-field__label">LEAVE TITLE *</label>
                <input class="mhr-input" v-model="form.title" placeholder="Annual Leave" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">ACCRUAL FREQUENCY</label>
                <select class="mhr-select" v-model="form.accrualFrequency">
                  <option value="">Select...</option>
                  <option value="Yearly">Yearly</option>
                  <option value="Monthly">Monthly</option>
                  <option value="Quarterly">Quarterly</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label"># OF MONTHLY LEAVES</label>
                <input class="mhr-input" type="number" v-model="form.numberOfLeaves" placeholder="30" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">ACTIV FLAG *</label>
                <select class="mhr-select" v-model="form.activeFlag">
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>
            </div>
          </template>

          <!-- Entitlement Tab -->
          <template v-if="addTab === 'entitlement'">
            <div style="color:var(--mhr-ink-2);font-size:14px;">
              Entitlement settings coming soon...
            </div>
          </template>

          <!-- Eligibility Tab -->
          <template v-if="addTab === 'eligibility'">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="mhr-field">
                <label class="mhr-field__label">CONTRACT TYPES</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="ct in contractTypes" :key="ct.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="ct.id" v-model="form.eligibleContractTypes" style="cursor:pointer;" />
                    <span>{{ ct.title }}</span>
                  </label>
                </div>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">GENDERS</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="g in genders" :key="g.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="g.id" v-model="form.eligibleGenders" style="cursor:pointer;" />
                    <span>{{ g.title }}</span>
                  </label>
                </div>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">DEPARTMENTS</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="d in departments" :key="d.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="d.id" v-model="form.eligibleDepartments" style="cursor:pointer;" />
                    <span>{{ d.name }}</span>
                  </label>
                </div>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">DESIGNATIONS</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="des in designations" :key="des.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="des.id" v-model="form.eligibleDesignations" style="cursor:pointer;" />
                    <span>{{ des.name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showAddModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="addLeaveType">Add Leave Type</button>
        </div>
      </div>
    </div>

    <!-- Edit Leave Type Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="showEditModal = false">
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd" style="padding-bottom:0;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Leave Type</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingLeaveType?.title }}</p>
            </div>
            <button class="mhr-icon-btn" @click="showEditModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
          <!-- Tabs -->
          <div style="display:flex;gap:0;margin-top:16px;border-bottom:2px solid var(--mhr-line);">
            <button
              v-for="tab in ['general','entitlement','eligibility']"
              :key="tab"
              @click="editTab = tab"
              style="padding:8px 18px;border:none;background:transparent;cursor:pointer;font-size:14px;font-weight:500;text-transform:capitalize;position:relative;bottom:-2px;"
              :style="editTab === tab ? 'color:var(--mhr-brand);border-bottom:2px solid var(--mhr-brand);' : 'color:var(--mhr-ink-2);'"
            >{{ tab }}</button>
          </div>
        </div>

        <div class="mhr-modal__body" style="padding-top:20px;min-height:200px;">
          <!-- General Tab -->
          <template v-if="editTab === 'general'">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
              <div class="mhr-field" style="grid-column:1/-1;">
                <label class="mhr-field__label">LEAVE TITLE *</label>
                <input class="mhr-input" v-model="editForm.title" placeholder="Annual Leave" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">ACCRUAL FREQUENCY</label>
                <select class="mhr-select" v-model="editForm.accrualFrequency">
                  <option value="">Select...</option>
                  <option value="Yearly">Yearly</option>
                  <option value="Monthly">Monthly</option>
                  <option value="Quarterly">Quarterly</option>
                </select>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label"># OF MONTHLY LEAVES</label>
                <input class="mhr-input" type="number" v-model="editForm.numberOfLeaves" placeholder="30" />
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">ACTIV FLAG *</label>
                <select class="mhr-select" v-model="editForm.activeFlag">
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>
            </div>
          </template>

          <!-- Entitlement Tab -->
          <template v-if="editTab === 'entitlement'">
            <div style="color:var(--mhr-ink-2);font-size:14px;">
              Entitlement settings coming soon...
            </div>
          </template>

          <!-- Eligibility Tab -->
          <template v-if="editTab === 'eligibility'">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="mhr-field">
                <label class="mhr-field__label">CONTRACT TYPES</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="ct in contractTypes" :key="ct.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="ct.id" v-model="editForm.eligibleContractTypes" style="cursor:pointer;" />
                    <span>{{ ct.title }}</span>
                  </label>
                </div>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">GENDERS</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="g in genders" :key="g.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="g.id" v-model="editForm.eligibleGenders" style="cursor:pointer;" />
                    <span>{{ g.title }}</span>
                  </label>
                </div>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">DEPARTMENTS</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="d in departments" :key="d.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="d.id" v-model="editForm.eligibleDepartments" style="cursor:pointer;" />
                    <span>{{ d.name }}</span>
                  </label>
                </div>
              </div>
              <div class="mhr-field">
                <label class="mhr-field__label">DESIGNATIONS</label>
                <div style="border:1px solid var(--mhr-line);border-radius:6px;padding:8px;max-height:200px;overflow-y:auto;">
                  <label v-for="des in designations" :key="des.id" style="display:flex;align-items:center;gap:8px;padding:6px;cursor:pointer;font-size:13px;" @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                    <input type="checkbox" :value="des.id" v-model="editForm.eligibleDesignations" style="cursor:pointer;" />
                    <span>{{ des.name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showEditModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="updateLeaveType">Save changes</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal mhr-modal--sm">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Deactivate Leave Type</h2>
          <p class="mhr-modal__sub">This action will deactivate the leave type.</p>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);font-size:14px;line-height:1.5;">
            Are you sure you want to deactivate <strong>{{ leaveTypeToDelete?.title }}</strong>?
            This will mark it as inactive.
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteLeaveType">Deactivate</button>
        </div>
      </div>
    </div>
  </div>
</template>
