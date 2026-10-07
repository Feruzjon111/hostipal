<script setup>
import { fullName, initials, dateTime } from '../lib/format';
import StatusBadge from './StatusBadge.vue';
import Icon from './Icon.vue';
defineProps({ patients: Array, compact: Boolean });
</script>
<template>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Bemor</th>
          <th v-if="!compact">Pasport / JShShIR</th>
          <th>Kasallik turi</th>
          <th>Shifokor</th>
          <th>Qabul sanasi</th>
          <th>Holat</th>
          <th><span class="sr-only">Ko‘rish</span></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="patient in patients" :key="patient.id">
          <td>
            <RouterLink :to="`/patients/${patient.id}`" class="person">
              <span class="avatar small" :class="`tone-${patient.id % 4}`">
                {{ initials(fullName(patient)) }}
              </span>
              <span>
                <strong>{{ fullName(patient) }}</strong>
                <small>{{ patient.middle_name }}</small>
              </span>
            </RouterLink>
          </td>
          <td v-if="!compact">
            <span class="mono">{{ patient.passport }}</span>
            <small class="mono">{{ patient.pin }}</small>
          </td>
          <td>{{ patient.disease?.name }}</td>
          <td>
            <span>{{ patient.doctor?.name }}</span>
            <small v-if="!compact">{{ patient.hospital?.name }}</small>
          </td>
          <td class="nowrap">{{ dateTime(patient.registered_at) }}</td>
          <td><StatusBadge :status="patient.status" /></td>
          <td>
            <RouterLink
              class="icon-button"
              :to="`/patients/${patient.id}`"
              :aria-label="`${fullName(patient)} kartasini ko‘rish`"
            >
              <Icon name="ArrowRight" :size="17" />
            </RouterLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
