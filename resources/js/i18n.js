import { createI18n } from 'vue-i18n';

// Import all language files
import bs from '../lang/bs.json';
import de from '../lang/de.json';
import en from '../lang/en.json';
import it from '../lang/it.json';
import sl from '../lang/sl.json';
import es from '../lang/es.json';
import bg from '../lang/bg.json';
import hu from '../lang/hu.json';
import fr from '../lang/fr.json';
import el from '../lang/el.json';

const messages = {
    bs,
    de,
    en,
    it,
    sl,
    es,
    bg,
    hu,
    fr,
    el,
};

const i18n = createI18n({
    legacy: false,
    locale: 'bs',
    fallbackLocale: 'bs',
    messages,
});

export default i18n;
