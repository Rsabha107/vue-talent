<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BaseModuleLayout from './BaseModuleLayout.vue'
import { usePayrollNavigation } from '@/Composables/usePayrollNavigation'

const page = usePage()
const { navigation, pageTitle, breadcrumbGroup, payrollPage } = usePayrollNavigation()

// User and permissions
const auth = computed(() => page.props.auth || {})
const can = computed(() => page.props.can || {})

// Browser title
const browserTitle = computed(() => {
  const base = pageTitle.value
  return base === 'Home' ? 'Payroll' : `${base} · Payroll`
})
</script>

<template>
  <BaseModuleLayout
    module-key="payroll"
    module-name="Payroll"
    module-icon="dollar"
    :navigation="navigation"
    :page-title="browserTitle"
    :breadcrumb-group="breadcrumbGroup"
    :current-page="payrollPage"
  >
    <!-- Topbar content (can add filters or date range selector here later) -->
    <template #topbar>
      <!-- Empty for now - can add payroll-specific controls like month/year selector -->
    </template>

    <!-- Main Content -->
    <slot />
  </BaseModuleLayout>
</template>
