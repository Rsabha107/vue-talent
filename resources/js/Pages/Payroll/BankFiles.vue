<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  bankFiles: { type: Array, default: () => [] },
  finalizedBatches: { type: Array, default: () => [] },
})

const page = usePage()
const showGenerateModal = ref(false)
const showDeleteModal = ref(false)
const isProcessing = ref(false)
const fileToDelete = ref(null)

// Generate form
const generateForm = ref({
  payment_batch_id: '',
  file_format: 'csv',
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
if (page.props.flash?.error) {
  showToast(page.props.flash.error, true)
}

function openGenerateModal() {
  if (props.finalizedBatches.length === 0) {
    showToast('No finalized batches available. Finalize a payment batch first.', true)
    return
  }
  showGenerateModal.value = true
  generateForm.value = {
    payment_batch_id: props.finalizedBatches[0]?.id || '',
    file_format: 'csv',
    notes: '',
  }
}

function closeGenerateModal() {
  showGenerateModal.value = false
}

function generateFile() {
  if (!generateForm.value.payment_batch_id) {
    showToast('Please select a payment batch', true)
    return
  }

  isProcessing.value = true
  router.post(route('payroll.bank-files.generate'), generateForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      closeGenerateModal()
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to generate file', true)
    },
    onFinish: () => {
      isProcessing.value = false
    }
  })
}

function downloadFile(fileId) {
  window.location.href = route('payroll.bank-files.download', fileId)
}

function openDeleteModal(file) {
  fileToDelete.value = file
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  fileToDelete.value = null
}

function deleteFile() {
  if (!fileToDelete.value) return

  isProcessing.value = true
  router.delete(route('payroll.bank-files.destroy', fileToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeDeleteModal()
      showToast('Bank file deleted successfully')
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to delete file', true)
    },
    onFinish: () => {
      isProcessing.value = false
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

function fmtFileSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Bank Files</h1>
        <p class="mhr-page-head__sub">Generate and download bank payment files for finalized batches</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton />
        <button class="mhr-btn mhr-btn--primary" @click="openGenerateModal">
          <AppIcon name="doc" /> Generate Bank File
        </button>
      </div>
    </div>

    <!-- Bank Files Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>FILE NAME</th>
              <th>BATCH</th>
              <th>FORMAT</th>
              <th style="text-align:right;">RECORDS</th>
              <th style="text-align:right;">TOTAL AMOUNT</th>
              <th style="text-align:right;">FILE SIZE</th>
              <th>GENERATED</th>
              <th style="width:120px;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="bankFiles.length === 0">
              <td colspan="8" style="text-align:center;padding:40px;">
                <AppIcon name="doc" :size="32" style="color:var(--mhr-ink-3);margin-bottom:12px;" />
                <div style="color:var(--mhr-ink-3);font-size:14px;">No bank files generated yet</div>
                <div style="color:var(--mhr-ink-4);font-size:12px;margin-top:4px;">Finalize a payment batch, then generate a file</div>
              </td>
            </tr>
            <tr v-for="file in bankFiles" :key="file.id">
              <td style="font-family:monospace;font-weight:500;color:var(--mhr-ink);font-size:12px;">{{ file.fileName }}</td>
              <td>
                <div style="font-weight:600;font-size:12px;font-family:monospace;color:var(--mhr-ink);">{{ file.batchNumber }}</div>
                <div style="font-size:11px;color:var(--mhr-ink-3);margin-top:2px;">{{ file.batchName }}</div>
              </td>
              <td>
                <span class="mhr-badge mhr-badge--neutral">{{ file.fileFormat }}</span>
              </td>
              <td style="text-align:right;color:var(--mhr-ink-2);">{{ file.recordCount }}</td>
              <td style="text-align:right;font-weight:600;color:var(--green-700);font-family:monospace;">{{ fmtMoney(file.totalAmount) }}</td>
              <td style="text-align:right;color:var(--mhr-ink-3);font-size:12px;">{{ fmtFileSize(file.fileSize) }}</td>
              <td style="color:var(--mhr-ink-3);font-size:12px;">
                <div>{{ fmtDate(file.generatedAt) }}</div>
                <div style="font-size:11px;margin-top:2px;">by {{ file.generatedBy }}</div>
              </td>
              <td>
                <div style="display:flex;gap:6px;">
                  <button 
                    class="mhr-btn mhr-btn--primary mhr-btn--sm" 
                    @click="downloadFile(file.id)" 
                    :disabled="!file.exists"
                    :title="file.exists ? 'Download' : 'File not found'"
                  >
                    <AppIcon name="download" :size="14" />
                  </button>
                  <button 
                    class="mhr-btn mhr-btn--danger mhr-btn--sm" 
                    @click="openDeleteModal(file)" 
                    title="Delete"
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

    <!-- Info card -->
    <div class="mhr-card" style="margin-top:16px;background:var(--mhr-info-bg);border:1px solid var(--mhr-info);">
      <div class="mhr-card__body">
        <div style="display:flex;gap:12px;">
          <AppIcon name="info" :size="20" style="color:var(--mhr-info);flex-shrink:0;margin-top:2px;" />
          <div>
            <h3 style="font-size:14px;font-weight:600;color:var(--mhr-info);margin:0 0 6px;">About Bank Files</h3>
            <p style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;">
              Bank files are generated from <strong>finalized</strong> payment batches and contain employee payment information ready for bank upload. 
              CSV format is a standard spreadsheet file, while TXT format is a pipe-delimited text file. Choose the format that matches your bank's requirements.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toast" class="mhr-toast" :style="toast.isError ? 'background:var(--mhr-danger);' : ''">
      <AppIcon :name="toast.isError ? 'x' : 'check'" :size="16" />
      {{ toast.msg }}
    </div>

    <!-- Generate File Modal -->
    <div v-if="showGenerateModal" class="mhr-modal__scrim" @click.self="closeGenerateModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Generate Bank File</h2>
          <p class="mhr-modal__sub">Export payment data for bank processing</p>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">Payment Batch</label>
            <select v-model="generateForm.payment_batch_id" class="mhr-select">
              <option value="">Select a finalized batch...</option>
              <option v-for="batch in finalizedBatches" :key="batch.id" :value="batch.id">
                {{ batch.batchNumber }} - {{ batch.batchName }} ({{ batch.period }})
              </option>
            </select>
            <p style="font-size:12px;color:var(--mhr-ink-3);margin-top:6px;">
              Only finalized batches can be exported to bank files.
            </p>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">File Format</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <label class="format-option" :class="{ 'format-option--selected': generateForm.file_format === 'csv' }">
                <input type="radio" v-model="generateForm.file_format" value="csv" style="position:absolute;opacity:0;" />
                <div style="display:flex;flex-direction:column;gap:4px;">
                  <div style="font-weight:600;font-size:14px;">CSV</div>
                  <div style="font-size:12px;color:var(--mhr-ink-3);">Comma-separated spreadsheet</div>
                </div>
                <AppIcon name="check" :size="16" v-if="generateForm.file_format === 'csv'" style="color:var(--mhr-accent);" />
              </label>
              <label class="format-option" :class="{ 'format-option--selected': generateForm.file_format === 'txt' }">
                <input type="radio" v-model="generateForm.file_format" value="txt" style="position:absolute;opacity:0;" />
                <div style="display:flex;flex-direction:column;gap:4px;">
                  <div style="font-weight:600;font-size:14px;">TXT</div>
                  <div style="font-size:12px;color:var(--mhr-ink-3);">Pipe-delimited text file</div>
                </div>
                <AppIcon name="check" :size="16" v-if="generateForm.file_format === 'txt'" style="color:var(--mhr-accent);" />
              </label>
            </div>
          </div>

          <div class="mhr-field">
            <label class="mhr-field__label">Notes (Optional)</label>
            <textarea 
              v-model="generateForm.notes" 
              class="mhr-input" 
              rows="3"
              placeholder="Add any notes about this file generation..."
              style="resize:vertical;"
            ></textarea>
          </div>

          <div style="background:var(--mhr-info-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-top:16px;">
            <AppIcon name="info" :size="16" style="color:var(--mhr-info);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;">
              The generated file will include all employees in the selected batch with their payment amounts and bank account details.
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeGenerateModal" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--primary" 
            @click="generateFile" 
            :disabled="isProcessing || !generateForm.payment_batch_id"
            :style="(isProcessing || !generateForm.payment_batch_id) ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              <span>Generating...</span>
            </span>
            <span v-else>
              <AppIcon name="doc" :size="14" style="vertical-align:middle;margin-right:4px;" />
              Generate File
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="mhr-modal__scrim" @click.self="closeDeleteModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd" style="border-bottom:3px solid var(--mhr-danger);">
          <h2 class="mhr-modal__title" style="color:var(--mhr-danger);">Delete Bank File</h2>
          <p class="mhr-modal__sub">This action cannot be undone</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-danger-soft);border:1px solid var(--mhr-danger);border-radius:8px;padding:16px;margin-bottom:20px;">
            <div style="display:flex;gap:12px;align-items:flex-start;">
              <AppIcon name="alert" :size="20" style="color:var(--mhr-danger);flex-shrink:0;" />
              <div>
                <div style="font-weight:600;color:var(--mhr-danger);margin-bottom:6px;">Warning</div>
                <div style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;">
                  Both the database record and the physical file will be permanently deleted.
                </div>
              </div>
            </div>
          </div>

          <div v-if="fileToDelete" style="background:var(--mhr-surface);border-radius:8px;padding:16px;">
            <div style="display:grid;grid-template-columns:120px 1fr;gap:12px;font-size:13px;">
              <div style="color:var(--mhr-ink-3);font-weight:500;">File Name:</div>
              <div style="font-family:monospace;color:var(--mhr-ink);font-weight:600;">{{ fileToDelete.fileName }}</div>
              
              <div style="color:var(--mhr-ink-3);font-weight:500;">Batch:</div>
              <div style="color:var(--mhr-ink-2);">{{ fileToDelete.batchNumber }} - {{ fileToDelete.batchName }}</div>
              
              <div style="color:var(--mhr-ink-3);font-weight:500;">Format:</div>
              <div style="text-transform:uppercase;color:var(--mhr-ink-2);">{{ fileToDelete.fileFormat }}</div>
              
              <div style="color:var(--mhr-ink-3);font-weight:500;">Records:</div>
              <div style="color:var(--mhr-ink-2);">{{ fileToDelete.recordCount }}</div>
            </div>
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeDeleteModal" :disabled="isProcessing">
            Cancel
          </button>
          <button 
            class="mhr-btn mhr-btn--danger" 
            @click="deleteFile" 
            :disabled="isProcessing"
            :style="isProcessing ? 'opacity:0.6;cursor:not-allowed;' : ''"
          >
            <span v-if="isProcessing" style="display:flex;align-items:center;gap:8px;">
              <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
              </svg>
              <span>Deleting...</span>
            </span>
            <span v-else>
              <AppIcon name="trash" :size="14" style="vertical-align:middle;margin-right:4px;" />
              Delete File
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.format-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border: 2px solid var(--mhr-line);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
}

.format-option:hover {
  border-color: var(--mhr-accent);
  background: var(--mhr-accent-soft);
}

.format-option--selected {
  border-color: var(--mhr-accent);
  background: var(--mhr-accent-soft);
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
