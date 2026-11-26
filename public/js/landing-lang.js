(function () {
  const copy = {
    en: {
      nav_home: 'Home',
      nav_services: 'Services',
      nav_book: 'Book Appointment',
      nav_schedule: 'View Schedule',
      nav_about: 'About',
      nav_location: 'Location',
      nav_contact: 'Contact Us',
      nav_portal: 'Patient Portal',
      hero_sub: 'Localized Integrated Network for Governance,<br>Knowledge, and Operation in Digital Healthcare',
      hero_desc: 'Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered<br> healthcare services to the community and surrounding regions.',
      hero_book: 'Book an Appointment',
      hero_schedule: 'View Schedule',
      pbc_eyebrow: 'MMWGH • LINGKOD',
      pbc_title: 'Book an Appointment',
      pbc_subtitle: 'Select your patient status to continue.',
      pbc_have_title: 'I have an account',
      pbc_have_badge: 'Returning',
      pbc_have_desc: 'Sign in to book faster and view your visit records.',
      pbc_new_title: "I'm a new patient",
      pbc_new_badge: 'First visit',
      pbc_new_desc: 'Create your patient profile and book your first consultation.',
      pbc_emergency_prefix: 'In an emergency, go to the MMWGH Emergency Department or call',
      howto_title: 'HOW TO USE LINGKOD?',
      howto_sub: 'A simple step-by-step guide on how to book an appointment.',
      howto_step_0_title: 'Book Appointment',
      howto_step_0_desc: "Fill out the required form and click 'Book Appointment' to start.",
      howto_step_1_title: 'Verify Your Email',
      howto_step_1_desc: 'Confirm your email to receive your secure login credentials.',
      howto_step_2_title: 'Await Review',
      howto_step_2_desc: 'Our medical staff will review your request shortly.',
      howto_step_3_title: 'Got It!',
      howto_step_3_desc: 'Check your inbox for your official appointment confirmation.',
      services_title: 'MEDICAL SERVICES',
      services_sub: 'Comprehensive care across mental and physical wellness.',
      about_body: 'The Mariveles Mental Wellness and General Hospital (MMWGH) serves as a Department of Health hospital, catering to the mental health needs of Northern and Central Luzon. Currently operating under a Level 1 license (LTO No. 03-0171-24-H1-1), with 18 beds, MMWGH also offers essential add-on services including a 500-bed custodial psychiatric care unit, type-1 ambulance service, secondary clinical laboratory, HIV-testing laboratory, and pharmacy.',
      why_body: 'Located in Mariveles, Bataan, we are a Department of Health (DOH) specialty hospital dedicated to providing accessible, high-quality mental health and general healthcare services delivered by skilled medical professionals with compassion and integrity.',
      footer_tagline: 'A Localized Network and Knowledge-base for<br> Online Service Delivery',
      footer_tagline_sub: 'Dedicated to your health, driven by excellence.',
      footer_links: 'Quick Links',
      footer_about: 'About Us',
      footer_services: 'Services',
      footer_location: 'Location',
      footer_schedules: 'Schedules',
      footer_book: 'Book Appointment',
      footer_contact: 'Contact Information',
      map_search: 'Search',
      map_all: 'All',
      map_visited: 'Most Visited',
      vision_body: 'Mariveles Mental Wellness and General Hospital is the premier client-oriented DOH hospital, providing safe. efficient, and quality services.',
      mission_body: 'We are committed to delivering compassionate, safe, and high-quality healthcare in every encounter by preserving dignity, strengthening hope, and promoting holistic well-being for a healthier and more resilient community.',
      quality_1: "The Mariveles Mental Wellness and General Hospital is committed to provide affordable quality mental and medical health care with Fairness, Accountability, and Continuous improvement. We shall ensure compliance with statutory and regulatory requirements. We pledge to continually improve our Quality Management System to exceed our client's satisfaction.",
      quality_2: "We shall ensure compliance with statutory and regulatory requirements. We pledge to continually improve our Quality Management System to exceed our client's satisfaction.",
      quality_3: "We pledge to continually improve our Quality Management System to exceed our client's satisfaction."
    },
    tl: {
      nav_home: 'Home',
      nav_services: 'Mga Serbisyo',
      nav_book: 'Magpa-appointment',
      nav_schedule: 'Tingnan ang Iskedyul',
      nav_about: 'Tungkol',
      nav_location: 'Lokasyon',
      nav_contact: 'Makipag-ugnayan',
      nav_portal: 'Portal ng Pasyente',
      hero_sub: 'Localized Integrated Network for Governance,<br>Knowledge, and Operation in Digital Healthcare',
      hero_desc: 'Nakatuon sa kabuuang kalusugan, naghahatid ang ospital ng de-kalidad, maasikaso, at nakasentro sa&nbsp;pasyente<br> na serbisyong pangkalusugan para sa komunidad at mga karatig na lugar.',
      hero_book: 'Magpa-appointment',
      hero_schedule: 'Tingnan ang Iskedyul',
      pbc_eyebrow: 'MMWGH • LINGKOD',
      pbc_title: 'Magpa-appointment',
      pbc_subtitle: 'Piliin ang estado ng iyong account upang magpatuloy.',
      pbc_have_title: 'May account na ako',
      pbc_have_badge: 'Dating pasyente',
      pbc_have_desc: 'Mag-sign in para mas mabilis na mag-book at makita ang iyong rekord.',
      pbc_new_title: 'Bagong pasyente ako',
      pbc_new_badge: 'Unang bisita',
      pbc_new_desc: 'Gumawa ng patient profile at magpa-iskedyul ng unang konsultasyon.',
      pbc_emergency_prefix: 'Sa oras ng emergency, pumunta sa MMWGH Emergency Department o tumawag sa',
      howto_title: 'PAANO GAMITIN ANG LINGKOD?',
      howto_sub: 'Simpleng hakbang-hakbang na gabay sa pagpapareserba ng appointment.',
      howto_step_0_title: 'Magpa-appointment',
      howto_step_0_desc: "Sagutan ang kinakailangang form at i-click ang 'Book Appointment' upang magsimula.",
      howto_step_1_title: 'I-verify ang Iyong Email',
      howto_step_1_desc: 'Kumpirmahin ang iyong email upang matanggap ang iyong ligtas na login credentials.',
      howto_step_2_title: 'Maghintay ng Pagsusuri',
      howto_step_2_desc: 'Susuriin ng aming medical staff ang iyong kahilingan sa lalong madaling panahon.',
      howto_step_3_title: 'Ayos Na!',
      howto_step_3_desc: 'Tingnan ang iyong inbox para sa opisyal na kumpirmasyon ng appointment.',
      services_title: 'MGA SERBISYONG MEDIKAL',
      services_sub: 'Kumpletong pangangalaga sa kalusugan ng isip at katawan.',
      about_body: 'Ang Mariveles Mental Wellness and General Hospital (MMWGH) ay ospital ng Department of Health na tumutugon sa pangangailangang pangkalusugan ng isip ng Hilaga at Gitnang Luzon. Kasalukuyan itong Level 1 (LTO No. 03-0171-24-H1-1), may 18 kama, at may dagdag na serbisyo tulad ng 500-bed custodial psychiatric care unit, type-1 ambulance, secondary clinical laboratory, HIV-testing laboratory, at botika.',
      why_body: 'Matatagpuan sa Mariveles, Bataan, kami ay specialty hospital ng Department of Health (DOH) na naghahatid ng abot-kaya at de-kalidad na serbisyo sa kalusugan ng isip at pangkalahatang pangangalaga, mula sa mahuhusay na manggagamot, nang may malasakit at integridad.',
      footer_tagline: 'Isang lokal na network at knowledge-base para sa<br> online na paghahatid ng serbisyo',
      footer_tagline_sub: 'Nakatuon sa iyong kalusugan, hinuhubog ng kahusayan.',
      footer_links: 'Mabilis na Link',
      footer_about: 'Tungkol sa Amin',
      footer_services: 'Mga Serbisyo',
      footer_location: 'Lokasyon',
      footer_schedules: 'Mga Iskedyul',
      footer_book: 'Magpa-appointment',
      footer_contact: 'Impormasyon sa Pakikipag-ugnayan',
      map_search: 'Maghanap',
      map_all: 'Lahat',
      map_visited: 'Pinakabinibisita',
      vision_body: 'Ang Mariveles Mental Wellness and General Hospital ay ang pangunahing ospital ng DOH na nakatuon sa kliyente, na nagbibigay ng ligtas, episyente, at de-kalidad na serbisyo.',
      mission_body: 'Nakatuon kami sa paghahatid ng maasikaso, ligtas, at de-kalidad na pangangalagang pangkalusugan sa bawat engkuwentro sa pamamagitan ng pagpapanatili ng dignidad, pagpapalakas ng pag-asa, at pagtataguyod ng kabuuang kalusugan para sa mas malusog at mas matatag na komunidad.',
      quality_1: 'Ang Mariveles Mental Wellness and General Hospital ay nakatuon sa pagbibigay ng abot-kayang de-kalidad na pangangalaga sa kalusugan ng isip at medikal nang may Pagkakapantay-pantay, Pananagutan, at Patuloy na pagpapabuti. Sisiguraduhin namin ang pagsunod sa mga batas at regulasyon. Nangangako kaming patuloy na pagbutihin ang aming Quality Management System upang higitan ang kasiyahan ng aming kliyente.',
      quality_2: 'Sisiguraduhin namin ang pagsunod sa mga batas at regulasyon. Nangangako kaming patuloy na pagbutihin ang aming Quality Management System upang higitan ang kasiyahan ng aming kliyente.',
      quality_3: 'Nangangako kaming patuloy na pagbutihin ang aming Quality Management System upang higitan ang kasiyahan ng aming kliyente.'
    }
  };

  function apply(lang) {
    const pack = copy[lang] || copy.en;
    document.documentElement.lang = lang === 'tl' ? 'tl' : 'en';
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      const value = pack[el.getAttribute('data-i18n')];
      if (value != null) el.textContent = value;
    });
    document.querySelectorAll('[data-i18n-html]').forEach(function (el) {
      const value = pack[el.getAttribute('data-i18n-html')];
      if (value != null) el.innerHTML = value;
    });
    document.querySelectorAll('[data-i18n-ph]').forEach(function (el) {
      const value = pack[el.getAttribute('data-i18n-ph')];
      if (value != null) el.setAttribute('placeholder', value);
    });
    const code = document.getElementById('langSwitchCode');
    if (code) code.textContent = lang === 'tl' ? 'TL' : 'EN';
    document.dispatchEvent(new CustomEvent('lingkod-lang', { detail: { lang: lang } }));
    document.querySelectorAll('.lang-switch-option').forEach(function (btn) {
      btn.classList.toggle('is-selected', btn.getAttribute('data-lang') === lang);
    });
  }

  function setLang(lang) {
    const next = lang === 'tl' ? 'tl' : 'en';
    localStorage.setItem('lingkodLang', next);
    apply(next);
    closeMenu();
  }

  function closeMenu() {
    const menu = document.getElementById('langSwitchMenu');
    const btn = document.getElementById('langSwitchBtn');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const saved = localStorage.getItem('lingkodLang') === 'tl' ? 'tl' : 'en';
    apply(saved);

    const btn = document.getElementById('langSwitchBtn');
    const menu = document.getElementById('langSwitchMenu');
    if (btn && menu) {
      btn.addEventListener('click', function (event) {
        event.stopPropagation();
        const open = menu.hidden;
        menu.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      menu.querySelectorAll('.lang-switch-option').forEach(function (option) {
        option.addEventListener('click', function () {
          setLang(option.getAttribute('data-lang'));
        });
      });
    }
    document.addEventListener('click', function (event) {
      if (!event.target.closest('.lang-switch')) closeMenu();
    });
  });
})();
