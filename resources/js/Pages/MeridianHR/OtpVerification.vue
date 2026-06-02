<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import OtpInput from '@/Components/OtpInput.vue'
import '@/../css/meridian.css'

const page = usePage()
const appName = computed(() => page.props.appName || 'Meridian HR')

const props = defineProps({
  email: { type: String, required: true },
  length: { type: Number, default: 4 },
})

const otpInput = ref(null)
const form = useForm({ otp: '' })
const isResending = ref(false)

function submit() {
  if (form.otp.length < props.length) return
  form.post(route('otp.verify'), {
    onError: () => { 
      form.otp = ''
      otpInput.value?.focus() 
    },
  })
}

function resend() {
  isResending.value = true
  useForm({}).post(route('otp.resend'), {
    onSuccess: () => { 
      form.otp = ''
      otpInput.value?.focus()
      isResending.value = false
    },
    onError: () => {
      isResending.value = false
    }
  })
}
</script>

<template>
  <div class="meridian-app mhr-auth-page">
    <Head :title="`Two-Step Verification - ${appName}`" />

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
          <h1 class="mhr-auth-brand__title">Secure access</h1>
          <p class="mhr-auth-brand__subtitle">
            We've sent a verification code to your email to ensure it's really you.
          </p>
        </div>
      </div>

      <!-- Right Panel - OTP Form -->
      <div class="mhr-auth-form-panel">
        <div class="mhr-auth-form-container">
          <!-- Icon -->
          <div class="mhr-otp-icon">
            <AppIcon name="mail" :size="32" />
          </div>

          <!-- Header -->
          <div class="mhr-auth-header">
            <h2 class="mhr-auth-form-title">Verify your email</h2>
            <p class="mhr-auth-form-subtitle">
              Enter the {{ length }}-digit code sent to<br />
              <strong style="color:var(--mhr-ink);">{{ email }}</strong>
            </p>
          </div>

          <!-- Error Message -->
          <div v-if="form.errors.otp" class="mhr-otp-error">
            <AppIcon name="alert" :size="16" />
            {{ form.errors.otp }}
          </div>

          <!-- OTP Form -->
          <form @submit.prevent="submit" class="mhr-otp-form">
            <div class="mhr-otp-input-wrapper">
              <OtpInput
                ref="otpInput"
                v-model="form.otp"
                :length="length"
                :has-error="!!form.errors.otp"
                @complete="submit"
              />
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              class="mhr-btn mhr-btn--primary mhr-btn--block"
              :disabled="form.otp.length < length || form.processing"
              :style="(form.otp.length < length || form.processing) ? 'opacity:0.6;cursor:not-allowed;' : ''"
            >
              <span v-if="form.processing" style="display:flex;align-items:center;gap:8px;justify-content:center;">
                <svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" opacity="0.25"/>
                  <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"/>
                </svg>
                Verifying...
              </span>
              <span v-else style="display:flex;align-items:center;gap:8px;justify-content:center;">
                <AppIcon name="check" :size="16" />
                Confirm
              </span>
            </button>
          </form>

          <!-- Actions -->
          <div class="mhr-otp-actions">
            <p class="mhr-otp-resend">
              Didn't receive the code?
              <button
                type="button"
                class="mhr-link"
                @click="resend"
                :disabled="isResending"
              >
                {{ isResending ? 'Sending...' : 'Resend code' }}
              </button>
            </p>
            
            <Link :href="route('login')" class="mhr-otp-back">
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

.mhr-auth-brand__dot {
  opacity: 0.5;
  margin: 0 2px;
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

.mhr-otp-icon {
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

.mhr-otp-error {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  background: var(--mhr-danger-bg);
  border: 1px solid var(--mhr-danger);
  border-radius: var(--mhr-r);
  color: var(--mhr-danger);
  font-size: 14px;
  margin-bottom: 24px;
  font-weight: 500;
}

.mhr-otp-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.mhr-otp-input-wrapper {
  display: flex;
  justify-content: center;
}

/* Override OtpInput styles for Meridian */
.mhr-otp-input-wrapper :deep(input) {
  width: 64px !important;
  max-width: 64px !important;
  height: 64px !important;
  border: 2px solid var(--mhr-line) !important;
  border-radius: var(--mhr-r) !important;
  background: var(--mhr-surface) !important;
  color: var(--mhr-ink) !important;
  font-size: 24px !important;
  font-weight: 600 !important;
  text-align: center !important;
  transition: all 0.15s !important;
  padding: 0 !important;
}

.mhr-otp-input-wrapper :deep(input:focus) {
  border-color: var(--mhr-accent) !important;
  outline: none !important;
  box-shadow: 0 0 0 3px var(--mhr-accent-soft) !important;
}

.mhr-otp-input-wrapper :deep(input.is-invalid) {
  border-color: var(--mhr-danger) !important;
}

.mhr-otp-input-wrapper :deep(.d-flex) {
  gap: 12px !important;
}

.mhr-btn--block {
  width: 100%;
  justify-content: center;
  margin-top: 8px;
}

.mhr-otp-actions {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--mhr-line);
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.mhr-otp-resend {
  font-size: 14px;
  color: var(--mhr-ink-3);
  margin: 0;
}

.mhr-link {
  border: none;
  background: none;
  padding: 0;
  font-size: 14px;
  color: var(--mhr-accent);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s;
  cursor: pointer;
}

.mhr-link:hover {
  color: var(--mhr-accent-ink);
  text-decoration: underline;
}

.mhr-link:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mhr-otp-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: var(--mhr-ink-3);
  text-decoration: none;
  transition: color 0.15s;
}

.mhr-otp-back:hover {
  color: var(--mhr-ink);
}
</style>
