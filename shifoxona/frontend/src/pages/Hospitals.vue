<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { api, queryString } from '../lib/api';
import { auth, lookups, loadLookups, notify, confirmDelete } from '../lib/state';
import Field from '../components/Field.vue';
import Icon from '../components/Icon.vue';
import Modal from '../components/Modal.vue';
import Pagination from '../components/Pagination.vue';
const search = ref('');
const data = ref(null);
const busy = ref(true);
const error = ref('');
const editor = ref(false);
const editingId = ref(null);
const saving = ref(false);
const detail = ref(null);
const errors = ref({});
const formError = ref('');
const form = reactive({ name: '', region_id: '', district_id: '', tin: '' });
const districts = computed(() =>
  lookups.districts.filter((d) => d.region_id === Number(form.region_id)),
);
let timer;
let requestId = 0;
let ready = false;
watch(
  () => form.region_id,
  () => {
    if (!districts.value.some((d) => d.id === Number(form.district_id))) {
      form.district_id = '';
    }
  },
);
async function load(page = 1) {
  const id = ++requestId;
  busy.value = true;
  error.value = '';
  try {
    const result = await api(`/hospitals?${queryString({ search: search.value, page })}`);
    if (id === requestId) {
      data.value = result;
    }
  } catch (e) {
    if (id === requestId) {
      error.value = e.message;
    }
  } finally {
    if (id === requestId) {
      busy.value = false;
    }
  }
}
watch(search, () => {
  if (!ready) {
    return;
  }
  clearTimeout(timer);
  timer = setTimeout(() => load(), 250);
});
onMounted(async () => {
  try {
    await loadLookups();
    ready = true;
    await load();
  } catch (e) {
    error.value = e.message;
    busy.value = false;
  }
});
onBeforeUnmount(() => {
  clearTimeout(timer);
  requestId++;
});
function edit(hospital = null) {
  editingId.value = hospital?.id || null;
  Object.assign(form, {
    name: hospital?.name || '',
    region_id: hospital?.region_id || '',
    district_id: hospital?.district_id || '',
    tin: hospital?.tin || '',
  });
  errors.value = {};
  formError.value = '';
  editor.value = true;
}
async function show(hospital) {
  try {
    detail.value = await api(`/hospitals/${hospital.id}`);
  } catch (e) {
    notify(e.message, 'error');
  }
}
async function save() {
  saving.value = true;
  errors.value = {};
  formError.value = '';
  try {
    await api(`/hospitals${editingId.value ? `/${editingId.value}` : ''}`, {
      method: editingId.value ? 'PUT' : 'POST',
      body: form,
    });
    editor.value = false;
    notify('Shifoxona ma’lumotlari saqlandi.');
    await loadLookups();
    await load();
  } catch (e) {
    errors.value = e.errors || {};
    formError.value = Object.keys(errors.value).length
      ? 'Belgilangan maydonlarni tekshiring.'
      : e.message;
  } finally {
    saving.value = false;
  }
}
async function remove(hospital) {
  const confirmed = confirmDelete(
    'Shifoxonani o‘chirish',
    `${hospital.name} o‘chiriladi. Xodimlari yoki bemorlari mavjud shifoxonani o‘chirib bo‘lmaydi.`,
  );
  if (!confirmed) {
    return;
  }
  try {
    await api(`/hospitals/${hospital.id}`, { method: 'DELETE' });
    notify('Shifoxona o‘chirildi.');
    await loadLookups();
    await load();
  } catch (e) {
    notify(Object.values(e.errors || {}).flat()[0] || e.message, 'error');
  }
}
</script>
<template>
  <div class="page-heading">
    <div>
      <span class="eyebrow">TIBBIYOT MUASSASALARI</span>
      <h1>{{ auth.user.role === 'director' ? 'Mening shifoxonam' : 'Shifoxonalar' }}</h1>
      <p>Shifoxonalar va ularning rahbarlari haqida ma’lumotlar.</p>
    </div>
    <button v-if="auth.user.role === 'admin'" class="button primary" @click="edit()">
      <Icon name="Plus" :size="19" />
      Shifoxona qo‘shish
    </button>
  </div>
  <div class="standalone-search search-input">
    <Icon name="Search" :size="19" />
    <input
      v-model="search"
      placeholder="Shifoxona nomi bo‘yicha qidirish…"
      aria-label="Shifoxona qidirish"
    />
  </div>
  <div v-if="error" class="alert error" role="alert">
    {{ error }}
    <button class="button secondary" @click="load">Qayta urinish</button>
  </div>
  <div v-else-if="busy" class="loading-state">
    <Icon name="LoaderCircle" class="spin" />
    Shifoxonalar yuklanmoqda…
  </div>
  <template v-else>
    <div v-if="data?.data.length" class="hospital-grid">
      <article v-for="hospital in data.data" :key="hospital.id" class="panel hospital-card">
        <div class="hospital-card-top">
          <span class="hospital-icon"><Icon name="Building2" :size="28" /></span>
          <span class="subtle-pill">STIR {{ hospital.tin }}</span>
        </div>
        <button class="hospital-title" @click="show(hospital)">{{ hospital.name }}</button>
        <p class="hospital-location">
          <Icon name="MapPin" :size="16" />
          {{ hospital.region?.name }}, {{ hospital.district?.name }}
        </p>
        <div class="hospital-director">
          <span class="field-hint">SHIFOXONA RAHBARI</span>
          <template v-if="hospital.directors?.length">
            <div v-for="d in hospital.directors" :key="d.id">
              <strong>{{ d.name }}</strong>
              <small>
                {{ d.email }}
                <span v-if="d.phone">· {{ d.phone }}</span>
              </small>
            </div>
          </template>
          <p v-else class="muted">Rahbar tayinlanmagan</p>
        </div>
        <div class="hospital-counts">
          <RouterLink :to="`/doctors?hospital_id=${hospital.id}`">
            <Icon name="Stethoscope" :size="18" />
            <strong>{{ hospital.doctors_count }}</strong>
            shifokor
          </RouterLink>
          <span>
            <Icon name="Users" :size="18" />
            <strong>{{ hospital.patients_count }}</strong>
            bemor
          </span>
        </div>
        <div class="hospital-card-actions">
          <button class="text-link" @click="show(hospital)">
            Batafsil
            <Icon name="ArrowRight" :size="16" />
          </button>
          <div class="table-actions">
            <button
              class="icon-button"
              :aria-label="`${hospital.name}ni tahrirlash`"
              @click="edit(hospital)"
            >
              <Icon name="Pencil" :size="17" />
            </button>
            <button
              v-if="auth.user.role === 'admin'"
              class="icon-button red"
              :aria-label="`${hospital.name}ni o‘chirish`"
              @click="remove(hospital)"
            >
              <Icon name="Trash2" :size="17" />
            </button>
          </div>
        </div>
      </article>
    </div>
    <div v-else class="panel empty-state">
      <Icon name="Building2" :size="36" />
      <h3>Shifoxonalar topilmadi</h3>
      <p>Qidiruvni o‘zgartiring yoki yangi shifoxona qo‘shing.</p>
    </div>
    <Pagination :data="data" :busy="busy" @page="load" />
  </template>
  <Modal
    :open="editor"
    :title="editingId ? 'Shifoxonani tahrirlash' : 'Shifoxona qo‘shish'"
    wide
    @close="
      () => {
        if (!saving) editor = false;
      }
    "
  >
    <form @submit.prevent="save">
      <div v-if="formError" class="alert error" role="alert">{{ formError }}</div>
      <div class="form-grid">
        <Field label="Shifoxona nomi" class="full-width" :error="errors.name" required>
          <input
            v-model="form.name"
            placeholder="Shifoxona nomini kiriting"
            maxlength="200"
            required
            autofocus
          />
        </Field>
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
        <Field
          label="STIR (TIN)"
          :error="errors.tin"
          hint="9 xonali soliq to‘lovchi raqami"
          required
        >
          <input
            v-model="form.tin"
            class="mono"
            placeholder="000000000"
            inputmode="numeric"
            maxlength="9"
            pattern="[0-9]{9}"
            required
          />
        </Field>
      </div>
      <div v-if="!editingId" class="alert info">
        <Icon name="ShieldCheck" :size="18" />
        Shifoxona saqlangach, «Rahbarlar» bo‘limidan rahbar hisobini yarating.
      </div>
      <div class="form-actions modal-actions">
        <button class="button secondary" type="button" :disabled="saving" @click="editor = false">
          Bekor qilish
        </button>
        <button class="button primary" :disabled="saving">
          <Icon :name="saving ? 'LoaderCircle' : 'Check'" :class="{ spin: saving }" :size="18" />
          {{ saving ? 'Saqlanmoqda…' : 'Saqlash' }}
        </button>
      </div>
    </form>
  </Modal>
  <Modal :open="!!detail" title="Shifoxona ma’lumotlari" @close="detail = null">
    <template v-if="detail">
      <div class="staff-profile">
        <span class="hospital-icon"><Icon name="Building2" :size="30" /></span>
        <h2>{{ detail.name }}</h2>
        <p>STIR: {{ detail.tin }}</p>
      </div>
      <dl class="detail-list">
        <div>
          <dt>Viloyat</dt>
          <dd>{{ detail.region?.name }}</dd>
        </div>
        <div>
          <dt>Tuman</dt>
          <dd>{{ detail.district?.name }}</dd>
        </div>
        <div>
          <dt>Shifokorlar</dt>
          <dd>{{ detail.doctors_count }}</dd>
        </div>
        <div>
          <dt>Bemorlar</dt>
          <dd>{{ detail.patients_count }}</dd>
        </div>
        <div>
          <dt>Rahbarlar</dt>
          <dd>
            <span v-if="!detail.directors?.length">Tayinlanmagan</span>
            <span v-for="d in detail.directors" :key="d.id" class="director-detail">
              {{ d.name }}
              <small>
                {{ d.email }}
                <br />
                {{ d.phone || '—' }}
              </small>
            </span>
          </dd>
        </div>
      </dl>
      <div class="form-actions modal-actions">
        <button class="button secondary" @click="detail = null">Yopish</button>
        <button
          class="button primary"
          @click="
            edit(detail);
            detail = null;
          "
        >
          <Icon name="Pencil" :size="17" />
          Tahrirlash
        </button>
      </div>
    </template>
  </Modal>
</template>
