import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import App from '../App.vue';

describe('App', () => {
  it('renders title', () => {
    const wrapper = mount(App);
    // Verificar texto que realmente existe en App.vue (Login)
    expect(wrapper.text()).toContain('Sistema de Ventas');
  });
});
