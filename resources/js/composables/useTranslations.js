import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { t, getTranslation } from '@/utils/translations';

export function useTranslations() {
    const page = usePage();
    
    const locale = computed(() => page.props.auth?.user?.language || 'bs');
    
    // Translation function that automatically uses user's language
    const trans = (key, replacements = {}) => {
        return t(key, locale.value, replacements);
    };
    
    // Get translation without replacements
    const __ = (key) => {
        return getTranslation(key, locale.value);
    };
    
    return {
        t: trans,
        __,
        locale,
    };
}
