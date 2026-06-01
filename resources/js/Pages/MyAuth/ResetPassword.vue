<script setup>
import { useForm } from "@inertiajs/vue3";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  token: String,
  email: String,
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
}).dontRemember('password', 'password_confirmation')


const submit = () => form.post("/reset-password");
</script>

<template>
  <Head title="Forgot Password" />
  <div class="account-pages my-5 pt-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <div class="card overflow-hidden">
            <div class="bg-primary-subtle">
              <div class="row">
                <div class="col-7">
                  <div class="text-primary p-4">
                    <h5 class="text-primary">Reset Password</h5>
                    <p>Enter your email to receive password reset instructions.</p>
                  </div>
                </div>
                <div class="col-5 align-self-end">
                  <img
                    alt=""
                    class="img-fluid"
                    src="/assets/images/profile-img.png"
                  />
                </div>
              </div>
            </div>
            <div class="card-body pt-0">
              <div>
                <Link :href="route('mypage')">
                  <div class="avatar-md profile-user-wid mb-4">
                    <span class="avatar-title rounded-circle bg-light">
                      <img
                        alt=""
                        class="rounded-circle"
                        height="34"
                        src="/assets/images/logo.svg"
                      />
                    </span>
                  </div>
                </Link>
              </div>
              <div class="p-2">
                <!-- <div class="alert alert-success text-center mb-4" role="alert">
                  Enter your Email and instructions will be sent to you!
                </div> -->
                <!-- <form action="index.html" class="form-horizontal"> -->
                <form @submit.prevent="submit">
                  <div v-if="form.errors.email" class="alert alert-danger" role="alert">
                    {{ form.errors.email }}
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="useremail"> Email </label>
                    <input
                      v-model="form.email"
                      :class="['form-control', { 'is-invalid': form.errors.email }]"
                      id="useremail"
                      placeholder="Enter email"
                      type="email"
                      required
                    />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="userpassword"> Password </label>
                    <input
                      v-model="form.password"
                      :class="['form-control', { 'is-invalid': form.errors.password }]"
                      id="userpassword"
                      placeholder="Enter password"
                      type="password"
                      required
                    />
                    <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="userpassword_confirmation"> Confirm Password </label>
                    <input
                      v-model="form.password_confirmation"
                      :class="['form-control', { 'is-invalid': form.errors.password_confirmation }]"
                      id="userpassword_confirmation"
                      placeholder="Enter password confirmation"
                      type="password"
                      required
                    />
                    <div v-if="form.errors.password_confirmation" class="invalid-feedback">{{ form.errors.password_confirmation }}</div>
                  </div>
                  <div class="text-end">
                                        
                    <button
                      class="btn btn-primary w-md waves-effect waves-light"
                      type="submit"
                      :disabled="form.processing"
                    >
                      <span
                        v-if="form.processing"
                        class="spinner-border spinner-border-sm me-1"
                        role="status"
                        aria-hidden="true"
                      ></span>
                      {{ form.processing ? 'Resetting...' : 'Reset Password' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="mt-5 text-center">
            <p>
              Remember It ?
              <Link class="fw-medium text-primary" :href="route('login')">
                Sign In here
              </Link>
            </p>
            <!-- <AuthFooter /> -->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>