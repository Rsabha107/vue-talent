<script setup>
import { ref, computed, nextTick, onBeforeUnmount } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  modelValue: { type: Number, default: null },
  employees: { type: Array, required: true },
  placeholder: { type: String, default: 'Select employee...' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const showDropdown = ref(false)
const searchQuery = ref('')
const triggerEl = ref(null)
const dropdownStyle = ref({})

function updateDropdownPosition() {
  if (!triggerEl.value) return
  const rect = triggerEl.value.getBoundingClientRect()
  const margin = 4
  const preferredHeight = 320
  const spaceBelow = window.innerHeight - rect.bottom - margin
  const spaceAbove = rect.top - margin

  if (spaceBelow >= preferredHeight || spaceBelow >= spaceAbove) {
    dropdownStyle.value = {
      position: 'fixed',
      top: `${rect.bottom + margin}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      maxHeight: `${Math.max(160, Math.min(preferredHeight, spaceBelow))}px`,
    }
  } else {
    dropdownStyle.value = {
      position: 'fixed',
      bottom: `${window.innerHeight - rect.top + margin}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      maxHeight: `${Math.max(160, Math.min(preferredHeight, spaceAbove))}px`,
    }
  }
}

function openDropdown() {
  showDropdown.value = true
  nextTick(updateDropdownPosition)
  window.addEventListener('scroll', updateDropdownPosition, true)
  window.addEventListener('resize', updateDropdownPosition)
}

function closeDropdown() {
  showDropdown.value = false
  window.removeEventListener('scroll', updateDropdownPosition, true)
  window.removeEventListener('resize', updateDropdownPosition)
}

onBeforeUnmount(() => {
  window.removeEventListener('scroll', updateDropdownPosition, true)
  window.removeEventListener('resize', updateDropdownPosition)
})

const selectedEmployee = computed(() => {
  if (!props.employees || !Array.isArray(props.employees)) return null
  return props.employees.find(emp => emp.id === props.modelValue)
})

const filteredEmployees = computed(() => {
  if (!props.employees || !Array.isArray(props.employees)) return []
  if (!searchQuery.value) return props.employees
  
  const query = searchQuery.value.toLowerCase()
  return props.employees.filter(emp => 
    emp.full_name?.toLowerCase().includes(query) ||
    emp.employee_number?.toLowerCase().includes(query)
  )
})

function selectEmployee(empId) {
  emit('update:modelValue', empId)
  closeDropdown()
  searchQuery.value = ''
}

function clearSelection() {
  emit('update:modelValue', null)
  searchQuery.value = ''
}
</script>

<template>
  <div style="position:relative;">
    <!-- Selected value / trigger button -->
    <button
      ref="triggerEl"
      type="button"
      @click="!disabled && (showDropdown ? closeDropdown() : openDropdown())"
      class="mhr-select"
      :disabled="disabled"
      style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding-right:10px;"
      :style="[
        !modelValue && required ? 'border-color:var(--mhr-danger);' : '',
        disabled ? 'cursor:not-allowed;opacity:0.6;' : 'cursor:pointer;'
      ]"
    >
      <span v-if="selectedEmployee" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;flex:1;min-width:0;">
        <span style="font-weight:500;">{{ selectedEmployee.full_name }}</span>
        <span style="font-size:11px;color:var(--mhr-ink-3);">{{ selectedEmployee.employee_number }}</span>
      </span>
      <span v-else style="color:var(--mhr-ink-3);flex:1;min-width:0;">{{ placeholder }}</span>
      
      <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
        <!-- Clear button -->
        <button
          v-if="modelValue && !disabled"
          type="button"
          @click.stop="clearSelection"
          style="width:20px;height:20px;border-radius:50%;display:grid;place-items:center;background:transparent;border:none;color:var(--mhr-ink-3);cursor:pointer;transition:all 0.15s;"
          @mouseenter="$event.currentTarget.style.background = 'var(--mhr-line-2)'; $event.currentTarget.style.color = 'var(--mhr-ink)'"
          @mouseleave="$event.currentTarget.style.background = 'transparent'; $event.currentTarget.style.color = 'var(--mhr-ink-3)'"
          title="Clear selection"
        >
          <AppIcon name="x" :size="14" />
        </button>
        
        <!-- Dropdown chevron -->
        <AppIcon name="chevron" :size="12" style="transition:transform 0.2s;flex-shrink:0;" :style="showDropdown ? 'transform:rotate(90deg);' : ''" />
      </div>
    </button>

    <!-- Dropdown (teleported within .meridian-app so it escapes the modal's overflow/stacking context but keeps the --mhr-* CSS variable scope) -->
    <Teleport to=".meridian-app">
      <div
        v-if="showDropdown"
        @click="closeDropdown"
        style="position:fixed;inset:0;z-index:299;"
      />
      <div
        v-if="showDropdown"
        @click.stop
        :style="[dropdownStyle, 'background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:var(--mhr-r);box-shadow:0 4px 12px rgba(0,0,0,0.16);z-index:300;display:flex;flex-direction:column;overflow:hidden;']"
      >
        <!-- Search input -->
        <div style="padding:8px;border-bottom:1px solid var(--mhr-line-2);flex-shrink:0;">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name or number..."
            class="mhr-input"
            style="font-size:13px;padding:8px 10px;"
            @click.stop
            autofocus
          />
        </div>

        <!-- Employee list -->
        <div style="overflow-y:auto;flex:1;min-height:0;">
          <div
            v-if="filteredEmployees.length === 0"
            style="padding:20px;text-align:center;color:var(--mhr-ink-3);font-size:13px;"
          >
            No employees found
          </div>
          <button
            v-for="emp in filteredEmployees"
            :key="emp.id"
            type="button"
            @click="selectEmployee(emp.id)"
            style="width:100%;padding:10px 12px;border:none;background:transparent;text-align:left;cursor:pointer;font-size:13px;color:var(--mhr-ink);display:flex;flex-direction:column;gap:2px;transition:background 0.15s;"
            :style="modelValue === emp.id ? 'background:var(--mhr-accent);color:white;' : ''"
            @mouseenter="$event.currentTarget.style.background = modelValue === emp.id ? 'var(--mhr-accent)' : 'var(--mhr-surface-2)'"
            @mouseleave="$event.currentTarget.style.background = modelValue === emp.id ? 'var(--mhr-accent)' : 'transparent'"
          >
            <span style="font-weight:500;">{{ emp.full_name }}</span>
            <span style="font-size:12px;opacity:0.8;">{{ emp.employee_number }}</span>
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
