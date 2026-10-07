<script setup>
import { statuses } from '../lib/format';
defineProps({ status: String });
</script>
<template>
  <span :class="['badge', status]">
    <i />
    {{ statuses[status] || status }}
  </span>
</template>
