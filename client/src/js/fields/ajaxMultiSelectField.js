import Vue from 'vue';
import VueI18n from 'vue-i18n';
import AjaxSelectField from 'src/components/AjaxMultiSelectField.vue';
import watchElement from 'src/utils';

Vue.use(VueI18n);

const i18n = new VueI18n({
  locale: 'en',
  fallbackLocale: 'en'
});

const render = (el) => {
  new Vue({
    render(h) {
      return h(AjaxSelectField, {
        props: {
          payload: JSON.parse(el.dataset.payload)
        }
      });
    },
    i18n
  }).$mount(`#${el.id}`);
};

watchElement('.sunnysideup-ajaxMultiSelectFieldPlaceholder', (el) => {
  setTimeout(() => {
    render(el);
  }, 1);
});
