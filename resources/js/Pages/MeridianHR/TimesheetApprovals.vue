<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  items:   { type: Array,  default: () => [] },
  hrRole:  { type: String, default: 'manager' },
})

const selected  = ref(new Set())
const toast     = ref(null)
const reviewing = ref(null)
const isProcessing = ref(false)
const isRefreshing = ref(false)
const showConfirmModal = ref(false)
const confirmAction = ref(null) // 'approve' or 'reject'
const confirmIds = ref([])
const additionalInfo = ref('')

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
  const route_name = action === 'approve' ? 'hr.approvals.time.approve' : 'hr.approvals.time.reject'
  
  isProcessing.value = true
  router.post(route(route_name), {
    ids: ids,
    additional_information: additionalInfo.value
  }, {
    onSuccess: () => {
      selected.value = new Set()
      reviewing.value = null
      showConfirmModal.value = false
      additionalInfo.value = ''
      showToast(`${ids.length} timesheet${ids.length > 1 ? 's' : ''} ${action === 'approve' ? 'approved' : 'rejected'}`)
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || `Failed to ${action} timesheets`)
    },
    onFinish: () => {
      isProcessing.value = false
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

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

function refreshData() {
  isRefreshing.value = true
  router.reload({
    only: ['items'],
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Timesheet approvals</h1>
        <p class="mhr-page-head__sub">{{ pending.length }} pending</p>
      </div>
      <div class="mhr-page-head__actions" v-if="selected.size === 0">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
      </div>
      <div v-if="selected.size > 0" class="mhr-page-head__actions">
        <span style="font-size:13px;color:var(--mhr-ink-3);">{{ selected.size }} selected</span>
        <button class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([...selected])" :disabled="isProcessing">
          <AppIcon name="x" /> Reject
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="approve([...selected])" :disabled="isProcessing">
          <AppIcon name="check" /> Approve all
        </button>
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
            <th>Period</th>
            <th>Days Worked</th>
            <th>Leave</th>
            <th>Unpaid</th>
            <th>Submitted</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="items.length === 0">
            <td colspan="8" style="text-align:center;padding:60px;color:var(--mhr-ink-3);">
              <div style="font-family:var(--mhr-font-display);font-size:20px;color:var(--mhr-ink);margin-bottom:6px;">All clear</div>
              No timesheets to review
            </td>
          </tr>
          <tr v-for="item in items" :key="item.id">
            <td>
              <span class="mhr-checkbox"
                :data-checked="selected.has(item.id) ? '1' : '0'"
                @click="toggle(item.id)" />
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <AppAvatar :name="item.emp" :c="item.c" />
                <div>
                  <div style="font-weight:500;">{{ item.emp }}</div>
                  <div class="mhr-mono" style="font-size:11.5px;color:var(--mhr-ink-3);">{{ item.empId }}</div>
                </div>
              </div>
            </td>
            <td>{{ item.period }}</td>
            <td>{{ item.worked }}</td>
            <td>{{ item.leave }}</td>
            <td>{{ item.unpaid }}</td>
            <td style="color:var(--mhr-ink-3);">{{ fmtDate(item.submitted) }}</td>
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
            <AppAvatar :name="reviewing.emp" :c="reviewing.c" size="lg" />
            <div>
              <h2 class="mhr-modal__title" style="font-size:18px;">{{ reviewing.emp }}</h2>
              <p class="mhr-modal__sub">{{ reviewing.period }}</p>
            </div>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div v-if="reviewing.note" style="background:var(--mhr-surface-2);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--mhr-ink-2);margin-bottom:16px;">
            <div style="color:var(--mhr-ink-3);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Note</div>
            "{{ reviewing.note }}"
          </div>
          
          <!-- Daily breakdown if available -->
          <div v-if="reviewing.days && reviewing.days.length" style="margin-bottom:16px;">
            <div style="color:var(--mhr-ink-3);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">Daily Breakdown</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
              <div v-for="day in reviewing.days" :key="day.date" 
                style="display:flex;justify-content:space-between;padding:8px 12px;background:var(--mhr-surface-2);border-radius:6px;font-size:13px;">
                <span>{{ fmtDate(day.date) }}</span>
                <strong>{{ day.hours }}h</strong>
              </div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Total Hours</div>
              <strong>{{ reviewing.worked }}h</strong>
            </div>
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Submitted</div>
              <strong>{{ fmtDate(reviewing.submitted) }}</strong>
            </div>
            <div v-if="reviewing.expected !== undefined">
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Expected Hours</div>
              <strong>{{ reviewing.expected }}h</strong>
            </div>
            <div v-if="reviewing.overtime !== undefined && reviewing.overtime > 0">
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Overtime</div>
              <strong style="color:var(--mhr-accent);">+{{ reviewing.overtime }}h</strong>
            </div>
          </div>
          
          <div v-if="reviewing.hasOverlap" style="background:var(--mhr-warn-bg);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--mhr-warn);display:flex;gap:8px;align-items:center;margin-top:16px;">
            <AppIcon name="alert" :size="14" /> Potential scheduling conflict detected
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="reviewing = null">Close</button>
          <button v-if="reviewing._status === 'pending'" class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([reviewing.id])">Reject</button>
          <button v-if="reviewing._status === 'pending'" class="mhr-btn mhr-btn--primary" @click="approve([reviewing.id])">Approve</button>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal for Approve/Reject -->
    <div v-if="showConfirmModal" class="mhr-modal__scrim" @click.self="cancelConfirmation">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">
            {{ confirmAction === 'approve' ? 'Approve Timesheet' : 'Reject Timesheet' }}
          </h2>
          <p class="mhr-modal__sub">
            {{ confirmIds.length }} timesheet{{ confirmIds.length > 1 ? 's' : '' }} selected
          </p>
        </div>
        <div class="mhr-modal__body">
          <div v-if="confirmAction === 'reject'" style="background:var(--mhr-warn-bg);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--mhr-warn);display:flex;gap:8px;align-items:center;margin-bottom:16px;">
            <AppIcon name="alert" :size="14" /> This action will reject the selected timesheet(s)
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
            <AppIcon v-if="isProcessing" name="refresh" :size="14" class="icon-spin" />
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
.icon-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
