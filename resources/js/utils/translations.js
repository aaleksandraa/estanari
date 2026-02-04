// Complete translation system for all languages
export const translations = {
    bs: {
        // Navigation
        dashboard: 'Pregled', unpaid: 'Neplaćeno', paid: 'Plaćeno', plans: 'Planovi', suppliers: 'Dobavljači',
        reports: 'Izvještaji', settings: 'Podešavanja', logout: 'Odjava',
        // Auth
        login: 'Prijava', register: 'Registracija', email: 'Email', password: 'Lozinka', name: 'Ime',
        remember_me: 'Zapamti me', forgot_password: 'Zaboravili ste lozinku?', confirm_password: 'Potvrdi lozinku',
        already_registered: 'Već ste registrovani?',
        // Common
        save: 'Spremi', saving: 'Spremanje...', cancel: 'Odustani', edit: 'Uredi', delete: 'Obriši',
        add: 'Dodaj', update: 'Ažuriraj', close: 'Zatvori', view: 'Pregled', search: 'Pretraži...',
        filters: 'Filteri', clear: 'Očisti', actions: 'Akcije', status: 'Status', required: '*',
        // Dates
        today: 'Danas', tomorrow: 'Sutra', '3days': '3 dana', '7days': '7 dana', all: 'Svi',
        period: 'Period', from_date: 'Od datuma', to_date: 'Do datuma', date: 'Datum',
        date_pay_by: 'Datum (Platiti do)', planned_date: 'Planirani datum', payment_date: 'Datum plaćanja',
        select_date: 'Odaberi datum', select_month: 'Odaberi mjesec', select_payment_date: 'Odaberi datum kada je plaćanje izvršeno',
        // Dashboard
        payments_overview: 'Pregled plaćanja', today_to_pay: 'Danas za plaćanje', planned_payments: 'planiranih plaćanja',
        unpaid_status: 'Neplaćeno', paid_status: 'Plaćeno', in_selected_period: 'u odabranom periodu',
        not_paid_on_time: 'Nije plaćeno na vrijeme', click_for_overview: 'klikni za pregled',
        // Filters
        all_statuses: 'Svi statusi', all_currencies: 'Sve valute', all_suppliers: 'Svi dobavljači',
        all_branches: 'Sve poslovnice',
        // Payments
        total: 'Ukupno:', total_unpaid: 'Ukupno neplaćeno:', total_km: 'Ukupno KM', total_eur: 'Ukupno EUR',
        total_usd: 'Ukupno USD', new_payment: 'Novo plaćanje', mark_paid: 'Označi plaćeno',
        mark_as_paid: 'Označi kao plaćeno', mark_payments_as_paid: 'Označiti {count} plaćanja kao plaćeno?',
        showing: 'Prikazano', payments: 'plaćanja', for_period: 'za period:', unpaid_payments: 'neplaćenih plaćanja',
        total_unpaid_period: 'Ukupno neplaćeno',
        // Table
        supplier: 'Dobavljač', branch: 'Poslovnica', invoice_number: 'Br. fakture', description: 'Opis',
        amount: 'Iznos', currency: 'Valuta', pay_by: 'Platiti do', payment_details: 'Detalji plaćanja',
        mark_as_unpaid: 'Označi kao neplaćeno', paid_payments: 'plaćenih plaćanja', no_paid_invoices: 'Nema plaćenih faktura',
        no_payments_to_display: 'Nema plaćanja za prikaz', view_payment: 'Pregledaj', edit_payment: 'Uredi plaćanje',
        return_to_unpaid: 'Vrati u neplaćeno', delete_payment: 'Obriši plaćanje',
        delete_payment_confirm: 'Jeste li sigurni da želite obrisati plaćanje za \'{name}\'? Ova akcija se ne može poništiti.',
        mark_payment_as_paid: 'Označiti plaćanje za {name} kao plaćeno?',
        // Payment form
        edit_payment: 'Uredi plaćanje', search_supplier: 'Pretraži dobavljača...',
        search_branch: 'Pretraži poslovnicu...', invoice_placeholder: 'npr. 21/2025, FA-001',
        // Plans
        save_plan: 'Spremi plan', save_payment_plan: 'Spremi plan plaćanja', selected_payments: 'Odabrana plaćanja:',
        plan_name: 'Naziv plana', plan_name_placeholder: 'npr. Dnevni plan 01.01.2026',
        optional_description: 'Opis (opcionalno)', additional_notes: 'Dodatne napomene o planu...',
        plan_details: 'Detalji plana', created_at: 'Kreirano', payment_count: 'Broj plaćanja',
        mark_plan_paid: 'Označi plan kao plaćen', export_plan: 'Izvezi plan', back_to_plans: 'Nazad na planove',
        no_payments_in_plan: 'Nema plaćanja u ovom planu', saved_plans: 'Spremljeni planovi',
        no_saved_plans: 'Nema spremljenih planova', no_saved_plans_desc: 'Kreirajte plan na stranici Pregled odabirom filtera i klikom na "Spremi plan". Planovi vam omogućavaju brz pristup često korištenim pregledima plaćanja.',
        mark_plan_as_paid_confirm: 'Označiti plan "{name}" kao plaćen? Sva planirana plaćanja ({count}) u ovom planu će biti označena kao plaćena.',
        delete_plan_confirm: 'Jeste li sigurni da želite obrisati plan "{name}"? Ova akcija se ne može poništiti.',
        export_csv: 'Export CSV', export_pdf: 'Export PDF', export_excel: 'Export Excel',
        paid_at: 'Plaćeno:', custom: 'Prilagođeno',
        create_new: 'Kreiraj novi',
        create_new_plan: 'Kreiraj novi plan',
        plan_for_today: 'PLAN za današnji dan',
        no_plans_today: 'Nema planova za danas',
        create_plan: 'Kreiraj plan',
        edit_plan: 'Uredi plan',
        save_changes: 'Spremi izmjene',
        plan_description_placeholder: 'Dodatne napomene o planu...',
        add_payment_to_plan: 'Dodaj plaćanje', add_custom_item: 'Dodaj custom', plan_items: 'Stavke plana',
        total_converted_km: 'Ukupno konvertovano u KM', select_payment_to_add: 'Odaberi plaćanje koje želiš dodati u plan:',
        no_available_payments: 'Nema dostupnih plaćanja za dodavanje', no_search_results: 'Nema rezultata za',
        search_by_supplier: 'Pretraži po dobavljaču, poslovnici, br. fakture...', of_payments: '{count} od {total} plaćanja',
        add_custom_item_title: 'Dodaj custom stavku', description_required: 'Opis *',
        description_placeholder: 'npr. Provizija banke, Poštarina...', amount_required: 'Iznos *',
        currency_required: 'Valuta *', date_required: 'Datum *', adding: 'Dodavanje...',
        mark_plan_as_paid_title: 'Označi plan kao plaćen', mark_plan_as_paid_desc: 'Označiti plan \'{name}\' kao plaćen?',
        all_planned_payments_marked: 'Sva planirana plaćanja ({count}) u ovom planu će biti označena kao plaćena.',
        remove_item_from_plan: 'Ukloni stavku iz plana', remove_item_confirm: 'Ukloniti stavku \'{name}\' iz plana?',
        remove: 'Ukloni', custom_item: 'Custom stavka',
        // Settings
        profile: 'Profil', manage_profile: 'Upravljajte svojim korisničkim profilom',
        security: 'Sigurnost', change_password: 'Promijenite lozinku', change_password_btn: 'Promijeni lozinku',
        current_password: 'Trenutna lozinka', new_password: 'Nova lozinka', save_profile: 'Spremi profil',
        exchange_rates: 'Kursevi valuta', exchange_rates_desc: 'Postavite kurseve za konverziju u KM',
        fixed_rate: 'Fiksni kurs: 1.95583', variable_rate: 'Promjenjiv kurs', save_rates: 'Spremi kurseve',
        system: 'Sistem', system_info: 'Informacije o sistemu', app_version: 'Verzija aplikacije',
        your_role: 'Vaša uloga', company_name: 'Naziv firme', company_name_desc: 'Naziv koji se prikazuje u aplikaciji',
        company_name_placeholder: 'npr. Moja Firma d.o.o.', save_company_name: 'Spremi naziv',
        language: 'Jezik', language_desc: 'Odaberite jezik aplikacije', save_language: 'Spremi jezik',
        // Suppliers
        add_supplier: 'Dodaj dobavljača', supplier_name: 'Naziv dobavljača',
        supplier_name_placeholder: 'npr. ABC d.o.o.', add_branch: 'Dodaj poslovnicu',
        branch_name: 'Naziv poslovnice', branch_name_placeholder: 'npr. Sarajevo Centar',
        active: 'Aktivan', inactive: 'Neaktivan', activate: 'Aktiviraj', deactivate: 'Deaktiviraj',
        edit_supplier: 'Uredi dobavljača', edit_branch: 'Uredi poslovnicu', branches: 'Poslovnice',
        no_branches: 'Nema poslovnica', new_supplier: 'Novi dobavljač', new_branch: 'Nova poslovnica',
        phone: 'Telefon', address: 'Adresa', added: 'Dodano', import: 'Import', import_suppliers: 'Import dobavljača iz Excel-a',
        import_instructions: 'Upute za import:', download_template: 'Preuzmi šablon (Excel)',
        select_excel_file: 'Odaberi Excel fajl:', supported_formats: 'Podržani formati: .xlsx, .xls (max 5MB)',
        importing: 'Importovanje...', no_suppliers: 'Nema dobavljača za prikaz',
        delete_supplier_confirm: 'Jeste li sigurni da želite obrisati dobavljača "{name}"? Ova akcija će obrisati i sve njegove poslovnice.',
        delete_branch_confirm: 'Jeste li sigurni da želite obrisati poslovnicu "{name}"?',
        // Reports
        daily_report: 'Dnevni izvještaj', monthly_report: 'Mjesečni izvještaj',
        by_supplier_report: 'Izvještaj po dobavljačima', by_currency_report: 'Izvještaj po valutama',
        generate_report: 'Generiši izvještaj', select_date: 'Odaberi datum', select_month: 'Odaberi mjesec',
        download_excel: 'Preuzmi Excel', daily_report_desc: 'Pregled svih plaćanja za odabrani dan',
        monthly_report_desc: 'Sumarni pregled plaćanja po danima u mjesecu',
        by_supplier_report_desc: 'Detaljan pregled plaćanja za svakog dobavljača',
        by_currency_report_desc: 'Pregled plaćanja grupisanih po valutama (KM, EUR, USD)',
        includes_all_suppliers: 'Uključuje sve dobavljače sa ukupnim iznosima po valutama',
        excel_with_sheets: 'Excel sa odvojenim listovima za svaku valutu + pregled',
        professional_excel_reports: 'Profesionalni Excel izvještaji',
        reports_description: 'Svi izvještaji se generišu u Excel formatu (.xlsx) sa formatiranim tabelama, bojama i automatski prilagođenim širinama kolona. Spremni za štampu ili dalju obradu.',
        // Export
        excel: 'Excel', csv: 'CSV', pdf: 'PDF',
        // Messages
        no_data: 'Nema podataka', no_results: 'Nema rezultata', loading: 'Učitavanje...',
        // Roles
        admin: 'Administrator', accountant: 'Računovodstvo', viewer: 'Pregled',
        // Languages
        bs: 'Srpski - Hrvatski - Bosanski', de: 'Njemački', en: 'Engleski', it: 'Italijanski',
        sl: 'Slovenski', es: 'Španski', bg: 'Bugarski', hu: 'Mađarski', fr: 'Francuski', el: 'Grčki',
    },
    de: {
        // Navigation
        dashboard: 'Übersicht', unpaid: 'Unbezahlt', paid: 'Bezahlt', plans: 'Pläne', suppliers: 'Lieferanten',
        reports: 'Berichte', settings: 'Einstellungen', logout: 'Abmelden',
        // Auth
        login: 'Anmelden', register: 'Registrieren', email: 'E-Mail', password: 'Passwort', name: 'Name',
        remember_me: 'Angemeldet bleiben', forgot_password: 'Passwort vergessen?', confirm_password: 'Passwort bestätigen',
        already_registered: 'Bereits registriert?',
        // Common
        save: 'Speichern', saving: 'Speichern...', cancel: 'Abbrechen', edit: 'Bearbeiten', delete: 'Löschen',
        add: 'Hinzufügen', update: 'Aktualisieren', close: 'Schließen', view: 'Ansicht', search: 'Suchen...',
        filters: 'Filter', clear: 'Löschen', actions: 'Aktionen', status: 'Status', required: '*',
        // Dates
        today: 'Heute', tomorrow: 'Morgen', '3days': '3 Tage', '7days': '7 Tage', all: 'Alle',
        period: 'Zeitraum', from_date: 'Von Datum', to_date: 'Bis Datum', date: 'Datum',
        date_pay_by: 'Datum (Fällig bis)', planned_date: 'Geplantes Datum', payment_date: 'Zahlungsdatum',
        select_date: 'Datum wählen', select_month: 'Monat wählen', select_payment_date: 'Wählen Sie das Datum der Zahlung',
        // Dashboard
        payments_overview: 'Zahlungsübersicht', today_to_pay: 'Heute zu zahlen', planned_payments: 'geplante Zahlungen',
        unpaid_status: 'Unbezahlt', paid_status: 'Bezahlt', in_selected_period: 'im ausgewählten Zeitraum',
        not_paid_on_time: 'Nicht rechtzeitig bezahlt', click_for_overview: 'Klicken für Übersicht',
        // Filters
        all_statuses: 'Alle Status', all_currencies: 'Alle Währungen', all_suppliers: 'Alle Lieferanten',
        all_branches: 'Alle Filialen',
        // Payments
        total: 'Gesamt:', total_unpaid: 'Gesamt unbezahlt:', total_km: 'Gesamt KM', total_eur: 'Gesamt EUR',
        total_usd: 'Gesamt USD', new_payment: 'Neue Zahlung', mark_paid: 'Als bezahlt markieren',
        mark_as_paid: 'Als bezahlt markieren', mark_payments_as_paid: '{count} Zahlungen als bezahlt markieren?',
        showing: 'Angezeigt', payments: 'Zahlungen', for_period: 'für Zeitraum:', unpaid_payments: 'unbezahlte Zahlungen',
        total_unpaid_period: 'Gesamt unbezahlt',
        // Table
        supplier: 'Lieferant', branch: 'Filiale', invoice_number: 'Rechnungsnr.', description: 'Beschreibung',
        amount: 'Betrag', currency: 'Währung', pay_by: 'Fällig bis', payment_details: 'Zahlungsdetails',
        mark_as_unpaid: 'Als unbezahlt markieren', paid_payments: 'bezahlte Zahlungen', no_paid_invoices: 'Keine bezahlten Rechnungen',
        no_payments_to_display: 'Keine Zahlungen zum Anzeigen', view_payment: 'Ansehen', edit_payment: 'Zahlung bearbeiten',
        return_to_unpaid: 'Zurück zu unbezahlt', delete_payment: 'Zahlung löschen',
        delete_payment_confirm: 'Sind Sie sicher, dass Sie die Zahlung für \'{name}\' löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
        mark_payment_as_paid: 'Zahlung für {name} als bezahlt markieren?',
        // Payment form
        edit_payment: 'Zahlung bearbeiten', search_supplier: 'Lieferant suchen...',
        search_branch: 'Filiale suchen...', invoice_placeholder: 'z.B. 21/2025, FA-001',
        // Plans
        save_plan: 'Plan speichern', save_payment_plan: 'Zahlungsplan speichern', selected_payments: 'Ausgewählte Zahlungen:',
        plan_name: 'Planname', plan_name_placeholder: 'z.B. Tagesplan 01.01.2026',
        optional_description: 'Beschreibung (optional)', additional_notes: 'Zusätzliche Notizen...',
        plan_details: 'Plandetails', created_at: 'Erstellt am', payment_count: 'Anzahl Zahlungen',
        mark_plan_paid: 'Plan als bezahlt markieren', export_plan: 'Plan exportieren', back_to_plans: 'Zurück zu Plänen',
        no_payments_in_plan: 'Keine Zahlungen in diesem Plan', saved_plans: 'Gespeicherte Pläne',
        no_saved_plans: 'Keine gespeicherten Pläne', no_saved_plans_desc: 'Erstellen Sie einen Plan auf der Übersichtsseite, indem Sie Filter auswählen und auf "Plan speichern" klicken. Pläne ermöglichen schnellen Zugriff auf häufig verwendete Zahlungsübersichten.',
        mark_plan_as_paid_confirm: 'Plan "{name}" als bezahlt markieren? Alle geplanten Zahlungen ({count}) in diesem Plan werden als bezahlt markiert.',
        delete_plan_confirm: 'Sind Sie sicher, dass Sie den Plan "{name}" löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
        export_csv: 'CSV exportieren', export_pdf: 'PDF exportieren', export_excel: 'Excel exportieren',
        paid_at: 'Bezahlt:', custom: 'Benutzerdefiniert',
        create_new: 'Neu erstellen',
        create_new_plan: 'Neuen Plan erstellen',
        plan_for_today: 'PLAN für heute',
        no_plans_today: 'Keine Pläne für heute',
        create_plan: 'Plan erstellen',
        edit_plan: 'Plan bearbeiten',
        save_changes: 'Änderungen speichern',
        plan_description_placeholder: 'Zusätzliche Hinweise zum Plan...',
        // Settings
        profile: 'Profil', manage_profile: 'Verwalten Sie Ihr Benutzerprofil',
        security: 'Sicherheit', change_password: 'Passwort ändern', change_password_btn: 'Passwort ändern',
        current_password: 'Aktuelles Passwort', new_password: 'Neues Passwort', save_profile: 'Profil speichern',
        exchange_rates: 'Wechselkurse', exchange_rates_desc: 'Wechselkurse für Umrechnung in KM festlegen',
        fixed_rate: 'Fester Kurs: 1.95583', variable_rate: 'Variabler Kurs', save_rates: 'Kurse speichern',
        system: 'System', system_info: 'Systeminformationen', app_version: 'App-Version',
        your_role: 'Ihre Rolle', company_name: 'Firmenname', company_name_desc: 'Name, der in der Anwendung angezeigt wird',
        company_name_placeholder: 'z.B. Meine Firma GmbH', save_company_name: 'Name speichern',
        language: 'Sprache', language_desc: 'Wählen Sie die Anwendungssprache', save_language: 'Sprache speichern',
        // Suppliers
        add_supplier: 'Lieferant hinzufügen', supplier_name: 'Lieferantenname',
        supplier_name_placeholder: 'z.B. ABC GmbH', add_branch: 'Filiale hinzufügen',
        branch_name: 'Filialname', branch_name_placeholder: 'z.B. Berlin Mitte',
        active: 'Aktiv', inactive: 'Inaktiv', activate: 'Aktivieren', deactivate: 'Deaktivieren',
        edit_supplier: 'Lieferant bearbeiten', edit_branch: 'Filiale bearbeiten', branches: 'Filialen',
        no_branches: 'Keine Filialen', new_supplier: 'Neuer Lieferant', new_branch: 'Neue Filiale',
        phone: 'Telefon', address: 'Adresse', added: 'Hinzugefügt', import: 'Import', import_suppliers: 'Lieferanten aus Excel importieren',
        import_instructions: 'Importanweisungen:', download_template: 'Vorlage herunterladen (Excel)',
        select_excel_file: 'Excel-Datei auswählen:', supported_formats: 'Unterstützte Formate: .xlsx, .xls (max 5MB)',
        importing: 'Importieren...', no_suppliers: 'Keine Lieferanten zum Anzeigen',
        delete_supplier_confirm: 'Sind Sie sicher, dass Sie den Lieferanten "{name}" löschen möchten? Diese Aktion löscht auch alle seine Filialen.',
        delete_branch_confirm: 'Sind Sie sicher, dass Sie die Filiale "{name}" löschen möchten?',
        // Reports
        daily_report: 'Tagesbericht', monthly_report: 'Monatsbericht',
        by_supplier_report: 'Bericht nach Lieferanten', by_currency_report: 'Bericht nach Währungen',
        generate_report: 'Bericht erstellen', select_date: 'Datum wählen', select_month: 'Monat wählen',
        download_excel: 'Excel herunterladen', daily_report_desc: 'Übersicht aller Zahlungen für den ausgewählten Tag',
        monthly_report_desc: 'Zusammenfassende Übersicht der Zahlungen nach Tagen im Monat',
        by_supplier_report_desc: 'Detaillierte Übersicht der Zahlungen für jeden Lieferanten',
        by_currency_report_desc: 'Übersicht der Zahlungen gruppiert nach Währungen (KM, EUR, USD)',
        includes_all_suppliers: 'Enthält alle Lieferanten mit Gesamtbeträgen nach Währungen',
        excel_with_sheets: 'Excel mit separaten Blättern für jede Währung + Übersicht',
        professional_excel_reports: 'Professionelle Excel-Berichte',
        reports_description: 'Alle Berichte werden im Excel-Format (.xlsx) mit formatierten Tabellen, Farben und automatisch angepassten Spaltenbreiten generiert. Bereit zum Drucken oder zur weiteren Verarbeitung.',
        // Export
        excel: 'Excel', csv: 'CSV', pdf: 'PDF',
        // Messages
        no_data: 'Keine Daten', no_results: 'Keine Ergebnisse', loading: 'Laden...',
        // Roles
        admin: 'Administrator', accountant: 'Buchhaltung', viewer: 'Ansicht',
        // Languages
        bs: 'Serbisch - Kroatisch - Bosnisch', de: 'Deutsch', en: 'Englisch', it: 'Italienisch',
        sl: 'Slowenisch', es: 'Spanisch', bg: 'Bulgarisch', hu: 'Ungarisch', fr: 'Französisch', el: 'Griechisch',
    },
    en: {
        // Navigation
        dashboard: 'Overview', unpaid: 'Unpaid', paid: 'Paid', plans: 'Plans', suppliers: 'Suppliers',
        reports: 'Reports', settings: 'Settings', logout: 'Logout',
        // Auth
        login: 'Login', register: 'Register', email: 'Email', password: 'Password', name: 'Name',
        remember_me: 'Remember me', forgot_password: 'Forgot password?', confirm_password: 'Confirm password',
        already_registered: 'Already registered?',
        // Common
        save: 'Save', saving: 'Saving...', cancel: 'Cancel', edit: 'Edit', delete: 'Delete',
        add: 'Add', update: 'Update', close: 'Close', view: 'View', search: 'Search...',
        filters: 'Filters', clear: 'Clear', actions: 'Actions', status: 'Status', required: '*',
        // Dates
        today: 'Today', tomorrow: 'Tomorrow', '3days': '3 days', '7days': '7 days', all: 'All',
        period: 'Period', from_date: 'From date', to_date: 'To date', date: 'Date',
        date_pay_by: 'Date (Due by)', planned_date: 'Planned date', payment_date: 'Payment date',
        select_date: 'Select date', select_month: 'Select month', select_payment_date: 'Select the date when payment was made',
        // Dashboard
        payments_overview: 'Payments Overview', today_to_pay: 'Due Today', planned_payments: 'planned payments',
        unpaid_status: 'Unpaid', paid_status: 'Paid', in_selected_period: 'in selected period',
        not_paid_on_time: 'Not paid on time', click_for_overview: 'click for overview',
        // Filters
        all_statuses: 'All statuses', all_currencies: 'All currencies', all_suppliers: 'All suppliers',
        all_branches: 'All branches',
        // Payments
        total: 'Total:', total_unpaid: 'Total unpaid:', total_km: 'Total KM', total_eur: 'Total EUR',
        total_usd: 'Total USD', new_payment: 'New payment', mark_paid: 'Mark paid',
        mark_as_paid: 'Mark as paid', mark_payments_as_paid: 'Mark {count} payments as paid?',
        showing: 'Showing', payments: 'payments', for_period: 'for period:', unpaid_payments: 'unpaid payments',
        total_unpaid_period: 'Total unpaid',
        // Table
        supplier: 'Supplier', branch: 'Branch', invoice_number: 'Invoice No.', description: 'Description',
        amount: 'Amount', currency: 'Currency', pay_by: 'Due by', payment_details: 'Payment details',
        mark_as_unpaid: 'Mark as unpaid', paid_payments: 'paid payments', no_paid_invoices: 'No paid invoices',
        no_payments_to_display: 'No payments to display', view_payment: 'View', edit_payment: 'Edit payment',
        return_to_unpaid: 'Return to unpaid', delete_payment: 'Delete payment',
        delete_payment_confirm: 'Are you sure you want to delete payment for \'{name}\'? This action cannot be undone.',
        mark_payment_as_paid: 'Mark payment for {name} as paid?',
        // Payment form
        edit_payment: 'Edit payment', search_supplier: 'Search supplier...',
        search_branch: 'Search branch...', invoice_placeholder: 'e.g. 21/2025, FA-001',
        // Plans
        save_plan: 'Save plan', save_payment_plan: 'Save payment plan', selected_payments: 'Selected payments:',
        plan_name: 'Plan name', plan_name_placeholder: 'e.g. Daily plan 01.01.2026',
        optional_description: 'Description (optional)', additional_notes: 'Additional notes...',
        plan_details: 'Plan details', created_at: 'Created at', payment_count: 'Payment count',
        mark_plan_paid: 'Mark plan as paid', export_plan: 'Export plan', back_to_plans: 'Back to plans',
        no_payments_in_plan: 'No payments in this plan', saved_plans: 'Saved plans',
        no_saved_plans: 'No saved plans', no_saved_plans_desc: 'Create a plan on the Overview page by selecting filters and clicking "Save plan". Plans allow quick access to frequently used payment overviews.',
        mark_plan_as_paid_confirm: 'Mark plan "{name}" as paid? All planned payments ({count}) in this plan will be marked as paid.',
        delete_plan_confirm: 'Are you sure you want to delete plan "{name}"? This action cannot be undone.',
        export_csv: 'Export CSV', export_pdf: 'Export PDF', export_excel: 'Export Excel',
        paid_at: 'Paid:', custom: 'Custom',
        create_new: 'Create new',
        create_new_plan: 'Create new plan',
        plan_for_today: 'PLAN for today',
        no_plans_today: 'No plans for today',
        create_plan: 'Create plan',
        edit_plan: 'Edit plan',
        save_changes: 'Save changes',
        plan_description_placeholder: 'Additional notes about the plan...',
        // Settings
        profile: 'Profile', manage_profile: 'Manage your user profile',
        security: 'Security', change_password: 'Change password', change_password_btn: 'Change password',
        current_password: 'Current password', new_password: 'New password', save_profile: 'Save profile',
        exchange_rates: 'Exchange rates', exchange_rates_desc: 'Set exchange rates for conversion to KM',
        fixed_rate: 'Fixed rate: 1.95583', variable_rate: 'Variable rate', save_rates: 'Save rates',
        system: 'System', system_info: 'System information', app_version: 'App version',
        your_role: 'Your role', company_name: 'Company name', company_name_desc: 'Name displayed in the application',
        company_name_placeholder: 'e.g. My Company Ltd.', save_company_name: 'Save name',
        language: 'Language', language_desc: 'Select application language', save_language: 'Save language',
        // Suppliers
        add_supplier: 'Add supplier', supplier_name: 'Supplier name',
        supplier_name_placeholder: 'e.g. ABC Ltd.', add_branch: 'Add branch',
        branch_name: 'Branch name', branch_name_placeholder: 'e.g. London Central',
        active: 'Active', inactive: 'Inactive', activate: 'Activate', deactivate: 'Deactivate',
        edit_supplier: 'Edit supplier', edit_branch: 'Edit branch', branches: 'Branches',
        no_branches: 'No branches', new_supplier: 'New supplier', new_branch: 'New branch',
        phone: 'Phone', address: 'Address', added: 'Added', import: 'Import', import_suppliers: 'Import suppliers from Excel',
        import_instructions: 'Import instructions:', download_template: 'Download template (Excel)',
        select_excel_file: 'Select Excel file:', supported_formats: 'Supported formats: .xlsx, .xls (max 5MB)',
        importing: 'Importing...', no_suppliers: 'No suppliers to display',
        delete_supplier_confirm: 'Are you sure you want to delete supplier "{name}"? This action will also delete all its branches.',
        delete_branch_confirm: 'Are you sure you want to delete branch "{name}"?',
        // Reports
        daily_report: 'Daily report', monthly_report: 'Monthly report',
        by_supplier_report: 'Report by suppliers', by_currency_report: 'Report by currencies',
        generate_report: 'Generate report', select_date: 'Select date', select_month: 'Select month',
        download_excel: 'Download Excel', daily_report_desc: 'Overview of all payments for selected day',
        monthly_report_desc: 'Summary overview of payments by days in month',
        by_supplier_report_desc: 'Detailed overview of payments for each supplier',
        by_currency_report_desc: 'Overview of payments grouped by currencies (KM, EUR, USD)',
        includes_all_suppliers: 'Includes all suppliers with total amounts by currencies',
        excel_with_sheets: 'Excel with separate sheets for each currency + overview',
        professional_excel_reports: 'Professional Excel reports',
        reports_description: 'All reports are generated in Excel format (.xlsx) with formatted tables, colors and automatically adjusted column widths. Ready for printing or further processing.',
        // Export
        excel: 'Excel', csv: 'CSV', pdf: 'PDF',
        // Messages
        no_data: 'No data', no_results: 'No results', loading: 'Loading...',
        // Roles
        admin: 'Administrator', accountant: 'Accounting', viewer: 'Viewer',
        // Languages
        bs: 'Serbian - Croatian - Bosnian', de: 'German', en: 'English', it: 'Italian',
        sl: 'Slovenian', es: 'Spanish', bg: 'Bulgarian', hu: 'Hungarian', fr: 'French', el: 'Greek',
    },
};

// Helper functions
export function t(key, locale = 'bs', replacements = {}) {
    let translation = translations[locale]?.[key] || translations.en?.[key] || translations.bs[key] || key;
    Object.keys(replacements).forEach(placeholder => {
        translation = translation.replace(`{${placeholder}}`, replacements[placeholder]);
    });
    return translation;
}

export function getTranslation(key, locale = 'bs') {
    return translations[locale]?.[key] || translations.en?.[key] || translations.bs[key] || key;
}

export function getNavTranslation(key, locale = 'bs') {
    return getTranslation(key, locale);
}
