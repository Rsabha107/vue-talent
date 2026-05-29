<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  batches: { type: Array, default: () => [] },
})

const page = usePage()
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const showFinalizeModal = ref(false)
const showProcessModal = ref(false)
const selectedBatch = ref(null)
const isProcessing = ref(false)
const isRefreshing = ref(false)

// Create batch form
const batchForm = ref({
  batch_name: '',
  month_id: new Date().getMonth() + 1,
  year: new Date().getFullYear().toString(),
  notes: '',
})

// Toast notification
const toast = ref(null)
function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => toast.value = null, 3000)
}

// Watch for flash messages
if (page.props.flash?.success) {
  showToast(page.props.flash.success)
}

// Watch for error messages (from withErrors)
if (page.props.errors?.error) {
  showToast(page.props.errors.error, true)
}

// Months
const months = [
  { id: 1, name: 'January' },
  { id: 2, name: 'February' },
  { id: 3, name: 'March' },
  { id: 4, name: 'April' },
  { id: 5, name: 'May' },
  { id: 6, name: 'June' },
  { id: 7, name: 'July' },
  { id: 8, name: 'August' },
  { id: 9, name: 'September' },
  { id: 10, name: 'October' },
  { id: 11, name: 'November' },
  { id: 12, name: 'December' },
]

// Years (current year + 2 back)
const years = computed(() => {
  const currentYear = new Date().getFullYear()
  return [currentYear - 2, currentYear - 1, currentYear].map(y => y.toString())
})

function openCreateModal() {
  showCreateModal.value = true
  // Default to current month
  const now = new Date()
  batchForm.value = {
    batch_name: `Payroll - ${months.find(m => m.id === now.getMonth() + 1)?.name} ${now.getFullYear()}`,
    month_id: now.getMonth() + 1,
    year: now.getFullYear().toString(),
    notes: '',
  }
}

function closeCreateModal() {
  showCreateModal.value = false
}

function createBatch() {
  if (!batchForm.value.batch_name.trim()) {
    showToast('Batch name is required', true)
    return
  }

  isProcessing.value = true
  router.post(route('payroll.payment-batches.store'), batchForm.value, {
    onSuccess: (page) => {
      closeCreateModal()
      // Check for error in response (withErrors case)
      if (page.props.errors?.error) {
        showToast(page.props.errors.error, true)
      }
    },
    onError: (errors) => {
      console.error('Batch creation errors:', errors)
      // Handle validation errors or general errors
      const errorMsg = errors.error || errors[Object.keys(errors)[0]] || 'Failed to create batch'
      showToast(errorMsg, true)
    },
    onFinish: () => {
      isProcessing.value = false
    }
  })
}

function viewBatch(batch) {
  router.visit(route('payroll.payment-batches.show', batch.id))
}

function exportBatch(batch) {
  window.location.href = route('payroll.payment-batches.export', batch.id)
}

function openFinalizeModal(batch) {
  selectedBatch.value = batch
  showFinalizeModal.value = true
}

function closeFinalizeModal() {
  showFinalizeModal.value = false
  selectedBatch.value = null
}

function confirmFinalize() {
  if (!selectedBatch.value) return

  router.post(route('payroll.payment-batches.finalize', selectedBatch.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast('Batch finalized successfully')
      closeFinalizeModal()
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to finalize batch', true)
    }
  })
}

function openProcessModal(batch) {
  selectedBatch.value = batch
  showProcessModal.value = true
}

function closeProcessModal() {
  showProcessModal.value = false
  selectedBatch.value = null
}

function confirmProcess() {
  if (!selectedBatch.value) return

  router.post(route('payroll.payment-batches.process', selectedBatch.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast('Batch marked as processed')
      closeProcessModal()
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to process batch', true)
    }
  })
}

function openDeleteModal(batch) {
  selectedBatch.value = batch
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  selectedBatch.value = null
}

function confirmDelete() {
  if (!selectedBatch.value) return

  router.delete(route('payroll.payment-batches.destroy', selectedBatch.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeDeleteModal()
      showToast('Batch deleted successfully')
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to delete batch', true)
    }
  })
}

function fmtMoney(n) {
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

function getStatusClass(status) {
  const statusLower = status.toLowerCase()
  if (statusLower === 'processed') return 'mhr-badge--success'
  if (statusLower === 'finalized') return 'mhr-badge--info'
  return 'mhr-badge--neutral'
}

function refreshData() {
  isRefreshing.value = true
  router.get(route('payroll.payment-batches'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Payment Batches</h1>
        <p class="mhr-page-head__sub">Create and manage payment batches for approved timesheets</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
        <button class="mhr-btn mhr-btn--primary" @click="openCreateModal">
          <AppIcon name="plus" /> Create Payment Batch
        </button>
      </div>
    </div>

    <!-- Batches Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>BATCH NUMBER</th>
              <th>NAME</th>
              <th>PERIOD</th>
              <th style="text-align:right;">TIMESHEETS</th>
              <th style="text-align:right;">EMPLOYEES</th>
              <th style="text-align:right;">TOTAL AMOUNT</th>
              <th>STATUS</th>
              <th>CREATED</th>
              <th style="width:200px;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="batches.length === 0">
              <td colspan="9" style="text-align:center;padding:40px;">
                <AppIcon name="box" :size="32" style="color:var(--mhr-ink-3);margin-bottom:12px;" />
                <div style="color:var(--mhr-ink-3);font-size:14px;">No payment batches yet</div>
                <div style="color:var(--mhr-ink-4);font-size:12px;margin-top:4px;">Create your first batch to get started</div>
              </td>
            </tr>
            <tr v-for="batch in batches" :key="batch.id">
              <td style="font-family:monospace;font-weight:600;color:var(--mhr-ink);">{{ batch.batchNumber }}</td>
              <td style="font-weight:500;color:var(--mhr-ink);">{{ batch.batchName }}</td>
              <td style="color:var(--mhr-ink-2);">{{ batch.period }}</td>
              <td style="text-align:right;color:var(--mhr-ink-2);">{{ batch.timesheetCount }}</td>
              <td style="text-align:right;color:var(--mhr-ink-2);">{{ batch.employeeCount }}</td>
              <td style="text-align:right;font-weight:600;color:var(--mhr-ink);font-family:monospace;">{{ fmtMoney(batch.totalAmount) }}</td>
              <td>
                <span class="mhr-badge" :class="getStatusClass(batch.status)">{{ batch.status }}</span>
              </td>
              <td style="color:var(--mhr-ink-3);font-size:12px;">{{ fmtDate(batch.createdAt) }}</td>
              <td>
                <div style="display:flex;gap:4px;">
                  <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="viewBatch(batch)" title="View Details">
                    <AppIcon name="eye" :size="14" />
                  </button>
                  <button class="mhr-btn mhr-btn--outline mhr-btn--sm" @click="exportBatch(batch)" title="Export to Excel">
                    <AppIcon name="download" :size="14" />
                  </button>
                  <button 
                    v-if="batch.canFinalize" 
                    class="mhr-btn mhr-btn--primary mhr-btn--sm" 
                    @click="openFinalizeModal(batch)"
                    title="Finalize Batch"
                  >
                    <AppIcon name="check" :size="14" />
                  </button>
                  <button 
                    v-if="batch.canProcess" 
                    class="mhr-btn mhr-btn--outline mhr-btn--sm" 
                    @click="openProcessModal(batch)"
                    title="Mark as Processed"
                  >
                    <AppIcon name="dollar" :size="14" />
                  </button>
                  <button 
                    v-if="batch.canEdit" 
                    class="mhr-btn mhr-btn--danger mhr-btn--sm" 
                    @click="openDeleteModal(batch)"
                    title="Delete Batch"
                  >
                    <AppIcon name="trash" :size="14" />
                  </button>
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

    <!-- Create Batch Modal -->
    <div v-if="showCreateModal" class="mhr-modal__scrim" @click.self="closeCreateModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Create Payment Batch</h2>
          <p class="mhr-modal__sub">Generate a new payment batch from approved timesheets</p>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">Batch Name</label>
            <input 
              v-model="batchForm.batch_name" 
              type="text" 
              class="mhr-input" 
              placeholder="e.g., Payroll - May 2026"
            />
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="mhr-field">
              <label class="mhr-field__label">Month</label>
              <select v-model="batchForm.month_id" class="mhr-select">
                <option v-for="month in months" :key="month.id" :value="month.id">{{ month.name }}</option>
              </select>
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">Year</label>
              <select v-model="batchForm.year" class="mhr-select">
                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Notes (Optional)</label>
            <textarea 
              v-model="batchForm.notes" 
              class="mhr-input" 
              rows="3"
              placeholder="Add any notes about this batch..."
              style="resize:vertical;"
            ></textarea>
          </div>

          <div style="background:var(--mhr-info-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-top:16px;">
            <AppIcon name="info" :size="16" style="color:var(--mhr-info);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;">
              This will create a batch from all <strong>Approved</strong> timesheets for the selected period that are not already in another batch.
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeCreateModal" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="createBatch" 
            :disabled="isProcessing || !batchForm.batch_name.trim()"
            :style="(isProcessing || !batchForm.batch_name.trim()) ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              <span>Creating...</span>
            </span>
            <span v-else>
              <AppIcon name="check" :size="14" style="vertical-align:middle;margin-right:4px;" />
              Create Batch
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Finalize Confirmation Modal -->
    <div v-if="showFinalizeModal" class="mhr-modal__scrim" @click.self="closeFinalizeModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Finalize Payment Batch</h2>
          <p class="mhr-modal__sub" v-if="selectedBatch">{{ selectedBatch.batchNumber }}</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-warn);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-warn);line-height:1.5;">
              <strong>This will lock the batch.</strong><br>
              Once finalized, you cannot edit or delete this batch. It will be ready for processing.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to finalize this batch?</p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeFinalizeModal">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="confirmFinalize">
            <AppIcon name="check" :size="14" /> Finalize Batch
          </button>
        </div>
      </div>
    </div>

    <!-- Process Confirmation Modal -->
    <div v-if="showProcessModal" class="mhr-modal__scrim" @click.self="closeProcessModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Process Payment Batch</h2>
          <p class="mhr-modal__sub" v-if="selectedBatch">{{ selectedBatch.batchNumber }}</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--green-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="check" :size="16" style="color:var(--green-500);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--green-700);line-height:1.5;">
              <strong>Mark as processed.</strong><br>
              This will mark the batch as paid and complete the payment cycle.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to mark this batch as processed?</p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeProcessModal">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="confirmProcess">
            <AppIcon name="dollar" :size="14" /> Mark as Processed
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="closeDeleteModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete Payment Batch</h2>
          <p class="mhr-modal__sub" v-if="selectedBatch">{{ selectedBatch.batchNumber }}</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-danger-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-danger);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-danger);line-height:1.5;">
              <strong>This action cannot be undone.</strong><br>
              The payment batch and all its items will be permanently deleted.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to delete this batch?</p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeDeleteModal">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--danger" @click="confirmDelete">
            <AppIcon name="trash" :size="14" style="vertical-align:middle;margin-right:4px;" />
            Delete Batch
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
