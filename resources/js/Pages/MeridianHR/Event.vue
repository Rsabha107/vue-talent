<script setup>
import { ref, computed } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:   { type: String, default: 'admin' },
  events:   { type: Array,  default: () => [] },
  venues:   { type: Array,  default: () => [] },
  statuses: { type: Array,  default: () => [] },
})

const q               = ref('')
const showAddModal    = ref(false)
const showEditModal   = ref(false)
const showDeleteModal = ref(false)
const editingEvent    = ref(null)
const eventToDelete   = ref(null)
const toast           = ref(null)
const openMenuId      = ref(null)
const isRefreshing    = ref(false)
const logoPreview     = ref(null)
const editLogoPreview = ref(null)

const getActiveStatusId = () => {
  const active = props.statuses.find(s => s.title?.toLowerCase() === 'active')
  return active ? active.id : (props.statuses[0]?.id ?? null)
}

const form = useForm({
  name: '',
  active_flag: getActiveStatusId(),
  logo: null,
  venue_ids: [],
})

const editForm = useForm({
  id: null,
  name: '',
  active_flag: null,
  logo: null,
  venue_ids: [],
})

const filtered = computed(() => {
  if (!q.value) return props.events
  const query = q.value.toLowerCase()
  return props.events.filter(e =>
    e.name?.toLowerCase().includes(query) ||
    e.statusName?.toLowerCase().includes(query)
  )
})

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => { toast.value = null }, 3000)
}

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s.length === 10 ? s + 'T00:00:00' : s)
    .toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

function handleLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.logo = file
  const reader = new FileReader()
  reader.onload = (ev) => { logoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

function handleEditLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  editForm.logo = file
  const reader = new FileReader()
  reader.onload = (ev) => { editLogoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function refresh() {
  isRefreshing.value = true
  router.reload({
    only: ['events'],
    onFinish: () => setTimeout(() => { isRefreshing.value = false }, 300),
  })
}

function addEvent() {
  form.post(route('hr.events.store'), {
    onSuccess: () => {
      showAddModal.value = false
      showToast('Event created successfully')
      form.reset()
      form.active_flag = getActiveStatusId()
      logoPreview.value = null
    },
    onError: () => showToast('Failed to create event', true),
  })
}

function openEdit(event) {
  editingEvent.value    = event
  editForm.id           = event.id
  editForm.name         = event.name
  editForm.active_flag  = event.activeFlag
  editForm.venue_ids    = event.venueIds || []
  editForm.logo         = null
  editLogoPreview.value = event.logoUrl || null
  showEditModal.value   = true
  openMenuId.value      = null
}

function updateEvent() {
  editForm.put(route('hr.events.update', editForm.id), {
    onSuccess: () => { showEditModal.value = false; showToast('Event updated successfully') },
    onError:   () => showToast('Failed to update event', true),
  })
}

function confirmDelete(event) {
  eventToDelete.value   = event
  showDeleteModal.value = true
  openMenuId.value      = null
}

function deleteEvent() {
  router.delete(route('hr.events.destroy', eventToDelete.value.id), {
    onSuccess: () => { showDeleteModal.value = false; showToast('Event deleted successfully') },
  })
}
</script>

<template>
  <div @click="openMenuId = null">

    <!-- Page Header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Events</h1>
        <p class="mhr-page-head__sub">Manage event information</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refresh" />
        <button class="mhr-btn mhr-btn--primary" @click="showAddModal = true">
          <AppIcon name="plus" /> Add Event
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
              <th style="width:52px;"></th>
              <th>NAME</th>
              <th>VENUES</th>
              <th>EMPLOYEES</th>
              <th>STATUS</th>
              <th>CREATED</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="7" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No events found
              </td>
            </tr>
            <tr v-for="event in filtered" :key="event.id">
              <td style="padding:8px 14px;">
                <img
                  v-if="event.logoUrl"
                  :src="event.logoUrl"
                  :alt="event.name"
                  style="width:40px;height:40px;object-fit:cover;border-radius:var(--mhr-r);border:1px solid var(--mhr-line);"
                />
                <div
                  v-else
                  style="width:40px;height:40px;background:var(--mhr-surface-2);border-radius:var(--mhr-r);border:1px solid var(--mhr-line);display:flex;align-items:center;justify-content:center;color:var(--mhr-ink-3);"
                >
                  <AppIcon name="image" :size="18" />
                </div>
              </td>
              <td style="font-weight:500;color:var(--mhr-ink);">{{ event.name }}</td>
              <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                  <span
                    v-for="venue in event.venues"
                    :key="venue.id"
                    class="mhr-badge mhr-badge--neutral"
                    style="font-size:11px;"
                  >{{ venue.title }}</span>
                  <span v-if="!event.venues?.length" style="color:var(--mhr-ink-3);font-size:13px;">—</span>
                </div>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:6px;">
                  <AppIcon name="users" :size="14" style="color:var(--mhr-ink-3);" />
                  <span style="font-weight:500;color:var(--mhr-ink);">{{ event.employeeCount || 0 }}</span>
                </div>
              </td>
              <td>
                <span
                  class="mhr-badge"
                  :class="event.statusName === 'active' ? 'mhr-badge--success' : (event.statusName ? 'mhr-badge--neutral' : '')"
                >
                  {{ event.statusName || '—' }}
                </span>
              </td>
              <td style="color:var(--mhr-ink-3);font-size:13px;">{{ fmtDate(event.createdAt) }}</td>
              <td>
                <div style="position:relative;">
                  <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop="toggleMenu(event.id)">
                    <AppIcon name="more" :size="13" />
                  </button>
                  <div
                    v-if="openMenuId === event.id"
                    @click.stop
                    style="position:absolute;right:0;top:100%;margin-top:4px;min-width:160px;background:white;border:1px solid var(--mhr-line);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;"
                  >
                    <Link
                      :href="route('hr.events.show', event.id)"
                      style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);text-decoration:none;"
                      @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
                      @mouseleave="$event.currentTarget.style.background='transparent'"
                    >
                      <AppIcon name="users" :size="14" /><span>Manage Team</span>
                    </Link>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button
                      @click="openEdit(event)"
                      style="width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;border:none;background:transparent;cursor:pointer;text-align:left;font-size:13px;color:var(--mhr-ink);"
                      @mouseenter="$event.currentTarget.style.background='var(--mhr-surface)'"
                      @mouseleave="$event.currentTarget.style.background='transparent'"
                    >
                      <AppIcon name="edit" :size="14" /><span>Edit</span>
                    </button>
                    <div style="border-top:1px solid var(--mhr-line-2);margin:4px 0;"></div>
                    <button
                      @click="confirmDelete(event)"
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
      <div class="mhr-modal mhr-modal--lg">
        <div class="mhr-modal__hd">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <h2 class="mhr-modal__title">Add Event</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">Create a new event</p>
            </div>
            <button class="mhr-icon-btn" @click="showAddModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div class="evt-form-grid">
            <div class="mhr-field" style="grid-column:1/3;">
              <label class="mhr-field__label">EVENT NAME *</label>
              <input class="mhr-input" v-model="form.name" placeholder="Enter event name" />
              <div v-if="form.errors.name" class="evt-field-err">{{ form.errors.name }}</div>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="form.active_flag">
                <option :value="null">Select status…</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.title }}</option>
              </select>
              <div v-if="form.errors.active_flag" class="evt-field-err">{{ form.errors.active_flag }}</div>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">EVENT LOGO</label>
              <label class="evt-logo-zone">
                <img v-if="logoPreview" :src="logoPreview" alt="Preview" class="evt-logo-preview" />
                <div v-else class="evt-logo-placeholder">
                  <AppIcon name="image" :size="22" style="color:var(--mhr-ink-4);" />
                  <span>Upload logo</span>
                </div>
                <input type="file" @change="handleLogoChange" accept="image/*" class="evt-file-hidden" />
              </label>
            </div>
            <div class="mhr-field" style="grid-column:1/3;">
              <label class="mhr-field__label">VENUES</label>
              <div class="evt-venue-wrap">
                <label
                  v-for="venue in venues" :key="venue.id"
                  class="evt-venue-pill"
                  :class="{ 'evt-venue-pill--on': form.venue_ids.includes(venue.id) }"
                >
                  <input type="checkbox" :value="venue.id" v-model="form.venue_ids" class="evt-checkbox" />
                  <AppIcon v-if="form.venue_ids.includes(venue.id)" name="check" :size="12" class="evt-pill-check" />
                  {{ venue.title }}
                </label>
                <span v-if="!venues.length" class="evt-venue-empty">No venues available</span>
              </div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showAddModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="addEvent" :disabled="form.processing" :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''">
            <span v-if="form.processing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              Creating…
            </span>
            <span v-else>Create Event</span>
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
              <h2 class="mhr-modal__title">Edit Event</h2>
              <p class="mhr-modal__sub" style="margin-top:2px;">{{ editingEvent?.name }}</p>
            </div>
            <button class="mhr-icon-btn" @click="showEditModal = false" style="margin-top:-4px;">
              <AppIcon name="x" :size="16" />
            </button>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div class="evt-form-grid">
            <div class="mhr-field" style="grid-column:1/3;">
              <label class="mhr-field__label">EVENT NAME *</label>
              <input class="mhr-input" v-model="editForm.name" placeholder="Enter event name" />
              <div v-if="editForm.errors.name" class="evt-field-err">{{ editForm.errors.name }}</div>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">STATUS *</label>
              <select class="mhr-select" v-model="editForm.active_flag">
                <option :value="null">Select status…</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.title }}</option>
              </select>
              <div v-if="editForm.errors.active_flag" class="evt-field-err">{{ editForm.errors.active_flag }}</div>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">EVENT LOGO</label>
              <label class="evt-logo-zone">
                <img v-if="editLogoPreview" :src="editLogoPreview" alt="Current logo" class="evt-logo-preview" />
                <div v-else class="evt-logo-placeholder">
                  <AppIcon name="image" :size="22" style="color:var(--mhr-ink-4);" />
                  <span>Upload logo</span>
                </div>
                <input type="file" @change="handleEditLogoChange" accept="image/*" class="evt-file-hidden" />
              </label>
            </div>
            <div class="mhr-field" style="grid-column:1/3;">
              <label class="mhr-field__label">VENUES</label>
              <div class="evt-venue-wrap">
                <label
                  v-for="venue in venues" :key="venue.id"
                  class="evt-venue-pill"
                  :class="{ 'evt-venue-pill--on': editForm.venue_ids.includes(venue.id) }"
                >
                  <input type="checkbox" :value="venue.id" v-model="editForm.venue_ids" class="evt-checkbox" />
                  <AppIcon v-if="editForm.venue_ids.includes(venue.id)" name="check" :size="12" class="evt-pill-check" />
                  {{ venue.title }}
                </label>
                <span v-if="!venues.length" class="evt-venue-empty">No venues available</span>
              </div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showEditModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="updateEvent" :disabled="editForm.processing" :style="editForm.processing ? 'opacity:0.6;cursor:not-allowed;' : ''">
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
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="showDeleteModal = false">
      <div class="mhr-modal mhr-modal--sm">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete Event</h2>
          <p class="mhr-modal__sub">This action cannot be undone.</p>
        </div>
        <div class="mhr-modal__body">
          <p style="color:var(--mhr-ink-2);font-size:14px;line-height:1.5;">
            Are you sure you want to delete <strong>{{ eventToDelete?.name }}</strong>?
          </p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showDeleteModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--danger" @click="deleteEvent">Delete</button>
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

/* Form two-column grid */
.evt-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

/* Error text */
.evt-field-err {
  color: var(--mhr-danger);
  font-size: 12px;
  margin-top: 2px;
}

/* Logo upload zone */
.evt-logo-zone {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100px;
  border: 2px dashed var(--mhr-line);
  border-radius: var(--mhr-r);
  background: var(--mhr-surface-2);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  overflow: hidden;
  position: relative;
}
.evt-logo-zone:hover {
  border-color: var(--mhr-accent);
  background: var(--mhr-accent-soft);
}
.evt-logo-preview {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  padding: 8px;
}
.evt-logo-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--mhr-ink-3);
}
.evt-file-hidden {
  position: absolute;
  inset: 0;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

/* Venue toggle pills */
.evt-venue-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  padding: 12px;
  border: 1px solid var(--mhr-line);
  border-radius: var(--mhr-r);
  background: var(--mhr-surface-2);
  max-height: 180px;
  overflow-y: auto;
}
.evt-checkbox { display: none; }
.evt-venue-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 999px;
  border: 1.5px solid var(--mhr-line);
  background: var(--mhr-surface);
  font-size: 12.5px;
  color: var(--mhr-ink-3);
  cursor: pointer;
  user-select: none;
  transition: border-color 0.14s, background 0.14s, color 0.14s, box-shadow 0.14s;
  white-space: nowrap;
}
.evt-venue-pill:hover {
  border-color: var(--mhr-accent);
  color: var(--mhr-accent);
  background: var(--mhr-accent-soft);
}
.evt-venue-pill--on {
  background: var(--mhr-accent);
  border-color: var(--mhr-accent);
  color: var(--mhr-accent-fg);
  font-weight: 500;
  box-shadow: 0 1px 4px rgba(59,111,67,0.25);
}
.evt-venue-pill--on:hover {
  background: var(--mhr-accent);
  color: var(--mhr-accent-fg);
}
.evt-pill-check { flex-shrink: 0; }
.evt-venue-empty {
  color: var(--mhr-ink-3);
  font-size: 13px;
  padding: 8px 4px;
}
</style>
