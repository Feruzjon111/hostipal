<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick, useId } from 'vue';
import Icon from './Icon.vue';
const props = defineProps({ open: Boolean, title: String, wide: Boolean });
const emit = defineEmits(['close']);
const dialog = ref();
const titleId = useId();
async function sync() {
  await nextTick();
  if (props.open && !dialog.value.open) {
    dialog.value.showModal();
    document.body.style.overflow = 'hidden';
  }
  if (!props.open && dialog.value.open) {
    dialog.value.close();
    document.body.style.overflow = '';
  }
}
watch(() => props.open, sync);
onMounted(sync);
onBeforeUnmount(() => {
  document.body.style.overflow = '';
});
</script>
<template>
  <dialog
    ref="dialog"
    :class="['modal', { wide }]"
    :aria-labelledby="titleId"
    @cancel.prevent="emit('close')"
    @click="
      (e) => {
        if (e.target === dialog) emit('close');
      }
    "
  >
    <header class="modal-head">
      <h2 :id="titleId">{{ title }}</h2>
      <button class="icon-button" aria-label="Oynani yopish" type="button" @click="emit('close')">
        <Icon name="X" />
      </button>
    </header>
    <div class="modal-body"><slot /></div>
  </dialog>
</template>
