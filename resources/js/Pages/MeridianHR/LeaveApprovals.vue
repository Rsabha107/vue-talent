<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  items:   { type: Array,  default: () => [] },
  hrRole:  { type: String, default: 'manager' },
  eventContext: { type: Object, default: null },
  selectedEvent: { type: Number, default: null },
})

const selected  = ref(new Set())
const toast     = ref(null)
const reviewing = ref(null)
const isProcessing = ref(false)
const showConfirmModal = ref(false)
const confirmAction = ref(null) // 'approve' or 'reject'
const confirmIds = ref([])
const additionalInfo = ref('')

// Backend already filters for pending only, so all items are pending
const pending = computed(() => props.items)

function toggleAll() {
  if (selected.value.size === pending.value.length) {
    selected.value = new Set()
  } else {
    selected.value = new Set(pending.value.map(i => i.id))
  }
}

function toggle(id) {
  const s = new Set(selected.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selected.value = s
}

function approve(ids) {
  confirmAction.value = 'approve'
  confirmIds.value = ids
  additionalInfo.value = ''
  showConfirmModal.value = true
}

function reject(ids) {
  confirmAction.value = 'reject'
  confirmIds.value = ids
  additionalInfo.value = ''
  showConfirmModal.value = true
}

function confirmApprovalAction() {
  if (isProcessing.value) return
  
  const ids = confirmIds.value
  const action = confirmAction.value
  const route_name = action === 'approve' ? 'hr.approvals.leave.approve' : 'hr.approvals.leave.reject'
  
  isProcessing.value = true
  router.post(route(route_name), {
    ids: ids,
    additional_information: additionalInfo.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isProcessing.value = false
      selected.value = new Set()
      reviewing.value = null
      showConfirmModal.value = false
      additionalInfo.value = ''
      showToast(`${ids.length} leave request${ids.length > 1 ? 's' : ''} ${action === 'approve' ? 'approved' : 'rejected'}`)
    },
    onError: (errors) => {
      isProcessing.value = false
      console.error(`Failed to ${action} leave requests:`, errors)
      showToast(`Failed to ${action} leave requests`)
    }
  })
}

function cancelConfirmation() {
  showConfirmModal.value = false
  confirmAction.value = null
  confirmIds.value = []
  additionalInfo.value = ''
}

function showToast(msg) {
  toast.value = msg
  setTimeout(() => { toast.value = null }, 3000)
}

function refresh() {
  router.reload({ only: ['items'] })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

function fmtRange(a, b) {
  if (a === b) return fmtDate(a)
  const da = new Date(a), db = new Date(b)
  if (da.getMonth() === db.getMonth())
    return `${da.getDate()} – ${db.getDate()} ${db.toLocaleDateString(undefined, { month: 'short', year: 'numeric' })}`
  const fs = d => d.toLocaleDateString(undefined, { day: '2-digit', month: 'short' })
  return `${fs(da)} – ${fs(db)}, ${db.getFullYear()}`
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Leave approvals</h1>
        <p class="mhr-page-head__sub">
          {{ props.items.length }} pending request{{ props.items.length !== 1 ? 's' : '' }}
          <span v-if="props.eventContext" style="color:var(--mhr-ink-3);margin-left:8px;">
            · {{ props.eventContext.name }}
          </span>
          <span v-else style="color:var(--mhr-warn);margin-left:8px;">
            · All Events (No filter)
          </span>
        </p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--ghost" @click="refresh" title="Refresh">
          <AppIcon name="refresh" :size="16" />
        </button>
        <template v-if="selected.size > 0">
          <span style="font-size:13px;color:var(--mhr-ink-3);">{{ selected.size }} selected</span>
          <button class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([...selected])" :disabled="isProcessing">
            <AppIcon name="x" /> Reject
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="approve([...selected])" :disabled="isProcessing">
            <AppIcon name="check" /> Approve all
          </button>
        </template>
      </div>
    </div>

    <div class="mhr-card">
      <table class="mhr-table">
        <thead>
          <tr>
            <th style="width:36px;">
              <span class="mhr-checkbox"
                :data-checked="selected.size === pending.length && pending.length > 0 ? '1' : selected.size > 0 ? 'indeterminate' : '0'"
                @click="toggleAll" />
            </th>
            <th>Employee</th>
            <th>Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Filed</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="props.items.length === 0">
            <td colspan="8" style="text-align:center;padding:60px;color:var(--mhr-ink-3);">
              <div style="font-family:var(--mhr-font-display);font-size:20px;color:var(--mhr-ink);margin-bottom:6px;">All clear</div>
              No leave requests to review
            </td>
          </tr>
          <tr v-for="item in props.items" :key="item.id">
            <td>
              <span class="mhr-checkbox"
                :data-checked="selected.has(item.id) ? '1' : '0'"
                @click="toggle(item.id)" />
            </td>
            <td>
              <div style="font-weight:500;color:var(--mhr-ink);">{{ item.emp }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">{{ item.empId }}</div>
              <div v-if="!selectedEvent && item.eventName" style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;display:flex;align-items:center;gap:4px;">
                <AppIcon name="calendar" :size="10" style="opacity:0.6;" />
                <span>{{ item.eventName }}</span>
              </div>
            </td>
            <td>{{ item.type }}</td>
            <td>{{ fmtRange(item.from, item.to) }}</td>
            <td>{{ item.days }}d</td>
            <td style="color:var(--mhr-ink-3);">{{ fmtDate(item.filed) }}</td>
            <td><StatusPill status="pending" /></td>
            <td>
              <div style="display:flex;gap:6px;">
                <button class="mhr-btn mhr-btn--sm mhr-btn--ghost mhr-btn--danger" @click="reject([item.id])" :disabled="isProcessing">
                  Reject
                </button>
                <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="approve([item.id])" :disabled="isProcessing">
                  Approve
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Detail modal -->
    <div v-if="reviewing" class="mhr-modal__scrim" @click.self="reviewing = null">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <div style="display:flex;align-items:center;gap:12px;">
            <AppAvatar :name="reviewing.emp" :c="reviewing.c" :initials="reviewing.initials" size="lg" />
            <div>
              <h2 class="mhr-modal__title" style="font-size:18px;">{{ reviewing.emp }}</h2>
              <p class="mhr-modal__sub">{{ reviewing.type }} · {{ fmtRange(reviewing.from, reviewing.to) }}</p>
            </div>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div v-if="reviewing.note" style="background:var(--mhr-surface-2);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--mhr-ink-2);margin-bottom:16px;">
            <div style="color:var(--mhr-ink-3);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Note</div>
            "{{ reviewing.note }}"
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Duration</div>
              <strong>{{ reviewing.days }} days</strong>
            </div>
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Filed</div>
              <strong>{{ fmtDate(reviewing.filed) }}</strong>
            </div>
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Leave Type</div>
              <strong>{{ reviewing.type }}</strong>
            </div>
            <div v-if="reviewing.balance !== undefined">
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Remaining Balance</div>
              <strong>{{ reviewing.balance }} days</strong>
            </div>
          </div>
          <div v-if="reviewing.hasOverlap" style="background:var(--mhr-warn-bg);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--mhr-warn);display:flex;gap:8px;align-items:center;margin-top:16px;">
            <AppIcon name="alert" :size="14" /> Team overlap detected during this period
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="reviewing = null">Close</button>
          <button class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([reviewing.id])" :disabled="isProcessing">Reject</button>
          <button class="mhr-btn mhr-btn--primary" @click="approve([reviewing.id])" :disabled="isProcessing">Approve</button>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal for Approve/Reject -->
    <div v-if="showConfirmModal" class="mhr-modal__scrim" @click.self="cancelConfirmation">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">
            {{ confirmAction === 'approve' ? 'Approve Leave Request' : 'Reject Leave Request' }}
          </h2>
          <p class="mhr-modal__sub">
            {{ confirmIds.length }} request{{ confirmIds.length > 1 ? 's' : '' }} selected
          </p>
        </div>
        <div class="mhr-modal__body">
          <div v-if="confirmAction === 'reject'" style="background:var(--mhr-warn-bg);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--mhr-warn);display:flex;gap:8px;align-items:center;margin-bottom:16px;">
            <AppIcon name="alert" :size="14" /> This action will reject the selected leave request(s)
          </div>
          
          <div class="mhr-field">
            <label class="mhr-field__label">
              Additional Information 
              <span style="color:var(--mhr-ink-3);font-weight:normal;">(Optional)</span>
            </label>
            <textarea 
              v-model="additionalInfo" 
              class="mhr-input" 
              rows="4"
              :placeholder="confirmAction === 'approve' ? 'Add notes about this approval...' : 'Explain the reason for rejection...'"
              style="resize:vertical;min-height:80px;"
            ></textarea>
            <p style="font-size:12px;color:var(--mhr-ink-3);margin-top:6px;">
              This information will be stored with the {{ confirmAction === 'approve' ? 'approval' : 'rejection' }} record.
            </p>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="cancelConfirmation" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            :class="['mhr-btn', confirmAction === 'approve' ? 'mhr-btn--primary' : 'mhr-btn--danger']" 
            @click="confirmApprovalAction" 
            :disabled="isProcessing"
          >
            <AppIcon v-if="isProcessing" name="refresh" :size="14" style="animation: spin 1s linear infinite;" />
            <span v-else>{{ confirmAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast"><AppIcon name="check" /> {{ toast }}</div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
