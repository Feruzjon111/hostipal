<script setup>
import Icon from './Icon.vue';
defineProps({ data: Object, busy: Boolean });
defineEmits(['page']);
</script>
<template>
  <div v-if="data" class="pagination">
    <span>{{ data.total }} ta yozuvdan {{ data.from || 0 }}–{{ data.to || 0 }} ko‘rsatilmoqda</span>
    <div>
      <button
        class="icon-button"
        aria-label="Oldingi sahifa"
        :disabled="busy || data.current_page <= 1"
        @click="$emit('page', data.current_page - 1)"
      >
        <Icon name="ChevronLeft" :size="18" />
      </button>
      <span class="page-number">{{ data.current_page }} / {{ data.last_page }}</span>
      <button
        class="icon-button"
        aria-label="Keyingi sahifa"
        :disabled="busy || data.current_page >= data.last_page"
        @click="$emit('page', data.current_page + 1)"
      >
        <Icon name="ChevronRight" :size="18" />
      </button>
    </div>
  </div>
</template>
