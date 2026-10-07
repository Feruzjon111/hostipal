<script setup>
import { ref, useId, onMounted, onUpdated } from 'vue';
const props = defineProps({
  label: String,
  error: [String, Array],
  required: Boolean,
  hint: String,
});
const wrapper = ref();
const id = useId();
function syncAccessibility() {
  const control = wrapper.value?.querySelector('input, select, textarea');
  if (!control) {
    return;
  }
  control.id = id;
  control.setAttribute('aria-label', props.label);
  control.setAttribute('aria-invalid', props.error ? 'true' : 'false');
  if (props.error || props.hint) {
    control.setAttribute('aria-describedby', `${id}-hint`);
  } else control.removeAttribute('aria-describedby');
}
onMounted(syncAccessibility);
onUpdated(syncAccessibility);
</script>
<template>
  <div ref="wrapper" class="field">
    <label :for="id" class="field-label">
      {{ label }}
      <span v-if="required" class="required" aria-hidden="true">*</span>
    </label>
    <slot />
    <span v-if="error" :id="`${id}-hint`" class="field-error">
      {{ Array.isArray(error) ? error[0] : error }}
    </span>
    <span v-else-if="hint" :id="`${id}-hint`" class="field-hint">{{ hint }}</span>
  </div>
</template>
