<script setup>
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  stats: { type: Object, default: () => ({}) },
  errors: { type: Array, default: () => [] },
  hasFailures: { type: Boolean, default: false },
  hasExportableFailures: { type: Boolean, default: false },
  entityName: { type: String, default: 'records' }, // e.g., "salary records", "employees", "bank accounts"
})

const emit = defineEmits(['close', 'export-failed'])

const hasUpdated = computed(() => props.stats?.updated > 0)
</script>

<template>
  <div v-if="show" class="mhr-modal__scrim" @click.self="emit('close')">
    <div class="mhr-modal mhr-modal--md">
      <div class="mhr-modal__hd">
        <h2 class="mhr-modal__title">Import Complete</h2>
        <p class="mhr-modal__sub">Summary of the import operation</p>
      </div>
      <div class="mhr-modal__body">
        <!-- Stats Grid -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(120px, 1fr));gap:12px;margin-bottom:20px;">
          <div style="background:var(--mhr-surface);border-radius:8px;padding:16px;text-align:center;border:1px solid var(--mhr-line);">
            <div style="font-size:28px;font-weight:700;color:var(--mhr-ink);margin-bottom:4px;">
              {{ stats?.total || 0 }}
            </div>
            <div style="font-size:12px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;">
              Total
            </div>
          </div>
          <div style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);border-radius:8px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(16, 185, 129, 0.2);">
            <div style="font-size:28px;font-weight:700;color:white;margin-bottom:4px;">
              {{ stats?.success || 0 }}
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.5px;">
              Success
            </div>
          </div>
          <div v-if="hasUpdated" style="background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);border-radius:8px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(59, 130, 246, 0.2);">
            <div style="font-size:28px;font-weight:700;color:white;margin-bottom:4px;">
              {{ stats?.updated || 0 }}
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.5px;">
              Updated
            </div>
          </div>
          <div v-if="hasFailures" style="background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%);border-radius:8px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(239, 68, 68, 0.2);">
            <div style="font-size:28px;font-weight:700;color:white;margin-bottom:4px;">
              {{ stats?.failed || 0 }}
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.5px;">
              Failed
            </div>
          </div>
        </div>

        <!-- Success Message -->
        <div v-if="!hasFailures" style="background:var(--green-50);border:1px solid var(--green-200);border-radius:8px;padding:16px;display:flex;align-items:center;gap:12px;">
          <div style="width:40px;height:40px;background:var(--green-600);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <AppIcon name="check" :size="20" style="color:white;" />
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;color:var(--green-900);margin-bottom:2px;">
              Import Successful!
            </div>
            <div style="font-size:13px;color:var(--green-700);">
              {{ stats?.success }} {{ entityName }} imported successfully
            </div>
          </div>
        </div>

        <!-- Error Summary -->
        <div v-if="hasFailures" style="background:var(--red-50);border:1px solid var(--red-200);border-radius:8px;padding:16px;">
          <div style="display:flex;align-items:start;gap:12px;margin-bottom:12px;">
            <div style="width:40px;height:40px;background:var(--red-600);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <AppIcon name="alert" :size="20" style="color:white;" />
            </div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:14px;color:var(--red-900);margin-bottom:2px;">
                Import Completed with Errors
              </div>
              <div style="font-size:13px;color:var(--red-700);">
                {{ stats?.failed }} row(s) failed validation. Review the errors below.
              </div>
            </div>
          </div>
          
          <div v-if="errors.length > 0" style="max-height:200px;overflow-y:auto;background:white;border-radius:6px;padding:12px;margin-bottom:12px;">
            <div style="font-weight:600;font-size:13px;color:var(--red-900);margin-bottom:8px;">
              {{ errors.length }} error(s) found:
            </div>
            <div v-for="(error, idx) in errors" :key="idx" style="padding:6px 0;border-bottom:1px solid var(--mhr-line-2);font-size:12px;color:var(--red-700);line-height:1.5;">
              • {{ error }}
            </div>
          </div>

          <div v-if="hasExportableFailures" style="padding:8px;background:rgba(255,255,255,0.6);border-radius:4px;font-size:12px;color:var(--red-700);border-left:3px solid var(--red-600);">
            <strong>💡 Tip:</strong> Click "Export Failed Rows" below to download an Excel file with the errors. Fix the issues and re-import.
          </div>
          <div v-else style="padding:8px;background:rgba(255,255,255,0.6);border-radius:4px;font-size:12px;color:var(--red-700);border-left:3px solid var(--red-600);">
            <strong>💡 Note:</strong> These rows were skipped during import. Review the error messages above.
          </div>
        </div>
      </div>
      <div class="mhr-modal__ft">
        <button v-if="hasExportableFailures" class="mhr-btn mhr-btn--outline" @click="emit('export-failed')" style="margin-right:auto;">
          <AppIcon name="download" :size="14" /> Export Failed Rows
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="emit('close')">
          {{ hasExportableFailures ? 'Done' : 'Close' }}
        </button>
      </div>
    </div>
  </div>
</template>
