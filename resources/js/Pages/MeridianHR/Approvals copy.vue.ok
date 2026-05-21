<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  kind:    { type: String, default: 'leave' }, // 'leave' | 'time'
  items:   { type: Array,  default: () => [] },
  hrRole:  { type: String, default: 'manager' },
})

const selected  = ref(new Set())
const toast     = ref(null)
const reviewing = ref(null)
const localItems = ref(props.items.map(i => ({ ...i, _status: 'pending' })))

const isLeave = computed(() => props.kind === 'leave')
const pending = computed(() => localItems.value.filter(i => i._status === 'pending'))

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
  ids.forEach(id => {
    const item = localItems.value.find(i => i.id === id)
    if (item) item._status = 'approved'
  })
  selected.value = new Set()
  reviewing.value = null
  showToast(`${ids.length} ${isLeave.value ? 'leave request' : 'timesheet'}${ids.length > 1 ? 's' : ''} approved`)
}
function reject(ids) {
  ids.forEach(id => {
    const item = localItems.value.find(i => i.id === id)
    if (item) item._status = 'rejected'
  })
  selected.value = new Set()
  reviewing.value = null
  showToast(`${ids.length} request${ids.length > 1 ? 's' : ''} rejected`)
}

function showToast(msg) {
  toast.value = msg
  setTimeout(() => { toast.value = null }, 3000)
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
        <h1 class="mhr-page-head__title">{{ isLeave ? 'Leave approvals' : 'Timesheet approvals' }}</h1>
        <p class="mhr-page-head__sub">{{ pending.length }} pending · {{ localItems.length }} total</p>
      </div>
      <div v-if="selected.size > 0" class="mhr-page-head__actions">
        <span style="font-size:13px;color:var(--mhr-ink-3);">{{ selected.size }} selected</span>
        <button class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([...selected])">
          <AppIcon name="x" /> Reject
        </button>
        <button class="mhr-btn mhr-btn--primary" @click="approve([...selected])">
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
            <th v-if="isLeave">Type</th>
            <th>{{ isLeave ? 'Dates' : 'Period' }}</th>
            <th>{{ isLeave ? 'Days' : 'Hours' }}</th>
            <th>Filed</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="localItems.length === 0">
            <td colspan="8" style="text-align:center;padding:60px;color:var(--mhr-ink-3);">
              <div style="font-family:var(--mhr-font-display);font-size:20px;color:var(--mhr-ink);margin-bottom:6px;">All clear</div>
              No items to review
            </td>
          </tr>
          <tr v-for="item in localItems" :key="item.id"
            :style="item._status !== 'pending' ? 'opacity:0.5;' : ''">
            <td>
              <span v-if="item._status === 'pending'"
                class="mhr-checkbox"
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
            <td v-if="isLeave">{{ item.type }}</td>
            <td>{{ isLeave ? fmtRange(item.from, item.to) : item.period }}</td>
            <td>{{ isLeave ? item.days + 'd' : item.worked + 'h' }}</td>
            <td style="color:var(--mhr-ink-3);">{{ fmtDate(item.filed || item.submitted) }}</td>
            <td><StatusPill :status="item._status" /></td>
            <td>
              <div style="display:flex;gap:6px;" v-if="item._status === 'pending'">
                <button class="mhr-btn mhr-btn--sm mhr-btn--ghost mhr-btn--danger" @click="reject([item.id])">
                  Reject
                </button>
                <button class="mhr-btn mhr-btn--sm mhr-btn--primary" @click="approve([item.id])">
                  Approve
                </button>
              </div>
              <button v-else class="mhr-btn mhr-btn--sm mhr-btn--ghost" @click="reviewing = item">
                View
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Detail drawer-style modal -->
    <div v-if="reviewing" class="mhr-modal__scrim" @click.self="reviewing = null">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <div style="display:flex;align-items:center;gap:12px;">
            <AppAvatar :name="reviewing.emp" :c="reviewing.c" size="lg" />
            <div>
              <h2 class="mhr-modal__title" style="font-size:18px;">{{ reviewing.emp }}</h2>
              <p class="mhr-modal__sub">
                {{ isLeave ? reviewing.type + ' · ' + fmtRange(reviewing.from, reviewing.to) : reviewing.period }}
              </p>
            </div>
          </div>
        </div>
        <div class="mhr-modal__body">
          <div v-if="reviewing.note" style="background:var(--mhr-surface-2);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--mhr-ink-2);">
            "{{ reviewing.note }}"
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">{{ isLeave ? 'Duration' : 'Hours logged' }}</div>
              <strong>{{ isLeave ? reviewing.days + ' days' : reviewing.worked + 'h' }}</strong>
            </div>
            <div>
              <div style="color:var(--mhr-ink-3);margin-bottom:4px;">Filed</div>
              <strong>{{ fmtDate(reviewing.filed || reviewing.submitted) }}</strong>
            </div>
          </div>
          <div v-if="reviewing.hasOverlap" style="background:var(--mhr-warn-bg);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--mhr-warn);display:flex;gap:8px;align-items:center;">
            <AppIcon name="alert" :size="14" /> Team overlap detected during this period
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="reviewing = null">Close</button>
          <button v-if="reviewing._status === 'pending'" class="mhr-btn mhr-btn--outline mhr-btn--danger" @click="reject([reviewing.id])">Reject</button>
          <button v-if="reviewing._status === 'pending'" class="mhr-btn mhr-btn--primary" @click="approve([reviewing.id])">Approve</button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast"><AppIcon name="check" /> {{ toast }}</div>
    </Transition>
  </div>
</template>
