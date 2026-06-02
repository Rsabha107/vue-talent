<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  batch: { type: Object, required: true },
  availableTimesheets: { type: Array, default: () => [] },
})

const page = usePage()
const toast = ref(null)
const isRefreshing = ref(false)
const showFinalizeModal = ref(false)
const showProcessModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showAddModal = ref(false)
const isAddingTimesheets = ref(false)
const isDeletingItem = ref(false)
const isEditingItem = ref(false)
const isFinalizingBatch = ref(false)
const isProcessingBatch = ref(false)
const selectedTimesheets = ref([])
const editingItem = ref(null)
const deletingItem = ref(null)

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => toast.value = null, 3000)
}

// Watch for flash messages
if (page.props.flash?.success) {
  showToast(page.props.flash.success)
}
if (page.props.flash?.error) {
  showToast(page.props.flash.error, true)
}

function backToBatches() {
  router.visit(route('payroll.payment-batches'))
}

function openFinalizeModal() {
  showFinalizeModal.value = true
}

function closeFinalizeModal() {
  if (isFinalizingBatch.value) return // Prevent closing while finalizing
  showFinalizeModal.value = false
}

function confirmFinalize() {
  isFinalizingBatch.value = true
  router.post(route('payroll.payment-batches.finalize', props.batch.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      isFinalizingBatch.value = false
      showToast('Batch finalized successfully')
      closeFinalizeModal()
    },
    onError: (errors) => {
      isFinalizingBatch.value = false
      showToast(Object.values(errors)[0] || 'Failed to finalize batch', true)
    }
  })
}

function openProcessModal() {
  showProcessModal.value = true
}

function closeProcessModal() {
  if (isProcessingBatch.value) return // Prevent closing while processing
  showProcessModal.value = false
}

function confirmProcess() {
  isProcessingBatch.value = true
  router.post(route('payroll.payment-batches.process', props.batch.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      isProcessingBatch.value = false
      showToast('Batch marked as processed')
      closeProcessModal()
    },
    onError: (errors) => {
      isProcessingBatch.value = false
      showToast(Object.values(errors)[0] || 'Failed to process batch', true)
    }
  })
}

function fmtMoney(n) {
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function getStatusClass(status) {
  const statusLower = status.toLowerCase()
  if (statusLower === 'processed') return 'mhr-badge--success'
  if (statusLower === 'finalized') return 'mhr-badge--info'
  return 'mhr-badge--neutral'
}

function refreshData() {
  isRefreshing.value = true
  router.get(route('payroll.payment-batches.show', props.batch.id), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}

function exportBatch() {
  window.location.href = route('payroll.payment-batches.export', props.batch.id)
}

function openEditModal(item) {
  editingItem.value = { ...item }
  showEditModal.value = true
}

function closeEditModal() {
  if (isEditingItem.value) return // Prevent closing while editing
  showEditModal.value = false
  editingItem.value = null
}

function confirmEdit() {
  isEditingItem.value = true
  router.put(route('payroll.payment-batches.items.update', { batchId: props.batch.id, itemId: editingItem.value.id }), {
    payment_amount: editingItem.value.paymentAmount,
    days_worked: editingItem.value.daysWorked,
    account_number: editingItem.value.iban,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isEditingItem.value = false
      showToast('Item updated successfully')
      closeEditModal()
    },
    onError: (errors) => {
      isEditingItem.value = false
      showToast(Object.values(errors)[0] || 'Failed to update item', true)
    }
  })
}

function openDeleteModal(item) {
  deletingItem.value = item
  showDeleteModal.value = true
}

function closeDeleteModal() {
  if (isDeletingItem.value) return // Prevent closing while deleting
  showDeleteModal.value = false
  deletingItem.value = null
}

function confirmDelete() {
  isDeletingItem.value = true
  router.delete(route('payroll.payment-batches.items.destroy', { batchId: props.batch.id, itemId: deletingItem.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      isDeletingItem.value = false
      showToast('Item deleted successfully')
      closeDeleteModal()
    },
    onError: (errors) => {
      isDeletingItem.value = false
      showToast(Object.values(errors)[0] || 'Failed to delete item', true)
    }
  })
}

function openAddModal() {
  selectedTimesheets.value = []
  showAddModal.value = true
}

function closeAddModal() {
  if (isAddingTimesheets.value) return // Prevent closing while adding
  showAddModal.value = false
  selectedTimesheets.value = []
}

function toggleTimesheetSelection(timesheetId) {
  const index = selectedTimesheets.value.indexOf(timesheetId)
  if (index > -1) {
    selectedTimesheets.value.splice(index, 1)
  } else {
    selectedTimesheets.value.push(timesheetId)
  }
}

function confirmAdd() {
  if (selectedTimesheets.value.length === 0) {
    showToast('Please select at least one timesheet', true)
    return
  }

  isAddingTimesheets.value = true
  let addedCount = 0
  let failedCount = 0
  const totalToAdd = selectedTimesheets.value.length
  const timesheetsToAdd = [...selectedTimesheets.value] // Create a copy

  const addNext = (index) => {
    if (index >= timesheetsToAdd.length) {
      isAddingTimesheets.value = false
      
      if (addedCount > 0 && failedCount === 0) {
        showToast(`${addedCount} timesheet(s) added successfully`)
      } else if (addedCount > 0 && failedCount > 0) {
        showToast(`${addedCount} added, ${failedCount} failed (may already exist)`)
      } else {
        showToast('Failed to add timesheets', true)
      }
      
      closeAddModal()
      
      // Refresh page to get updated data
      router.get(route('payroll.payment-batches.show', props.batch.id), {}, {
        preserveState: false,
        preserveScroll: true,
      })
      return
    }

    router.post(route('payroll.payment-batches.items.store', props.batch.id), {
      timesheet_id: timesheetsToAdd[index],
    }, {
      preserveScroll: true,
      preserveState: false,
      only: ['batch', 'availableTimesheets'], // Only update these props
      onSuccess: () => {
        addedCount++
        addNext(index + 1)
      },
      onError: (errors) => {
        failedCount++
        // Continue with next timesheet instead of stopping
        addNext(index + 1)
      }
    })
  }

  addNext(0)
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
          <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="backToBatches">
            <AppIcon name="arrow-left" :size="14" />
          </button>
          <h1 class="mhr-page-head__title" style="margin:0;">{{ batch.batchNumber }}</h1>
          <span class="mhr-badge" :class="getStatusClass(batch.status)">{{ batch.status }}</span>
        </div>
        <p class="mhr-page-head__sub">{{ batch.batchName }}</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
        <button class="mhr-btn mhr-btn--outline" @click="exportBatch">
          <AppIcon name="download" /> Export to Excel
        </button>
        <button 
          v-if="batch.canFinalize" 
          class="mhr-btn mhr-btn--primary" 
          @click="openFinalizeModal"
        >
          <AppIcon name="check" /> Finalize Batch
        </button>
        <button 
          v-if="batch.canProcess" 
          class="mhr-btn mhr-btn--outline" 
          @click="openProcessModal"
        >
          <AppIcon name="dollar" /> Mark as Processed
        </button>
      </div>
    </div>

    <!-- Warning if batch is finalized but still editable -->
    <div v-if="batch.status === 'finalized'" class="mhr-card" style="margin-bottom:24px;background:var(--mhr-warn-bg);border:1px solid var(--mhr-warn);">
      <div style="padding:16px;display:flex;gap:12px;align-items:flex-start;">
        <AppIcon name="alert" :size="18" style="color:var(--mhr-warn);flex-shrink:0;margin-top:2px;" />
        <div>
          <div style="font-weight:600;color:var(--mhr-warn);margin-bottom:4px;">Finalized Batch - Edit with Caution</div>
          <div style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;">
            This batch is finalized. Any edits will revert it to draft status and require re-finalization before processing.
          </div>
        </div>
      </div>
    </div>

    <!-- Batch Summary -->
    <div class="mhr-grid-4" style="margin-bottom:24px;">
      <div class="mhr-stat">
        <div class="mhr-stat__label">Period</div>
        <div class="mhr-stat__value"><em>{{ batch.period }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">payroll cycle</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Timesheets</div>
        <div class="mhr-stat__value"><em>{{ batch.timesheetCount }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">submitted and approved</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Employees</div>
        <div class="mhr-stat__value"><em>{{ batch.employeeCount }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">unique payees</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Total Amount</div>
        <div class="mhr-stat__value"><em>{{ fmtMoney(batch.totalAmount) }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">batch payment total</div>
      </div>
    </div>

    <!-- Batch Details -->
    <div class="mhr-card" style="margin-bottom:24px;">
      <div style="padding:20px;border-bottom:1px solid var(--mhr-line);">
        <h3 style="font-size:14px;font-weight:600;color:var(--mhr-ink);margin:0;">Batch Information</h3>
      </div>
      <div style="padding:20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
          <div>
            <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Created</div>
            <div style="font-size:14px;color:var(--mhr-ink);">{{ fmtDate(batch.createdAt) }}</div>
            <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">by {{ batch.createdBy }}</div>
          </div>
          <div v-if="batch.finalizedAt">
            <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Finalized</div>
            <div style="font-size:14px;color:var(--mhr-ink);">{{ fmtDate(batch.finalizedAt) }}</div>
            <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">by {{ batch.finalizedBy }}</div>
          </div>
          <div v-if="batch.processedAt">
            <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Processed</div>
            <div style="font-size:14px;color:var(--mhr-ink);">{{ fmtDate(batch.processedAt) }}</div>
            <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">by {{ batch.processedBy }}</div>
          </div>
        </div>
        <div v-if="batch.notes" style="margin-top:20px;padding-top:20px;border-top:1px solid var(--mhr-line);">
          <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Notes</div>
          <div style="font-size:14px;color:var(--mhr-ink-2);line-height:1.5;">{{ batch.notes }}</div>
        </div>
      </div>
    </div>

    <!-- Batch Items -->
    <div class="mhr-card">
      <div style="padding:20px;border-bottom:1px solid var(--mhr-line);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:14px;font-weight:600;color:var(--mhr-ink);margin:0;">Payment Details ({{ batch.items.length }})</h3>
        <button 
          v-if="batch.canEdit" 
          class="mhr-btn mhr-btn--outline mhr-btn--sm" 
          @click="openAddModal"
        >
          <AppIcon name="plus" :size="14" /> Add Entry
          <span v-if="availableTimesheets && availableTimesheets.length > 0">({{ availableTimesheets.length }})</span>
        </button>
      </div>
      <div class="mhr-table-wrap" style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="mhr-table" style="min-width:1800px;">
          <thead>
            <tr>
              <th style="min-width:140px;">TIMESHEET PERIOD</th>
              <th style="min-width:120px;">AGREEMENT #</th>
              <th style="min-width:180px;">NAME</th>
              <th style="min-width:150px;">ROLE</th>
              <th style="min-width:100px;">START DATE</th>
              <th style="min-width:100px;">END DATE</th>
              <th style="text-align:right;min-width:130px;">SALARY/MONTH</th>
              <th style="min-width:120px;">SALARY BASIS</th>
              <th style="text-align:right;min-width:100px;">DAYS WORKED</th>
              <th style="text-align:right;min-width:120px;">TOTAL</th>
              <th style="min-width:180px;">IBAN</th>
              <th style="min-width:180px;">ACCOUNT HOLDER NAME</th>
              <th v-if="batch.canEdit" style="width:100px;text-align:center;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in batch.items" :key="item.id">
              <td style="color:var(--mhr-ink-2);font-size:13px;white-space:nowrap;">{{ item.timesheetPeriod }}</td>
              <td style="font-family:monospace;font-weight:500;white-space:nowrap;">{{ item.agreementNumber }}</td>
              <td style="font-weight:500;color:var(--mhr-ink);white-space:nowrap;">{{ item.employeeName }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;white-space:nowrap;">{{ item.role }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;white-space:nowrap;">{{ item.startDate }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;white-space:nowrap;">{{ item.endDate }}</td>
              <td style="text-align:right;font-family:monospace;color:var(--mhr-ink-2);white-space:nowrap;">{{ fmtMoney(item.monthlySalary) }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;white-space:nowrap;">{{ item.salaryBasis }}</td>
              <td style="text-align:right;color:var(--mhr-ink);white-space:nowrap;">{{ item.daysWorked }}</td>
              <td style="text-align:right;font-weight:600;color:var(--green-700);font-family:monospace;white-space:nowrap;">{{ fmtMoney(item.paymentAmount) }}</td>
              <td style="font-family:monospace;font-size:13px;color:var(--mhr-ink-2);white-space:nowrap;">{{ item.iban }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;white-space:nowrap;">{{ item.accountHolderName }}</td>
              <td v-if="batch.canEdit" style="text-align:center;white-space:nowrap;">
                <div style="display:flex;gap:6px;justify-content:center;">
                  <button 
                    class="mhr-btn mhr-btn--ghost mhr-btn--sm" 
                    @click="openEditModal(item)"
                    title="Edit item"
                  >
                    <AppIcon name="edit" :size="14" />
                  </button>
                  <button 
                    class="mhr-btn mhr-btn--ghost mhr-btn--sm" 
                    @click="openDeleteModal(item)"
                    title="Delete item"
                    style="color:var(--mhr-danger);"
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

    <!-- Finalize Confirmation Modal -->
    <div v-if="showFinalizeModal" class="mhr-modal__scrim" @click.self="closeFinalizeModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Finalize Payment Batch</h2>
          <p class="mhr-modal__sub">{{ batch.batchNumber }}</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-warn);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-warn);line-height:1.5;">
              <strong>This will lock the batch for processing.</strong><br>
              You can still edit the batch if needed, but any changes will revert it to draft status.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to finalize this batch?</p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeFinalizeModal" :disabled="isFinalizingBatch">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="confirmFinalize"
            :disabled="isFinalizingBatch"
            :style="isFinalizingBatch ? 'opacity:0.5;cursor:not-allowed;' : ''"
          >
            <span v-if="isFinalizingBatch" style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:6px;"></span>
            <AppIcon v-else name="check" :size="14" /> 
            <span v-if="isFinalizingBatch">Finalizing...</span>
            <span v-else>Finalize Batch</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Process Confirmation Modal -->
    <div v-if="showProcessModal" class="mhr-modal__scrim" @click.self="closeProcessModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Process Payment Batch</h2>
          <p class="mhr-modal__sub">{{ batch.batchNumber }}</p>
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
          <button class="mhr-btn mhr-btn--outline" @click="closeProcessModal" :disabled="isProcessingBatch">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="confirmProcess"
            :disabled="isProcessingBatch"
            :style="isProcessingBatch ? 'opacity:0.5;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessingBatch" style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:6px;"></span>
            <AppIcon v-else name="dollar" :size="14" /> 
            <span v-if="isProcessingBatch">Processing...</span>
            <span v-else>Mark as Processed</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Item Modal -->
    <div v-if="showEditModal && editingItem" class="mhr-modal__scrim" @click.self="closeEditModal">
      <div class="mhr-modal" style="max-width:500px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Edit Payment Item</h2>
          <p class="mhr-modal__sub">{{ editingItem.employeeName }}</p>
        </div>
        <div class="mhr-modal__body">
          <div v-if="batch.status === 'finalized'" style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-warn);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-warn);line-height:1.5;">
              <strong>Warning:</strong> Editing will revert the batch to draft status. You'll need to re-finalize before processing.
            </div>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Payment Amount</label>
            <input 
              v-model="editingItem.paymentAmount" 
              type="number" 
              step="0.01"
              class="mhr-input" 
              placeholder="0.00"
              :disabled="isEditingItem"
            />
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Days Worked</label>
            <input 
              v-model="editingItem.daysWorked" 
              type="number" 
              class="mhr-input" 
              placeholder="0"
              :disabled="isEditingItem"
            />
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">IBAN / Account Number</label>
            <input 
              v-model="editingItem.iban" 
              type="text" 
              class="mhr-input" 
              placeholder="Enter account number"
              :disabled="isEditingItem"
            />
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeEditModal" :disabled="isEditingItem">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="confirmEdit"
            :disabled="isEditingItem"
            :style="isEditingItem ? 'opacity:0.5;cursor:not-allowed;' : ''"
          >
            <span v-if="isEditingItem" style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:6px;"></span>
            <AppIcon v-else name="check" :size="14" /> 
            <span v-if="isEditingItem">Saving...</span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal && deletingItem" class="mhr-modal__scrim" @click.self="closeDeleteModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Delete Payment Item</h2>
          <p class="mhr-modal__sub">{{ deletingItem.employeeName }}</p>
        </div>
        <div class="mhr-modal__body">
          <div v-if="batch.status === 'finalized'" style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-warn);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-warn);line-height:1.5;">
              <strong>Warning:</strong> Deleting will revert the batch to draft status. You'll need to re-finalize before processing.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to delete this payment item? This action cannot be undone.</p>
          <div style="margin-top:16px;padding:12px;background:var(--mhr-surface);border-radius:6px;">
            <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;font-size:13px;">
              <div style="color:var(--mhr-ink-3);">Amount:</div>
              <div style="color:var(--mhr-ink);font-weight:600;">{{ fmtMoney(deletingItem.paymentAmount) }}</div>
              <div style="color:var(--mhr-ink-3);">Days Worked:</div>
              <div style="color:var(--mhr-ink);">{{ deletingItem.daysWorked }}</div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeDeleteModal" :disabled="isDeletingItem">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--danger" 
            @click="confirmDelete"
            :disabled="isDeletingItem"
            :style="isDeletingItem ? 'opacity:0.5;cursor:not-allowed;' : ''"
          >
            <span v-if="isDeletingItem" style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:6px;"></span>
            <AppIcon v-else name="trash" :size="14" /> 
            <span v-if="isDeletingItem">Deleting...</span>
            <span v-else>Delete Item</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Add Entry Modal -->
    <div v-if="showAddModal" class="mhr-modal__scrim" @click.self="closeAddModal">
      <div class="mhr-modal" style="max-width:700px;">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Add Timesheets to Batch</h2>
          <p class="mhr-modal__sub">{{ batch.batchNumber }} • {{ batch.period }}</p>
        </div>
        <div class="mhr-modal__body">
          <div v-if="batch.status === 'finalized'" style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-warn);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-warn);line-height:1.5;">
              <strong>Warning:</strong> Adding entries will revert the batch to draft status. You'll need to re-finalize before processing.
            </div>
          </div>

          <div v-if="availableTimesheets.length === 0" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
            <AppIcon name="check" :size="32" style="color:var(--mhr-ink-4);margin-bottom:12px;" />
            <p style="font-size:14px;">No available timesheets to add. All approved timesheets for this period are already in batches.</p>
          </div>

          <div v-else>
            <p style="font-size:13px;color:var(--mhr-ink-2);margin-bottom:16px;">
              Select timesheets to add to this payment batch. These are approved timesheets from {{ batch.period }} that aren't in any batch yet.
            </p>

            <div style="max-height:400px;overflow-y:auto;border:1px solid var(--mhr-line);border-radius:6px;">
              <table class="mhr-table" style="margin:0;">
                <thead style="position:sticky;top:0;background:var(--mhr-bg);z-index:1;">
                  <tr>
                    <th style="width:40px;text-align:center;" @click.stop>
                      <input 
                        type="checkbox" 
                        :checked="selectedTimesheets.length === availableTimesheets.length && availableTimesheets.length > 0"
                        @change="selectedTimesheets = $event.target.checked ? availableTimesheets.map(t => t.timesheetId) : []"
                        style="cursor:pointer;"
                      />
                    </th>
                    <th>EMPLOYEE</th>
                    <th>PERIOD</th>
                    <th style="text-align:right;">DAYS</th>
                    <th style="text-align:right;">AMOUNT</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="timesheet in availableTimesheets" 
                    :key="timesheet.timesheetId"
                    @click="toggleTimesheetSelection(timesheet.timesheetId)"
                    style="cursor:pointer;"
                    :style="selectedTimesheets.includes(timesheet.timesheetId) ? 'background:var(--mhr-accent-bg);' : ''"
                  >
                    <td style="text-align:center;" @click.stop>
                      <input 
                        type="checkbox" 
                        :checked="selectedTimesheets.includes(timesheet.timesheetId)"
                        @change="toggleTimesheetSelection(timesheet.timesheetId)"
                        style="cursor:pointer;"
                      />
                    </td>
                    <td>
                      <div style="font-weight:500;">{{ timesheet.employeeName }}</div>
                      <div style="font-size:12px;color:var(--mhr-ink-3);">{{ timesheet.employeeNumber }}</div>
                    </td>
                    <td style="font-size:13px;color:var(--mhr-ink-2);">{{ timesheet.period }}</td>
                    <td style="text-align:right;color:var(--mhr-ink);">{{ timesheet.daysWorked }}</td>
                    <td style="text-align:right;font-weight:600;color:var(--green-700);font-family:monospace;">
                      {{ fmtMoney(timesheet.paymentAmount) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="selectedTimesheets.length > 0" style="margin-top:16px;padding:12px;background:var(--mhr-accent-bg);border-radius:6px;display:flex;justify-content:space-between;align-items:center;">
              <div style="font-size:13px;color:var(--mhr-ink);">
                <strong>{{ selectedTimesheets.length }}</strong> timesheet(s) selected
              </div>
              <div style="font-size:13px;font-weight:600;color:var(--green-700);">
                Total: {{ fmtMoney(availableTimesheets.filter(t => selectedTimesheets.includes(t.timesheetId)).reduce((sum, t) => sum + parseFloat(t.paymentAmount), 0)) }}
              </div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeAddModal" :disabled="isAddingTimesheets">
            Cancel
          </button>
          <button 
            v-if="availableTimesheets.length > 0"
            class="mhr-btn mhr-btn--primary" 
            @click="confirmAdd"
            :disabled="selectedTimesheets.length === 0 || isAddingTimesheets"
            :style="(selectedTimesheets.length === 0 || isAddingTimesheets) ? 'opacity:0.5;cursor:not-allowed;' : ''"
          >
            <span v-if="isAddingTimesheets" style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:6px;"></span>
            <AppIcon v-else name="plus" :size="14" /> 
            <span v-if="isAddingTimesheets">Adding...</span>
            <span v-else>Add {{ selectedTimesheets.length > 0 ? selectedTimesheets.length + ' ' : '' }}Timesheet{{ selectedTimesheets.length !== 1 ? 's' : '' }}</span>
          </button>
        </div>
      </div>
    </div>
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
