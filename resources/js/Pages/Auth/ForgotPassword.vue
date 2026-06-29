<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import '@/../css/meridian.css'

const page = usePage()
const appName = computed(() => page.props.appName || 'Meridian HR')

const props = defineProps({
  status: {
    type: String,
  },
})

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <div class="meridian-app mhr-auth-page">
    <Head :title="`Reset Password - ${appName}`" />

    <div class="mhr-auth-container">
      <!-- Left Panel - Branding -->
      <div class="mhr-auth-brand">
        <div class="mhr-auth-brand__content">
          <div class="mhr-auth-brand__logo">
            <div class="mhr-auth-brand__mark">{{ appName.charAt(0).toLowerCase() }}</div>
            <div class="mhr-auth-brand__name">
              <span>{{ appName }}<em>·</em>HR</span>
            </div>
          </div>
          <h1 class="mhr-auth-brand__title">Reset your password</h1>
          <p class="mhr-auth-brand__subtitle">
            Enter your email address and we'll send you a link to reset your password.
          </p>
        </div>
      </div>

      <!-- Right Panel - Form -->
      <div class="mhr-auth-form-panel">
        <div class="mhr-auth-form-container">
          <!-- Icon -->
          <div class="mhr-forgot-icon">
            <AppIcon name="lock" :size="32" />
          </div>

          <!-- Header -->
          <div class="mhr-auth-header">
            <h2 class="mhr-auth-form-title">Forgot your password?</h2>
            <p class="mhr-auth-form-subtitle">
              No problem. Just enter your email address and we'll send you a password reset link.
            </p>
          </div>

          <!-- Status Message -->
          <div v-if="status" class="mhr-auth-status">
            <AppIcon name="check" :size="16" />
            {{ status }}
          </div>

          <!-- Forgot Password Form -->
          <form @submit.prevent="submit" class="mhr-auth-form">
            <!-- Email Field -->
            <div class="mhr-field">
              <label class="mhr-field__label" for="email">EMAIL ADDRESS</label>
              <div style="position:relative;">
                <AppIcon name="mail" :size="16" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--mhr-ink-3);" />
                <input
                  id="email"
                  type="email"
                  class="mhr-input"
                  style="padding-left:44px;"
                  v-model="form.email"
                  placeholder="you@company.com"
                  required
                  autofocus
                  autocomplete="username"
                />
              </div>
              <div v-if="form.errors.email" class="mhr-field__error">
                <AppIcon name="alert" :size="14" />
                {{ form.errors.email }}
              </div>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              class="mhr-btn mhr-btn--primary mhr-btn--block"
              :disabled="form.processing"
              :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
            >
              <span v-if="form.processing" style="display:flex;align-items:center;gap:8px;justify-content:center;">
                <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" opacity="0.25"/>
                  <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
                </svg>
                Sending...
              </span>
              <span v-else style="display:flex;align-items:center;gap:8px;justify-content:center;">
                <AppIcon name="mail" :size="16" />
                Send Reset Link
              </span>
            </button>
          </form>

          <!-- Footer -->
          <div class="mhr-auth-footer">
            <Link :href="route('login')" class="mhr-forgot-back">
              <AppIcon name="arrow-right" :size="14" style="transform:rotate(180deg);" />
              Back to Login
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Override the sidebar grid layout so the forgot password page fills the viewport */
.meridian-app.mhr-auth-page {
  display: block;
  overflow: auto;
}
</style>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.mhr-auth-container {
  display: flex;
  min-height: 100vh;
  background: var(--mhr-bg);
}

@media (max-width: 1024px) {
  .mhr-auth-container {
    flex-direction: column;
  }
  .mhr-auth-brand {
    display: none;
  }
  .mhr-auth-form-panel {
    padding: 40px 30px;
  }
}

/* Brand Panel */
.mhr-auth-brand {
  flex: 0 0 45%;
  background: linear-gradient(135deg, var(--green-700) 0%, var(--green-800) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px;
  position: relative;
  overflow: hidden;
}

.mhr-auth-brand::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 80%;
  height: 150%;
  background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
  pointer-events: none;
}

.mhr-auth-brand__content {
  position: relative;
  z-index: 1;
  max-width: 480px;
}

.mhr-auth-brand__logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 48px;
}

.mhr-auth-brand__mark {
  width: 48px;
  height: 48px;
  background: rgba(255,255,255,0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 700;
  color: white;
  font-family: var(--mhr-font-display);
}

.mhr-auth-brand__name {
  font-size: 28px;
  font-weight: 600;
  color: white;
  font-family: var(--mhr-font-display);
}

.mhr-auth-brand__title {
  font-size: 42px;
  font-weight: 600;
  color: white;
  line-height: 1.2;
  margin-bottom: 16px;
  font-family: var(--mhr-font-display);
}

.mhr-auth-brand__subtitle {
  font-size: 18px;
  color: rgba(255,255,255,0.8);
  line-height: 1.6;
}

/* Form Panel */
.mhr-auth-form-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 80px;
  background: var(--mhr-surface);
}

.mhr-auth-form-container {
  width: 100%;
  max-width: 520px;
}

.mhr-forgot-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 32px;
  background: var(--mhr-accent-soft);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--mhr-accent);
}

.mhr-auth-header {
  margin-bottom: 32px;
  text-align: center;
}

.mhr-auth-form-title {
  font-size: 28px;
  font-weight: 600;
  color: var(--mhr-ink);
  margin-bottom: 12px;
  font-family: var(--mhr-font-display);
}

.mhr-auth-form-subtitle {
  font-size: 15px;
  color: var(--mhr-ink-3);
  line-height: 1.6;
}

.mhr-auth-status {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: var(--green-100);
  border: 1px solid var(--green-300);
  border-radius: var(--mhr-r);
  color: var(--green-800);
  font-size: 14px;
  margin-bottom: 24px;
  font-weight: 500;
}

.mhr-auth-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.mhr-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.mhr-field__label {
  font-size: 12px;
  font-weight: 600;
  color: var(--mhr-ink-2);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.mhr-field__error {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--mhr-danger);
}

.mhr-btn--block {
  width: 100%;
  justify-content: center;
  margin-top: 8px;
}

.mhr-auth-footer {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--mhr-line);
  text-align: center;
}

.mhr-forgot-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: var(--mhr-ink-3);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s;
}

.mhr-forgot-back:hover {
  color: var(--mhr-ink);
}
</style>
