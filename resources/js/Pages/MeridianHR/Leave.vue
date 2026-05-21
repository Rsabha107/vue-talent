<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:       { type: String, default: 'employee' },
  leaveBalance: { type: Object, default: () => ({}) },
  leaves:       { type: Array,  default: () => [] },
})

const filter = ref('all')
const showModal = ref(false)
const toast = ref(null)

const form = ref({ type: 'Annual', from: '', to: '', note: '' })

const filtered = computed(() => {
  if (filter.value === 'all') return props.leaves
  return props.leaves.filter(l => l.status === filter.value)
})

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
function fmtRange(a, b) {
  if (!a) return ''
  if (a === b) return fmtDate(a)
  const da = new Date(a), db = new Date(b)
  if (da.getMonth() === db.getMonth())
    return `${da.getDate()} – ${db.getDate()} ${db.toLocaleDateString(undefined, { month: 'short', year: 'numeric' })}`
  const fmtS = d => d.toLocaleDateString(undefined, { day: '2-digit', month: 'short' })
  return `${fmtS(da)} – ${fmtS(db)}, ${db.getFullYear()}`
}

function submitLeave() {
  router.post(route('hr.leave.store'), form.value, {
    onSuccess: () => {
      showModal.value = false
      toast.value = 'Leave request submitted'
      form.value = { type: 'Annual', from: '', to: '', note: '' }
      setTimeout(() => { toast.value = null }, 3000)
    },
  })
}

const lb = computed(() => props.leaveBalance)
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Time off</h1>
        <p class="mhr-page-head__sub">Leave balances and request history</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--primary" @click="showModal = true">
          <AppIcon name="plus" /> Request time off
        </button>
      </div>
    </div>

    <!-- Balance cards -->
    <div class="mhr-grid-4" style="margin-bottom:24px;">
      <div class="mhr-stat" v-for="(bal, key) in { Annual: lb.annual, Sick: lb.sick, Personal: lb.personal }" :key="key">
        <div class="mhr-stat__label">{{ key }} leave</div>
        <div class="mhr-stat__value">
          <em>{{ (bal?.total ?? 0) - (bal?.used ?? 0) - (bal?.pending ?? 0) }}</em>
          <span class="mhr-stat__unit"> / {{ bal?.total ?? 0 }} days</span>
        </div>
        <div style="margin-top:12px;height:6px;background:var(--mhr-line-2);border-radius:999px;overflow:hidden;display:flex;">
          <div :style="`width:${((bal?.used??0)/(bal?.total??1))*100}%;background:var(--green-700)`" />
          <div :style="`width:${((bal?.pending??0)/(bal?.total??1))*100}%;background:var(--green-300)`" />
        </div>
        <div class="mhr-stat__delta">
          {{ bal?.used ?? 0 }} used
          <span v-if="(bal?.pending ?? 0) > 0" style="color:var(--mhr-warn);"> · {{ bal.pending }} pending</span>
        </div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Unpaid leave</div>
        <div class="mhr-stat__value"><em>{{ lb.unpaid?.used ?? 0 }}</em><span class="mhr-stat__unit"> days</span></div>
        <div class="mhr-stat__delta">No cap</div>
      </div>
    </div>

    <!-- Filter tabs + history table -->
    <div class="mhr-card">
      <div class="mhr-card__hd">
        <div>
          <h3 class="mhr-card__title">Leave history</h3>
          <p class="mhr-card__sub">{{ filtered.length }} request{{ filtered.length !== 1 ? 's' : '' }}</p>
        </div>
        <div class="mhr-card__hd-actions">
          <div style="display:flex;gap:4px;padding:3px;background:var(--mhr-surface-2);border:1px solid var(--mhr-line);border-radius:9px;">
            <button v-for="f in ['all','pending','approved','rejected']" :key="f"
              class="mhr-btn mhr-btn--sm"
              :style="filter === f ? 'background:var(--green-700);color:#fff;' : 'background:transparent;color:var(--mhr-ink-2);'"
              @click="filter = f">
              {{ f.charAt(0).toUpperCase() + f.slice(1) }}
            </button>
          </div>
        </div>
      </div>
      <table class="mhr-table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Status</th>
            <th>Filed</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="6" style="text-align:center;padding:40px;color:var(--mhr-ink-3);">No records</td>
          </tr>
          <tr v-for="l in filtered" :key="l.id">
            <td><strong>{{ l.type }}</strong></td>
            <td>{{ fmtRange(l.from, l.to) }}</td>
            <td>{{ l.days }}d</td>
            <td><StatusPill :status="l.status" /></td>
            <td style="color:var(--mhr-ink-3);">{{ fmtDate(l.filed) }}</td>
            <td style="color:var(--mhr-ink-3);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ l.note }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Request modal -->
    <div v-if="showModal" class="mhr-modal__scrim" @click.self="showModal = false">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Request time off</h2>
          <p class="mhr-modal__sub">Your request will be sent to your manager for approval.</p>
        </div>
        <div class="mhr-modal__body">
          <div class="mhr-field">
            <label class="mhr-field__label">Leave type</label>
            <select v-model="form.type" class="mhr-select">
              <option>Annual</option>
              <option>Sick</option>
              <option>Personal</option>
              <option>Unpaid</option>
            </select>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="mhr-field">
              <label class="mhr-field__label">From</label>
              <input type="date" v-model="form.from" class="mhr-input" />
            </div>
            <div class="mhr-field">
              <label class="mhr-field__label">To</label>
              <input type="date" v-model="form.to" class="mhr-input" />
            </div>
          </div>
          <div class="mhr-field">
            <label class="mhr-field__label">Note <span class="mhr-field__hint">(optional)</span></label>
            <textarea v-model="form.note" class="mhr-textarea" placeholder="Add context for your manager…" rows="3" />
          </div>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--ghost" @click="showModal = false">Cancel</button>
          <button class="mhr-btn mhr-btn--primary" @click="submitLeave">Submit request</button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast">
        <AppIcon name="check" /> {{ toast }}
      </div>
    </Transition>
  </div>
</template>
