import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Permission-based access control composable
 * 
 * Provides reactive access to user permissions and roles for building
 * dynamic UIs based on capabilities rather than hardcoded role checks.
 * 
 * @returns {Object} Permission utilities
 */
export function usePermissions() {
  const page = usePage()
  
  /**
   * Permission map from backend (BaseController)
   * Contains boolean flags for each capability
   */
  const can = computed(() => page.props.can || {})
  
  /**
   * User's assigned Spatie roles
   * Array of role names: ['employee', 'manager', 'payroll-admin']
   */
  const userRoles = computed(() => page.props.userRoles || [])
  
  /**
   * Available modules for this user
   * Array of { key, name, icon, url } objects
   */
  const modules = computed(() => page.props.modules || [])
  
  /**
   * Check if user has a specific role
   * @param {string} role - Role name to check
   * @returns {boolean}
   */
  const hasRole = (role) => userRoles.value.includes(role)
  
  /**
   * Check if user has any of the provided roles
   * @param {...string} roles - Role names to check
   * @returns {boolean}
   */
  const hasAnyRole = (...roles) => roles.some(role => hasRole(role))
  
  /**
   * Check if user has all of the provided roles
   * @param {...string} roles - Role names to check
   * @returns {boolean}
   */
  const hasAllRoles = (...roles) => roles.every(role => hasRole(role))
  
  /**
   * Check if user has access to a specific module
   * @param {string} moduleKey - Module key ('hr', 'payroll', 'procurement')
   * @returns {boolean}
   */
  const hasModule = (moduleKey) => modules.value.some(m => m.key === moduleKey)
  
  /**
   * Get current module based on route name
   * @returns {string} Module key ('hr', 'payroll', etc.)
   */
  const currentModule = computed(() => {
    const routeName = page.props.ziggy?.location || ''
    if (routeName.includes('/payroll')) return 'payroll'
    if (routeName.includes('/procurement')) return 'procurement'
    if (routeName.includes('/recruiting')) return 'recruiting'
    if (routeName.includes('/finance')) return 'finance'
    return 'hr'
  })
  
  return {
    can,
    userRoles,
    modules,
    hasRole,
    hasAnyRole,
    hasAllRoles,
    hasModule,
    currentModule,
  }
}
