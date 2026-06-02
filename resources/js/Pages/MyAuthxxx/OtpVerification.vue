<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import OtpInput from '@/Components/OtpInput.vue'

const props = defineProps({
  email: { type: String, required: true },
  length: { type: Number, default: 4 },
})

const otpInput = ref(null)

const form = useForm({ otp: '' })

function submit() {
  if (form.otp.length < props.length) return
  form.post(route('otp.verify'), {
    onError: () => { form.otp = ''; otpInput.value?.focus() },
  })
}

function resend() {
  useForm({}).post(route('otp.resend'), {
    onSuccess: () => { form.otp = ''; otpInput.value?.focus() },
  })
}
</script>

<template>
  <Head title="Two-Step Verification" />
  <div class="account-pages my-5 pt-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <div class="card">
            <div class="card-body">
              <div class="p-2">
                <div class="text-center">
                  <div class="avatar-md mx-auto">
                    <div class="avatar-title rounded-circle bg-light">
                      <i class="bx bxs-envelope h1 mb-0 text-primary"></i>
                    </div>
                  </div>

                  <div class="p-2 mt-4">
                    <h4>Verify your email</h4>
                    <p class="mb-5">
                      Please enter the {{ length }}-digit code sent to
                      <span class="fw-semibold">{{ email }}</span>
                    </p>

                    <div v-if="form.errors.otp" class="alert alert-danger mb-4" role="alert">
                      {{ form.errors.otp }}
                    </div>

                    <form @submit.prevent="submit">
                      <OtpInput
                        ref="otpInput"
                        v-model="form.otp"
                        :length="length"
                        :has-error="!!form.errors.otp"
                        @complete="submit"
                      />

                      <div class="mt-4">
                        <button
                          type="submit"
                          class="btn btn-success w-md waves-effect waves-light"
                          :disabled="form.otp.length < length || form.processing"
                        >
                          <span
                            v-if="form.processing"
                            class="spinner-border spinner-border-sm me-1"
                            role="status"
                            aria-hidden="true"
                          ></span>
                          {{ form.processing ? 'Verifying...' : 'Confirm' }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 text-center">
            <p>
              Didn't receive a code?
              <button
                type="button"
                class="btn btn-link p-0 fw-medium text-primary"
                @click="resend"
              >
                Resend
              </button>
            </p>
            <p>
              <Link class="text-muted" :href="route('login')">
                <i class="mdi mdi-arrow-left me-1"></i> Back to Login
              </Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
