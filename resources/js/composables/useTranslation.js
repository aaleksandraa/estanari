import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

export function useTranslation() {
    const { t, locale } = useI18n();
    const page = usePage();
    
    // Watch for user language changes
    watch(() => page.props.auth?.user?.language, (newLang) => {
        if (newLang && locale.value !== newLang) {
            locale.value = newLang;
        }
    }, { immediate: true });
    
    return { t, locale };
}
