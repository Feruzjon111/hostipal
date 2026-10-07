<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import { api } from '../lib/api';
import { auth, lookups, loadLookups, notify } from '../lib/state';
import { statuses, inputDate } from '../lib/format';
import Field from '../components/Field.vue';
import Icon from '../components/Icon.vue';
const route = useRoute();
const router = useRouter();
const editing = computed(() => !!route.params.id);
const form = reactive({
  first_name: '',
  last_name: '',
  middle_name: '',
  passport: '',
  pin: '',
  region_id: '',
  district_id: '',
  address: '',
  disease_id: '',
  doctor_id: '',
  registered_at: inputDate(),
  status: 'waiting',
  notes: '',
});
const errors = ref({});
const error = ref('');
const busy = ref(false);
const loading = ref(true);
const initial = ref('');
const districts = computed(() =>
  lookups.districts.filter((d) => d.region_id === Number(form.region_id)),
);
const dirty = computed(() => initial.value && JSON.stringify(form) !== initial.value);
watch(
  () => form.region_id,
  () => {
    if (!districts.value.some((d) => d.id === Number(form.district_id))) {
      form.district_id = '';
    }
  },
);
onMounted(async () => {
  try {
    await loadLookups();
    if (editing.value) {
      const patient = await api(`/patients/${route.params.id}`);
      for (const key of Object.keys(form)) form[key] = patient[key] ?? '';
      form.registered_at = inputDate(patient.registered_at);
    }
    initial.value = JSON.stringify(form);
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});
async function submit() {
  busy.value = true;
  errors.value = {};
  error.value = '';
  try {
    const data = await api(editing.value ? `/patients/${route.params.id}` : '/patients', {
      method: editing.value ? 'PUT' : 'POST',
      body: form,
    });
    initial.value = JSON.stringify(form);
    notify(
      editing.value ? 'Bemor ma’lumotlari yangilandi.' : 'Bemor muvaffaqiyatli ro‘yxatga olindi.',
    );
    await router.push(`/patients/${data.id}`);
  } catch (e) {
    errors.value = e.errors || {};
    error.value = Object.keys(errors.value).length
      ? 'Belgilangan maydonlarni tekshirib, qayta saqlang.'
      : e.message;
  } finally {
    busy.value = false;
  }
}
function beforeUnload(event) {
  if (dirty.value) {
    event.preventDefault();
    event.returnValue = '';
  }
}
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload));
onBeforeRouteLeave(
  () => !dirty.value || window.confirm('Saqlanmagan o‘zgarishlar mavjud. Sahifadan chiqilsinmi?'),
);
</script>
<template>
  <RouterLink :to="editing ? `/patients/${route.params.id}` : '/patients'" class="back-link">
    <Icon name="ArrowLeft" :size="17" />
    {{ editing ? 'Bemor kartasiga qaytish' : 'Bemorlar ro‘yxatiga qaytish' }}
  </RouterLink>
  <div class="page-heading">
    <div>
      <span class="eyebrow">BEMOR KARTASI</span>
      <h1>{{ editing ? 'Bemorni tahrirlash' : 'Yangi bemor qo‘shish' }}</h1>
      <p>Shaxsiy ma’lumotlar va qabul tafsilotlarini kiriting.</p>
    </div>
    <span class="subtle-pill">
      <Icon name="ShieldCheck" :size="16" />
      Himoyalangan ma’lumotlar
    </span>
  </div>
  <div v-if="loading" class="loading-state">
    <Icon name="LoaderCircle" class="spin" />
    Ma’lumotlar yuklanmoqda…
  </div>
  <div v-else-if="error && !initial" class="alert error" role="alert">{{ error }}</div>
  <form v-else class="patient-form" @submit.prevent="submit">
    <div v-if="error" class="alert error" role="alert">
      <Icon name="AlertCircle" :size="18" />
      {{ error }}
    </div>
    <section class="panel form-section">
      <div class="section-title">
        <span class="section-icon"><Icon name="Users" :size="21" /></span>
        <div>
          <h2>Shaxsiy ma’lumotlar</h2>
          <p>Bemorning asosiy va hujjat ma’lumotlari</p>
        </div>
        <span class="section-number">01</span>
      </div>
      <div class="form-grid three">
        <Field label="Ism" :error="errors.first_name" required>
          <input v-model="form.first_name" placeholder="Bemor ismi" maxlength="100" required />
        </Field>
        <Field label="Familiya" :error="errors.last_name" required>
          <input v-model="form.last_name" placeholder="Bemor familiyasi" maxlength="100" required />
        </Field>
        <Field label="Otasining ismi" :error="errors.middle_name" required>
          <input v-model="form.middle_name" placeholder="Otasining ismi" maxlength="100" required />
        </Field>
        <Field
          label="Pasport"
          hint="2 ta harf va 7 ta raqam: AA0000000"
          :error="errors.passport"
          required
        >
          <input
            v-model="form.passport"
            class="mono uppercase"
            placeholder="AA0000000"
            maxlength="9"
            pattern="[A-Za-z]{2}[0-9]{7}"
            required
            @input="form.passport = form.passport.toUpperCase()"
          />
        </Field>
        <Field
          label="JShShIR (PIN)"
          hint="14 xonali shaxsiy identifikatsiya raqami"
          :error="errors.pin"
          required
        >
          <input
            v-model="form.pin"
            class="mono"
            placeholder="00000000000000"
            inputmode="numeric"
            maxlength="14"
            pattern="[0-9]{14}"
            required
          />
        </Field>
      </div>
    </section>
    <section class="panel form-section">
      <div class="section-title">
        <span class="section-icon"><Icon name="MapPin" :size="21" /></span>
        <div>
          <h2>Yashash manzili</h2>
          <p>Viloyat, tuman va to‘liq manzil</p>
        </div>
        <span class="section-number">02</span>
      </div>
      <div class="form-grid">
        <Field label="Viloyat" :error="errors.region_id" required>
          <select v-model="form.region_id" required>
            <option value="" disabled>Viloyatni tanlang</option>
            <option v-for="r in lookups.regions" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </Field>
        <Field label="Tuman / shahar" :error="errors.district_id" required>
          <select v-model="form.district_id" :disabled="!form.region_id" required>
            <option value="" disabled>Tumanni tanlang</option>
            <option v-for="d in districts" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </Field>
        <Field label="Manzil" class="full-width" :error="errors.address" required>
          <input
            v-model="form.address"
            placeholder="Mahalla, ko‘cha, uy va xonadon"
            maxlength="500"
            required
          />
        </Field>
      </div>
    </section>
    <section class="panel form-section">
      <div class="section-title">
        <span class="section-icon"><Icon name="Stethoscope" :size="21" /></span>
        <div>
          <h2>Qabul ma’lumotlari</h2>
          <p>Kasallik turi va qabul tafsilotlari</p>
        </div>
        <span class="section-number">03</span>
      </div>
      <div class="form-grid">
        <Field
          v-if="auth.user.role === 'admin'"
          label="Shifokor"
          :error="errors.doctor_id"
          required
        >
          <select v-model="form.doctor_id" required>
            <option value="" disabled>Shifokorni tanlang</option>
            <option v-for="d in lookups.doctors" :key="d.id" :value="d.id">
              {{ d.name }} · {{ lookups.hospitals.find((h) => h.id === d.hospital_id)?.name }}
            </option>
          </select>
        </Field>
        <Field label="Kasallik turi" :error="errors.disease_id" required>
          <select v-model="form.disease_id" required>
            <option value="" disabled>Kasallik turini tanlang</option>
            <option v-for="d in lookups.diseases" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </Field>
        <Field
          label="Qabul sanasi va vaqti"
          hint="Toshkent vaqti (UTC+5)"
          :error="errors.registered_at"
          required
        >
          <input v-model="form.registered_at" type="datetime-local" required />
        </Field>
        <Field label="Qabul holati" :error="errors.status" required>
          <select v-model="form.status" required>
            <option v-for="(label, value) in statuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </Field>
        <Field label="Qo‘shimcha izoh" class="full-width" :error="errors.notes">
          <textarea
            v-model="form.notes"
            placeholder="Zarur bo‘lsa, qo‘shimcha ma’lumot kiriting…"
            maxlength="5000"
            rows="4"
          />
        </Field>
      </div>
    </section>
    <div class="form-bottom">
      <span class="muted">
        <span class="required">*</span>
        Majburiy maydonlar
      </span>
      <div class="form-actions">
        <RouterLink
          :to="editing ? `/patients/${route.params.id}` : '/patients'"
          class="button secondary"
        >
          Bekor qilish
        </RouterLink>
        <button class="button primary" :disabled="busy">
          <Icon :name="busy ? 'LoaderCircle' : 'Check'" :class="{ spin: busy }" :size="18" />
          {{
            busy ? 'Saqlanmoqda…' : editing ? 'O‘zgarishlarni saqlash' : 'Bemorni ro‘yxatga olish'
          }}
        </button>
      </div>
    </div>
  </form>
</template>
