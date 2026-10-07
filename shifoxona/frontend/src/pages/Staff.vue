<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import { api, queryString } from '../lib/api';
import { auth, lookups, loadLookups, notify, confirmDelete } from '../lib/state';
import { initials } from '../lib/format';
import Icon from '../components/Icon.vue';
import Field from '../components/Field.vue';
import Modal from '../components/Modal.vue';
import Pagination from '../components/Pagination.vue';
const props = defineProps({ type: String });
const route = useRoute();
const isDoctor = computed(() => props.type === 'doctors');
const title = computed(() => (isDoctor.value ? 'Shifokorlar' : 'Rahbarlar'));
const singular = computed(() => (isDoctor.value ? 'Shifokor' : 'Rahbar'));
const data = ref(null);
const busy = ref(true);
const error = ref('');
const saving = ref(false);
const detail = ref(null);
const editor = ref(false);
const editingId = ref(null);
const formError = ref('');
const errors = ref({});
const filter = reactive({ search: '', hospital_id: route.query.hospital_id || '' });
const form = reactive({
  name: '',
  email: '',
  password: '',
  phone: '',
  specialization: '',
  hospital_id: '',
});
let timer;
let requestId = 0;
let ready = false;
async function load(page = 1) {
  const id = ++requestId;
  busy.value = true;
  error.value = '';
  try {
    const result = await api(`/${props.type}?${queryString({ ...filter, page })}`);
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
watch(filter, () => {
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
function edit(staff = null) {
  editingId.value = staff?.id || null;
  Object.assign(form, {
    name: staff?.name || '',
    email: staff?.email || '',
    password: '',
    phone: staff?.phone || '',
    specialization: staff?.specialization || '',
    hospital_id: staff?.hospital_id || auth.user.hospital_id || '',
  });
  errors.value = {};
  formError.value = '';
  editor.value = true;
}
async function show(staff) {
  try {
    detail.value = await api(`/${props.type}/${staff.id}`);
  } catch (e) {
    notify(e.message, 'error');
  }
}
async function save() {
  saving.value = true;
  errors.value = {};
  formError.value = '';
  try {
    await api(`/${props.type}${editingId.value ? `/${editingId.value}` : ''}`, {
      method: editingId.value ? 'PUT' : 'POST',
      body: form,
    });
    editor.value = false;
    notify(`${singular.value} ma’lumotlari saqlandi.`);
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
async function remove(staff) {
  const confirmed = confirmDelete(
    `${singular.value}ni o‘chirish`,
    `${staff.name} hisobi o‘chiriladi. Bemor kartalari mavjud shifokorni o‘chirib bo‘lmaydi.`,
  );
  if (!confirmed) {
    return;
  }
  try {
    await api(`/${props.type}/${staff.id}`, { method: 'DELETE' });
    notify('Xodim hisobi o‘chirildi.');
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
      <span class="eyebrow">{{ isDoctor ? 'TIBBIYOT JAMOASI' : 'SHIFOXONA BOSHQARUVI' }}</span>
      <h1>{{ title }}</h1>
      <p>
        {{
          isDoctor
            ? 'Shifokorlar va ularning hisoblarini boshqaring.'
            : 'Shifoxona rahbarlarini tayinlang va hisoblarini boshqaring.'
        }}
      </p>
    </div>
    <button class="button primary" @click="edit()">
      <Icon name="Plus" :size="19" />
      {{ singular }} qo‘shish
    </button>
  </div>
  <section class="panel">
    <div class="filters">
      <div class="search-input">
        <Icon name="Search" :size="19" />
        <input
          v-model="filter.search"
          :placeholder="`${singular} ismi yoki email bo‘yicha qidirish…`"
          :aria-label="`${singular} qidirish`"
        />
      </div>
      <select
        v-if="auth.user.role === 'admin'"
        v-model="filter.hospital_id"
        aria-label="Shifoxona bo‘yicha filtrlash"
      >
        <option value="">Barcha shifoxonalar</option>
        <option v-for="h in lookups.hospitals" :key="h.id" :value="h.id">{{ h.name }}</option>
      </select>
    </div>
    <div v-if="error" class="alert error page-alert" role="alert">
      {{ error }}
      <button class="button secondary" @click="load">Qayta urinish</button>
    </div>
    <div v-else-if="busy" class="loading-state">
      <Icon name="LoaderCircle" class="spin" />
      Ma’lumotlar yuklanmoqda…
    </div>
    <template v-else>
      <div v-if="data?.data.length" class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>{{ singular }}</th>
              <th>Shifoxona</th>
              <th v-if="isDoctor">Mutaxassislik</th>
              <th>Aloqa</th>
              <th v-if="isDoctor">Bemorlar</th>
              <th>Amallar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="staff in data.data" :key="staff.id">
              <td>
                <button class="person person-button" @click="show(staff)">
                  <span class="avatar small" :class="`tone-${staff.id % 4}`">
                    {{ initials(staff.name) }}
                  </span>
                  <span>
                    <strong>{{ staff.name }}</strong>
                    <small>{{ staff.email }}</small>
                  </span>
                </button>
              </td>
              <td>{{ staff.hospital?.name }}</td>
              <td v-if="isDoctor">{{ staff.specialization }}</td>
              <td class="nowrap">{{ staff.phone || '—' }}</td>
              <td v-if="isDoctor">
                <span class="count-pill">{{ staff.patients_count }}</span>
              </td>
              <td>
                <div class="table-actions">
                  <button
                    class="icon-button"
                    :aria-label="`${staff.name}ni ko‘rish`"
                    @click="show(staff)"
                  >
                    <Icon name="Eye" :size="17" />
                  </button>
                  <button
                    class="icon-button"
                    :aria-label="`${staff.name}ni tahrirlash`"
                    @click="edit(staff)"
                  >
                    <Icon name="Pencil" :size="17" />
                  </button>
                  <button
                    class="icon-button red"
                    :aria-label="`${staff.name}ni o‘chirish`"
                    @click="remove(staff)"
                  >
                    <Icon name="Trash2" :size="17" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon :name="isDoctor ? 'Stethoscope' : 'ShieldCheck'" :size="36" />
        <h3>{{ title }} topilmadi</h3>
        <p>Qidiruvni o‘zgartiring yoki yangi hisob qo‘shing.</p>
      </div>
    </template>
    <Pagination :data="data" :busy="busy" @page="load" />
  </section>
  <Modal
    :open="editor"
    :title="`${singular}${editingId ? 'ni tahrirlash' : ' qo‘shish'}`"
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
        <Field label="To‘liq ism" :error="errors.name" required>
          <input
            v-model="form.name"
            placeholder="Ism va familiya"
            maxlength="150"
            required
            autofocus
          />
        </Field>
        <Field label="Email" :error="errors.email" required>
          <input
            v-model="form.email"
            type="email"
            placeholder="shifokor@shifo.uz"
            autocomplete="off"
            maxlength="150"
            required
          />
        </Field>
        <Field
          label="Parol"
          :required="!editingId"
          :error="errors.password"
          :hint="editingId ? 'Bo‘sh qoldirsangiz, amaldagi parol saqlanadi.' : 'Kamida 8 ta belgi.'"
        >
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            minlength="8"
            maxlength="72"
            :required="!editingId"
            placeholder="Kamida 8 ta belgi"
          />
        </Field>
        <Field label="Telefon" :error="errors.phone">
          <input v-model="form.phone" type="tel" placeholder="+998 90 123 45 67" maxlength="30" />
        </Field>
        <Field
          v-if="auth.user.role === 'admin'"
          label="Shifoxona"
          :error="errors.hospital_id"
          required
        >
          <select v-model="form.hospital_id" required>
            <option value="" disabled>Shifoxonani tanlang</option>
            <option v-for="h in lookups.hospitals" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </Field>
        <Field v-if="isDoctor" label="Mutaxassislik" :error="errors.specialization" required>
          <input
            v-model="form.specialization"
            placeholder="Masalan, terapevt"
            maxlength="150"
            required
          />
        </Field>
      </div>
      <div v-if="auth.user.role === 'director'" class="alert info">
        <Icon name="Building2" :size="18" />
        Hisob {{ auth.user.hospital?.name }} shifoxonasiga biriktiriladi.
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
  <Modal :open="!!detail" :title="`${singular} ma’lumotlari`" @close="detail = null">
    <template v-if="detail">
      <div class="staff-profile">
        <span class="avatar large">{{ initials(detail.name) }}</span>
        <h2>{{ detail.name }}</h2>
        <p>{{ detail.specialization || 'Shifoxona rahbari' }}</p>
      </div>
      <dl class="detail-list">
        <div>
          <dt>Email</dt>
          <dd>{{ detail.email }}</dd>
        </div>
        <div>
          <dt>Telefon</dt>
          <dd>{{ detail.phone || '—' }}</dd>
        </div>
        <div>
          <dt>Shifoxona</dt>
          <dd>{{ detail.hospital?.name }}</dd>
        </div>
        <div>
          <dt>Hudud</dt>
          <dd>
            {{ detail.hospital?.region?.name }}
            <small>{{ detail.hospital?.district?.name }}</small>
          </dd>
        </div>
        <div v-if="isDoctor">
          <dt>Bemorlar soni</dt>
          <dd>{{ detail.patients_count }}</dd>
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
