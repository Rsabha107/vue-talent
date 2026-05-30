<script setup>
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  me:      { type: Object, default: () => ({}) },
  profile: { type: Object, default: () => ({}) },
})

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">My profile</h1>
        <p class="mhr-page-head__sub">Personal details, emergency contact, and banking</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--outline"><AppIcon name="edit" /> Edit profile</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:16px;">
      <!-- Profile card -->
      <div class="mhr-card" style="padding:24px;text-align:center;">
        <AppAvatar :name="me.name" :c="me.avatarColor" :initials="me.initials" size="xl" />
        <h3 style="font-family:var(--mhr-font-display);font-size:22px;font-weight:500;margin:16px 0 4px;letter-spacing:-0.01em;">
          {{ me.name }}
        </h3>
        <div style="color:var(--mhr-ink-3);font-size:13.5px;">
          <div>{{ me.systemRole }}<template v-if="me.role"> · {{ me.role }}</template></div>
          <div v-if="me.systemRoles && me.systemRoles.length > 1" style="display:flex;flex-wrap:wrap;gap:4px;justify-content:center;margin-top:6px;">
            <span 
              v-for="(role, idx) in me.systemRoles.slice(1)" 
              :key="idx" 
              style="display:inline-block;padding:2px 8px;font-size:10px;font-weight:500;color:var(--mhr-accent);background:var(--mhr-accent-soft);border-radius:3px;text-transform:uppercase;letter-spacing:0.3px;"
            >
              {{ role }}
            </span>
          </div>
        </div>
        <div style="margin-top:16px;padding:10px 14px;background:var(--mhr-accent-soft);border-radius:8px;font-size:12px;color:var(--green-800);">
          <span class="mhr-mono">{{ me.empNumber }}</span> · joined {{ fmtDate(me.joinDate) }}
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:18px;text-align:left;">
          <div v-for="item in [
            { icon: 'mail',     v: me.email },
            { icon: 'phone',    v: profile.phone || 'N/A' },
            { icon: 'building', v: profile.location || 'N/A' },
            { icon: 'user',     v: 'Reports to ' + (me.manager || 'N/A') },
          ]" :key="item.icon"
            style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--mhr-ink-2);">
            <AppIcon :name="item.icon" :size="14" style="color:var(--mhr-ink-3);flex-shrink:0;" />
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ item.v }}</span>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Employment -->
        <div class="mhr-card">
          <div class="mhr-card__hd"><h3 class="mhr-card__title">Employment</h3></div>
          <div style="padding:8px 20px 16px;">
            <div v-for="row in [
              { k: 'Employee ID',      v: me.empNumber || 'N/A',          mono: true },
              { k: 'Department',       v: me.department || 'N/A' },
              { k: 'Manager',          v: me.manager || 'N/A' },
              { k: 'Start date',       v: fmtDate(me.joinDate) || 'N/A' },
              { k: 'Employment type',  v: 'Full-time · Permanent' },
              { k: 'Work location',    v: profile.location || 'N/A' },
            ]" :key="row.k"
              style="display:grid;grid-template-columns:180px 1fr;gap:16px;padding:10px 0;border-bottom:1px solid var(--mhr-line-2);font-size:13.5px;">
              <div style="color:var(--mhr-ink-3);">{{ row.k }}</div>
              <div :class="row.mono ? 'mhr-mono' : ''">{{ row.v }}</div>
            </div>
          </div>
        </div>

        <!-- Personal -->
        <div class="mhr-card">
          <div class="mhr-card__hd"><h3 class="mhr-card__title">Personal</h3></div>
          <div style="padding:8px 20px 16px;">
            <div v-for="row in [
              { k: 'Date of birth', v: profile.dob || 'N/A' },
              { k: 'Nationality',   v: profile.nationality || 'N/A' },
              { k: 'Address',       v: profile.address || 'N/A' },
            ]" :key="row.k"
              style="display:grid;grid-template-columns:180px 1fr;gap:16px;padding:10px 0;border-bottom:1px solid var(--mhr-line-2);font-size:13.5px;">
              <div style="color:var(--mhr-ink-3);">{{ row.k }}</div>
              <div>{{ row.v }}</div>
            </div>
          </div>
        </div>

        <!-- Emergency contact -->
        <div class="mhr-card">
          <div class="mhr-card__hd"><h3 class="mhr-card__title">Emergency contact</h3></div>
          <div style="padding:8px 20px 16px;">
            <div v-for="row in [
              { k: 'Name',  v: profile.emergencyName  || 'N/A' },
              { k: 'Phone', v: profile.emergencyPhone || 'N/A' },
            ]" :key="row.k"
              style="display:grid;grid-template-columns:180px 1fr;gap:16px;padding:10px 0;border-bottom:1px solid var(--mhr-line-2);font-size:13.5px;">
              <div style="color:var(--mhr-ink-3);">{{ row.k }}</div>
              <div>{{ row.v }}</div>
            </div>
          </div>
        </div>

        <!-- Banking -->
        <div class="mhr-card">
          <div class="mhr-card__hd"><h3 class="mhr-card__title">Banking</h3></div>
          <div style="padding:8px 20px 16px;">
            <div v-for="row in [
              { k: 'Bank',    v: profile.bank || 'N/A' },
              { k: 'Account', v: profile.accountNumber || 'N/A', mono: true },
              { k: 'Routing', v: profile.routingNumber || 'N/A', mono: true },
            ]" :key="row.k"
              style="display:grid;grid-template-columns:180px 1fr;gap:16px;padding:10px 0;border-bottom:1px solid var(--mhr-line-2);font-size:13.5px;">
              <div style="color:var(--mhr-ink-3);">{{ row.k }}</div>
              <div :class="row.mono ? 'mhr-mono' : ''">{{ row.v }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
