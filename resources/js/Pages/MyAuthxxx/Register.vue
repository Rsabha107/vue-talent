<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthFooter from "@/Components/AuthFooter.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

const validated = ref(false);

const form = useForm({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

function submit(e) {
  const htmlForm = e.currentTarget;

  if (!htmlForm.checkValidity()) {
    e.preventDefault();
    e.stopPropagation();
    validated.value = true;
    return;
  }

  validated.value = true;

  form.post(route("register"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
}
</script>
<template>
  <div class="account-pages my-5 pt-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <div class="card overflow-hidden">
            <div class="bg-primary-subtle">
              <div class="row">
                <div class="col-7">
                  <div class="text-primary p-4">
                    <h5 class="text-primary">Register</h5>
                    <p>Get your account now.</p>
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
                <form
                  @submit.prevent="submit"
                  class="needs-validation"
                  :class="{ 'was-validated': validated }"
                  novalidate
                >
                  <div class="mb-3">
                    <label class="form-label" for="useremail">Email</label>
                    <input
                      class="form-control"
                      id="useremail"
                      placeholder="Enter email"
                      type="email"
                      v-model="form.email"
                      required
                      :class="{ 'is-invalid': form.errors.email }"
                    />
                    <div class="invalid-feedback" v-if="form.errors.email">
                      {{ form.errors.email }}
                    </div>
                    <div class="invalid-feedback" v-else>
                      Please enter a valid email.
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input
                      class="form-control"
                      id="username"
                      placeholder="Enter username"
                      type="text"
                      v-model="form.name"
                      required
                      :class="{ 'is-invalid': form.errors.name }"
                    />
                    <div class="invalid-feedback" v-if="form.errors.name">
                      {{ form.errors.name }}
                    </div>
                    <div class="invalid-feedback" v-else>
                      Please enter a username.
                    </div>
                  </div>

                  <PasswordInput
                    v-model="form.password"
                    :error="form.errors.password"
                    label="Password"
                    placeholder="Enter password"
                    id="userpassword"
                  />

                  <PasswordInput
                    v-model="form.password_confirmation"
                    :error="form.errors.password_confirmation"
                    label="Confirm Password"
                    placeholder="Confirm password"
                    id="userpassword_confirmation"
                  />

                  <div class="mt-4 d-grid">
                    <button
                      class="btn btn-primary waves-effect waves-light"
                      type="submit"
                      :disabled="form.processing"
                    >
                      {{ form.processing ? "Registering..." : "Register" }}
                    </button>
                  </div>
                  <div class="mt-4 text-center">
                    <h5 class="font-size-14 mb-3">Sign up using</h5>
                    <ul class="list-inline">
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-primary text-white border-primary"
                          href="javascript::void()"
                        >
                          <i class="mdi mdi-facebook"> </i>
                        </a>
                      </li>
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-info text-white border-info"
                          href="javascript::void()"
                        >
                          <i class="mdi mdi-twitter"> </i>
                        </a>
                      </li>
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-danger text-white border-danger"
                          href="javascript::void()"
                        >
                          <i class="mdi mdi-google"> </i>
                        </a>
                      </li>
                    </ul>
                  </div>
                  <div class="mt-4 text-center">
                    <p class="mb-0">
                      By registering you agree to the
                      <a class="text-primary" href="#">Terms of Use</a>
                    </p>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="mt-5 text-center">
            <div>
              <p>
                Already have an account?
                <Link class="fw-medium text-primary" :href="route('login')">
                  Login
                </Link>
              </p>
              <!-- <AuthFooter /> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
