<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  categories: { type: Array, default: () => [] },
})

const activeCat = ref(props.categories[0]?.id || '')
const activeDoc = ref(props.categories[0]?.items[0] || null)

const catObj    = computed(() => props.categories.find(c => c.id === activeCat.value))
const items     = computed(() => catObj.value?.items || [])

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

const CAT_ICONS = {
  contracts:    'file-signature',
  ids:          'id-card',
  certificates: 'award',
  policies:     'book',
  payslips:     'wallet',
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Documents</h1>
        <p class="mhr-page-head__sub">Your contracts, certificates, and company policies</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--outline"><AppIcon name="upload" /> Upload</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:220px 1fr;gap:16px;">
      <!-- Category sidebar -->
      <div style="display:flex;flex-direction:column;gap:2px;">
        <button v-for="cat in categories" :key="cat.id"
          class="mhr-btn"
          :style="activeCat === cat.id
            ? 'background:var(--mhr-accent-soft);color:var(--mhr-accent-ink);width:100%;justify-content:flex-start;font-weight:500;'
            : 'background:transparent;color:var(--mhr-ink-2);width:100%;justify-content:flex-start;'"
          @click="activeCat = cat.id; activeDoc = cat.items[0] || null">
          <AppIcon :name="CAT_ICONS[cat.id] || 'doc'" :size="15" />
          {{ cat.label }}
          <span style="margin-left:auto;font-size:11px;color:var(--mhr-ink-4);">{{ cat.items.length }}</span>
        </button>
      </div>

      <!-- Documents list + preview -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="mhr-card">
          <div class="mhr-card__hd">
            <h3 class="mhr-card__title">{{ catObj?.label }}</h3>
            <p class="mhr-card__sub">{{ items.length }} document{{ items.length !== 1 ? 's' : '' }}</p>
          </div>
          <div style="padding:4px 0;">
            <div v-for="doc in items" :key="doc.id"
              style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--mhr-line-2);cursor:pointer;"
              :style="activeDoc?.id === doc.id ? 'background:var(--mhr-accent-soft);' : ''"
              @click="activeDoc = doc">
              <div style="width:36px;height:36px;border-radius:8px;background:var(--mhr-surface-2);border:1px solid var(--mhr-line);display:grid;place-items:center;flex-shrink:0;color:var(--mhr-ink-3);">
                <AppIcon name="doc" :size="16" />
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:13.5px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ doc.name }}</div>
                <div style="font-size:11.5px;color:var(--mhr-ink-3);">{{ doc.type }} · {{ doc.size }} · {{ doc.pages }}p</div>
              </div>
              <StatusPill :status="doc.status" />
            </div>
          </div>
        </div>

        <!-- Preview panel -->
        <div class="mhr-card" style="overflow:hidden;">
          <template v-if="activeDoc">
            <div class="mhr-card__hd">
              <div>
                <h3 class="mhr-card__title">{{ activeDoc.name }}</h3>
                <p class="mhr-card__sub">{{ activeDoc.type }} · {{ activeDoc.size }} · {{ fmtDate(activeDoc.date) }}</p>
              </div>
              <div class="mhr-card__hd-actions">
                <button class="mhr-icon-btn"><AppIcon name="download" :size="14" /></button>
              </div>
            </div>
            <div class="mhr-card__body" style="background:var(--mhr-surface-2);display:flex;flex-direction:column;align-items:center;gap:16px;min-height:280px;justify-content:center;">
              <div style="width:80px;height:100px;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;box-shadow:var(--mhr-shadow-2);">
                <AppIcon name="doc" :size="28" style="color:var(--mhr-ink-3);" />
                <span style="font-size:10px;font-weight:700;color:var(--mhr-ink-4);letter-spacing:0.05em;">{{ activeDoc.type }}</span>
              </div>
              <div style="text-align:center;">
                <p style="font-size:13.5px;font-weight:500;">{{ activeDoc.name }}</p>
                <p style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">{{ activeDoc.pages }} page{{ activeDoc.pages !== 1 ? 's' : '' }}</p>
              </div>
              <div style="display:flex;gap:8px;">
                <button class="mhr-btn mhr-btn--outline mhr-btn--sm"><AppIcon name="eye" /> Preview</button>
                <button class="mhr-btn mhr-btn--ghost mhr-btn--sm"><AppIcon name="download" /> Download</button>
              </div>
            </div>
            <div style="padding:12px 20px;border-top:1px solid var(--mhr-line-2);display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12.5px;">
              <div><span style="color:var(--mhr-ink-3);">Status</span><br><StatusPill :status="activeDoc.status" /></div>
              <div><span style="color:var(--mhr-ink-3);">Last updated</span><br><strong>{{ fmtDate(activeDoc.date) }}</strong></div>
            </div>
          </template>
          <div v-else class="mhr-card__body" style="display:flex;align-items:center;justify-content:center;min-height:280px;color:var(--mhr-ink-3);">
            Select a document to preview
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
