<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:   { type: String, default: 'admin' },
  venues:   { type: Array,  default: () => [] },
  statuses: { type: Array,  default: () => [] },
})

const q             = ref('')
const showAddModal  = ref(false)
const showEditModal = ref(false)
const showDelModal  = ref(false)
const editingVenue  = ref(null)
const venueToDelete = ref(null)
const toast         = ref(null)
const openMenuId    = ref(null)
const isRefreshing  = ref(false)

const form = useForm({ title: '', active_flag: null })
const editForm = useForm({ id: null, title: '', active_flag: null })

const filtered = computed(() => {
  if (!q.value) return props.venues
  const query = q.value.toLowerCase()
  return props.venues.filter(v =>
    v.title?.toLowerCase().includes(query) ||
    v.statusName?.toLowerCase().includes(query)
  )
})

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s + 'T00:00:00').toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function refresh() {
  isRefreshing.value = true
  router.get(route('venues.index'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => setTimeout(() => { isRefreshing.value = false }, 500),
  })
}

function addVenue() {
  form.post(route('venues.store'), {
    onSuccess: () => { showAddModal.value = false; showToast('Venue created successfully'); form.reset() },
    onError:   () => showToast('Failed to create venue', true),
  })
}

function openEdit(venue) {
  editingVenue.value = venue
  editForm.id          = venue.id
  editForm.title       = venue.title
  editForm.active_flag = venue.activeFlag
  showEditModal.value  = true
  openMenuId.value     = null
}

function updateVenue() {
  editForm.put(route('venues.update', editForm.id), {
    onSuccess: () => { showEditModal.value = false; showToast('Venue updated successfully') },
    onError:   () => showToast('Failed to update venue', true),
  })
}

function confirmDelete(venue) {
  venueToDelete.value = venue
  showDelModal.value  = true
  openMenuId.value    = null
}

function deleteVenue() {
  router.delete(route('venues.destroy', venueToDelete.value.id), {
    onSuccess: () => { showDelModal.value = false; showToast('Venue deleted successfully') },
  })
}
</script>

<template>
  <div @click="openMenuId = null">

    <!-- Page Header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Venues</h1>
        <p class="mhr-page-head__sub">Manage venue information</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <button class="mhr-btn mhr-btn--outline" @click="refresh" :disabled="isRefreshing">
          <AppIcon name="refresh" :size="14" :style="{ transition: 'transform 0.5s', transform: isRefreshing ? 'rotate(360deg)' : 'rotate(0deg)' }" />
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Venue
        </button>
      </div>
    </div>

    <!-- Search -->
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="position:relative;flex:1;max-width:360px;">
        <AppIcon name="search" :size="14" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
        <input class="mhr-input" style="padding-left:32px;" placeholder="Search by name or status…" v-model="q" />
      </div>
    </div>

    <!-- Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>NAME</th>
              <th>STATUS</th>
              <th>CREATED</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="4" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No venues found
              </td>
            </tr>
            <tr v-for="venue in filtered" :key="venue.id">
              <td style="font-weight:500;color:var(--mhr-ink);">{{ venue.title }}</td>
              <td>
                <span
                  class="mhr-badge"
                  :class="venue.statusName === 'active' ? 'mhr-badge--success' : (venue.statusName ? 'mhr-badge--neutral' : '')"
                >
                  {{ venue.statusName || '—' }}
                </span>
              </td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">{{ fmtDate(venue.createdAt) }}</td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(venue.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div
                    v-if="openMenuId === venue.id"
                    @click.stop
                    style="position:absolute;right:0;top:100%;margin-top:4px;min-width:160px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;"
                  >
                    <button
                      @click="openEdit(venue)"
                      style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);"
                      @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
                      @mouseleave="$event.currentTarget.style.background='transparent'"
                    >
                      <AppIcon name="edit" :size="14" /><span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button
                      @click="confirmDelete(venue)"
                      style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-danger);"
                      @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
                      @mouseleave="$event.currentTarget.style.background='transparent'"
                    >
                      <AppIcon name="trash" :size="14" /><span>Delete</span>
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast" class="mhr-toast" :style="toast.isError ? 'background:var(--mhr-danger);' : ''">
      <AppIcon :name="toast.isError ? 'x' : 'check'" :size="16" />
      {{ toast.msg }}
    </div>

    <!-- Add Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="showAddModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Venue</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new venue</p>
            </div>
            <button class="mhr-icon-btn" @click="showAddModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div style="display:grid;gap:16px;">
            <div class="mhr-field">
              <label class="mhr-field__label">NAME *</label>
              <input class="mhr-input" v-model="form.title" placeholder="Enter venue name" />
              <div v-if="form.errors.title" style="color:var(--mhr-danger);font-size:12px;margin-top:4px;">{{ form.errors.title }}</div>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="form.active_flag">
                <option :value="null">Select status…</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.title }}</option>
              </select>
              <div v-if="form.errors.active_flag" style="color:var(--mhr-danger);font-size:12px;margin-top:4px;">{{ form.errors.active_flag }}</div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showAddModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="addVenue" :disabled="form.processing" :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''">
            <span v-if="form.processing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              Creating…
            </span>
            <span v-else>Create Venue</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="mhr-modal__scrim" @click.self="showEditModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Edit Venue</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingVenue?.title }}</p>
            </div>
            <button class="mhr-icon-btn" @click="showEditModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div style="display:grid;gap:16px;">
            <div class="mhr-field">
              <label class="mhr-field__label">NAME *</label>
              <input class="mhr-input" v-model="editForm.title" placeholder="Enter venue name" />
              <div v-if="editForm.errors.title" style="color:var(--mhr-danger);font-size:12px;margin-top:4px;">{{ editForm.errors.title }}</div>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="editForm.active_flag">
                <option :value="null">Select status…</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.title }}</option>
              </select>
              <div v-if="editForm.errors.active_flag" style="color:var(--mhr-danger);font-size:12px;margin-top:4px;">{{ editForm.errors.active_flag }}</div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showEditModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="updateVenue" :disabled="editForm.processing" :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''">
            <span v-if="editForm.processing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              Saving…
            </span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDelModal" class="mhr-modal__scrim" @click.self="showDelModal = false">
      <div class="mhr-modal mhr-modal--sm">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete Venue</h2>
          <p class="mhr-modal__sub">This action cannot be undone.</p>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);font-size:14px;line-height:1.5;">
            Are you sure you want to delete <strong>{{ venueToDelete?.title }}</strong>?
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDelModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteVenue">Delete</button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>
