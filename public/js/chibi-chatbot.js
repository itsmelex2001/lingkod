/**
 * CHIBI CHATBOT CONTROLLER - LINGKOD Assistant (Nurse Mika & Dr. Leo)
 * Elevated Ultra-Compact Flat UI with Interactive FAQs, Follow-up Chips, and 4-Question Star Rating
 */
(function () {
  'use strict';

  const HEADER_ICONS = {
    girl: `
      <svg class="chibi-header-icon chibi-icon-cap" viewBox="0 0 64 42" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M16 13C21 5.5 43 5.5 48 13" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round"/>
        <path d="M7 17.5C18 10.5 46 10.5 57 17.5L51.5 34.5C40 28 24 28 12.5 34.5L7 17.5Z" stroke="#ffffff" stroke-width="2.4" stroke-linejoin="round"/>
        <path d="M30.2 17H33.8V20.8H37.6V24.2H33.8V28H30.2V24.2H26.4V20.8H30.2V17Z" stroke="#ffffff" stroke-width="1.9" stroke-linejoin="round"/>
      </svg>
    `,
    boy: `
      <svg class="chibi-header-icon chibi-icon-steth" viewBox="0 0 56 54" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20.5" cy="6.5" r="2.3" stroke="#ffffff" stroke-width="2.1"/>
        <circle cx="35.5" cy="6.5" r="2.3" stroke="#ffffff" stroke-width="2.1"/>
        <path d="M18.5 6.8C13.5 8 12.5 18 17.8 24C21.2 27.8 25.2 29.2 28 29.2C30.8 29.2 34.8 27.8 38.2 24C43.5 18 42.5 8 37.5 6.8" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round"/>
        <path d="M15.2 18.8C19.8 25.5 36.2 25.5 40.8 18.8" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
        <path d="M28 29.2V35.5C28 43 21.5 49 16 48C11.2 47.2 10.8 41.2 16 39.2C21.2 37.2 28.2 43 34.2 47.2C38.5 50.2 43 49 44.5 45.5" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round"/>
        <circle cx="45.5" cy="41.5" r="5" stroke="#ffffff" stroke-width="2.2"/>
        <circle cx="45.5" cy="41.5" r="2.2" stroke="#ffffff" stroke-width="1.8"/>
      </svg>
    `
  };

  const ASSISTANTS = {
    girl: {
      name: { EN: "Nurse Mika", TL: "Nurse Mika" },
      role: { EN: "LINGKOD ASSISTANT", TL: "LINGKOD ASSISTANT" },
      avatar: "/sdn/public/assets/chibi_girl_full.png",
      welcome: {
        EN: "Hello! I am Nurse Mika, your LINGKOD assistant. How can I help you today?",
        TL: "Kumusta! Ako si Nurse Mika, inyong LINGKOD assistant. Paano ko kayo matutulungan ngayon?"
      }
    },
    boy: {
      name: { EN: "Dr. Leo", TL: "Dr. Leo" },
      role: { EN: "LINGKOD ASSISTANT", TL: "LINGKOD ASSISTANT" },
      avatar: "/sdn/public/assets/chibi_boy_full.png",
      welcome: {
        EN: "Hello! I am Nurse Mika, your LINGKOD assistant. How can I help you today?",
        TL: "Kumusta! Ako si Dr. Leo, inyong LINGKOD assistant. Paano ko kayo matutulungan ngayon?"
      }
    }
  };

  const topics = {
    EN: [
      "How do I request an appointment/schedule?",
      "What are the hospital's operating hours & doctor schedules?",
      "Where I can find the nearest MMWGH hospital?",
      "What should I bring for my consultation & EMR triage?"
    ],
    TL: [
      "Paano ako mag-request ng appointment/iskedyul?",
      "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
      "Saan ko makikita ang pinakamalapit na ospital ng MMWGH?",
      "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?"
    ]
  };

  const dict = {
    EN: {
      selectQuestion: "CHOOSE A TOPIC TO ASK:",
      followUpLabel: "CHOOSE A FOLLOW-UP QUESTION:",
      backToTopics: "← Back to Main Topics",
      closeAria: "Close support chat",
      defaultUnmatched:
        "I could not locate an exact match for your inquiry. Please choose one of the core appointment workflow topics below:",
      defaultFollowUps: [
        "How do I check my appointment status on the portal?",
        "Can I make a same-day appointment or walk-in booking?",
        "What should I bring for my consultation & EMR triage?"
      ]
    },
    TL: {
      selectQuestion: "PUMILI NG TANONG:",
      followUpLabel: "MGA KAUGNAY NA TANONG:",
      backToTopics: "← Bumalik sa Pangunahing Tanong",
      closeAria: "Isara ang support chat",
      defaultUnmatched:
        "Hindi nahanap ang eksaktong sagot para sa inyong tanong. Pumili sa mga sumusunod na paksa:",
      defaultFollowUps: [
        "Paano ko masusuri ang status ng aking appointment sa portal?",
        "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?"
      ]
    }
  };

  const faqs = {
    EN: [
      {
        id: "request-appointment",
        question: "How do I request an appointment/schedule?",
        keywords: ["request", "appointment", "schedule", "book", "reserve", "slot"],
        answer:
          "Click <strong>'Book Appointment'</strong> on the homepage, select your desired clinical department, and complete the registration. <strong>Staff Verification Protocol:</strong> Our clinic staff will call your active phone number to confirm your details before placing you in the physician queue. Please keep your phone line active!",
        followUps: [
          "Why does clinic staff call before confirming my appointment?",
          "Can I make a same-day appointment or walk-in booking?",
          "What should I bring for my consultation & EMR triage?"
        ]
      },
      {
        id: "hospital-hours",
        question: "What are the hospital's operating hours & doctor schedules?",
        keywords: ["hours", "operating", "doctor", "schedules", "time", "open", "opd", "emergency"],
        answer:
          "The <strong>Emergency Triage</strong> operates 24/7. <strong>Outpatient (OPD) specialty clinics</strong> and telehealth services run Monday through Friday from <strong>8:00 AM to 5:00 PM</strong>. Live doctor availability and open slots can be inspected in the Schedules section.",
        followUps: [
          "How do I check live doctor availability and clinic schedules?",
          "What is the cut-off time for same-day clinic queues?",
          "Can I make a same-day appointment or walk-in booking?"
        ]
      },
      {
        id: "sdn-facility",
        question: "Where I can find the nearest MMWGH hospital?",
        keywords: ["where", "nearest", "mmwgh", "hospital", "sdn", "facility", "location", "rhu", "bhs", "network"],
        answer:
          "Access the interactive <strong>'Location Map'</strong> on our portal to view Mariveles Mental Wellness and General Hospital (MMWGH) and all affiliated Service Delivery Network (SDN) facilities, Rural Health Units (RHUs), and Barangay Health Stations (BHS).",
        followUps: [
          "How do I check my appointment status on the portal?",
          "What should I bring for my consultation & EMR triage?",
          "Why is Data Privacy Act (DPA) consent required?"
        ]
      },
      {
        id: "consultation-requirements",
        question: "What should I bring for my consultation & EMR triage?",
        keywords: ["bring", "consultation", "triage", "emr", "requirements", "documents", "id", "vitals"],
        answer:
          "Please present a valid government, company, or student ID, your active contact phone number, and any prior medical records, lab results, or prescription lists. Our staff records triage vitals into your electronic medical record (EMR) and issues printable clinical notes.",
        followUps: [
          "Why is Data Privacy Act (DPA) consent required?",
          "How do I check my appointment status on the portal?",
          "What happens if I miss the phone verification call?"
        ]
      },
      {
        id: "portal-status",
        question: "How do I check my appointment status on the portal?",
        keywords: ["portal", "status", "check", "dashboard", "queue", "reference"],
        answer:
          "Log in with your registered mobile number or patient reference number under the <strong>Patient Portal</strong> tab. Your dashboard displays your scheduled date, designated physician, and real-time consultation queue position.",
        followUps: [
          "Where can I view clinical notes and doctor prescriptions?",
          "What happens if I miss the phone verification call?",
          "Why is Data Privacy Act (DPA) consent required?"
        ]
      },
      {
        id: "portal-records",
        question: "Where can I view clinical notes and doctor prescriptions?",
        keywords: ["notes", "prescriptions", "records", "clinical", "pdf", "medical", "lab"],
        answer:
          "Completed consultation summaries, digital prescriptions, and laboratory referral forms are automatically archived in your <strong>Patient Portal &gt; Medical Records</strong> section as downloadable PDFs.",
        followUps: [
          "How do I check my appointment status on the portal?",
          "What should I bring for my consultation & EMR triage?",
          "Why is Data Privacy Act (DPA) consent required?"
        ]
      },
      {
        id: "dpa-consent",
        question: "Why is Data Privacy Act (DPA) consent required?",
        keywords: ["privacy", "dpa", "consent", "data", "confidential", "security", "law", "act"],
        answer:
          "Under <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>, explicit consent is required before processing healthcare data. Your personal identifiable information and medical records remain encrypted and restricted strictly to attending medical personnel.",
        followUps: [
          "What patient information is collected and how is it protected?",
          "How do I check my appointment status on the portal?",
          "Can I make a same-day appointment or walk-in booking?"
        ]
      },
      {
        id: "dpa-protection",
        question: "What patient information is collected and how is it protected?",
        keywords: ["collected", "protected", "stored", "safety", "records", "personal"],
        answer:
          "We collect basic demographic details, contact information, and triage vitals. Records are hosted within certified healthcare data infrastructure conforming to Department of Health (DOH) security guidelines.",
        followUps: [
          "Why is Data Privacy Act (DPA) consent required?",
          "Why does clinic staff call before confirming my appointment?",
          "Can I make a same-day appointment or walk-in booking?"
        ]
      },
      {
        id: "same-day",
        question: "Can I make a same-day appointment or walk-in booking?",
        keywords: ["same-day", "same day", "today", "walk-in", "walkin", "urgent"],
        answer:
          "Online same-day bookings are accepted for morning clinics until <strong>10:00 AM</strong>, subject to slot quotas. For acute or emergency conditions, report directly to the <strong>24/7 Emergency Triage</strong> without needing prior booking.",
        followUps: [
          "What is the cut-off time for same-day clinic queues?",
          "What are the hospital's operating hours & doctor schedules?",
          "Why does clinic staff call before confirming my appointment?"
        ]
      },
      {
        id: "cutoff-time",
        question: "What is the cut-off time for same-day clinic queues?",
        keywords: ["cut-off", "cutoff", "time", "deadline", "queue", "limit", "morning"],
        answer:
          "Outpatient departments enforce a strict <strong>10:00 AM cut-off</strong> for morning clinics and <strong>2:00 PM cut-off</strong> for afternoon clinics to ensure timely physical triage and examination.",
        followUps: [
          "Can I make a same-day appointment or walk-in booking?",
          "What are the hospital's operating hours & doctor schedules?",
          "Why does clinic staff call before confirming my appointment?"
        ]
      },
      {
        id: "schedules",
        question: "How do I check live doctor availability and clinic schedules?",
        keywords: ["schedule", "doctor", "availability", "roster", "physician", "slots", "calendar"],
        answer:
          "Open the <strong>Schedules</strong> module to inspect weekly rosters across specialties (Internal Medicine, Pediatrics, OB-GYN, Surgery). Open daily slots update dynamically according to duty shifts.",
        followUps: [
          "What are the hospital's operating hours & doctor schedules?",
          "Can I make a same-day appointment or walk-in booking?",
          "How do I check my appointment status on the portal?"
        ]
      },
      {
        id: "verification-call",
        question: "Why does clinic staff call before confirming my appointment?",
        keywords: ["call", "phone", "verify", "verification", "confirm", "staff", "nurse"],
        answer:
          "A nurse or admitting officer conducts a brief phone verification to review symptom urgency and ensure allocation to the proper specialty department before locking your consultation slot.",
        followUps: [
          "What happens if I miss the phone verification call?",
          "What should I bring for my consultation & EMR triage?",
          "Why is Data Privacy Act (DPA) consent required?"
        ]
      },
      {
        id: "missed-call",
        question: "What happens if I miss the phone verification call?",
        keywords: ["missed", "miss", "unanswered", "retry", "released", "cancel"],
        answer:
          "Staff make up to <strong>two retry attempts</strong> within 30 minutes. If unreachable, the pending slot is released back to the general queue. You can re-initiate booking or call our support hotline directly.",
        followUps: [
          "Why does clinic staff call before confirming my appointment?",
          "How do I check my appointment status on the portal?",
          "Can I make a same-day appointment or walk-in booking?"
        ]
      }
    ],
    TL: [
      {
        id: "request-appointment",
        question: "Paano ako mag-request ng appointment/iskedyul?",
        keywords: ["request", "appointment", "iskedyul", "book", "magpa-schedule"],
        answer:
          "Pindutin ang <strong>'Book Appointment'</strong> sa homepage, piliin ang inyong departamento, at sagutan ang form. <strong>Staff Verification Protocol:</strong> Tatawagan po kayo ng clinic staff sa inyong numero upang kumpirmahin ang detalye bago ilagay sa queue ng doktor. Pakisigurong aktibo ang inyong linya!",
        followUps: [
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
          "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?"
        ]
      },
      {
        id: "hospital-hours",
        question: "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
        keywords: ["oras", "operasyon", "iskedyul", "doktor", "bukas", "emergency", "opd"],
        answer:
          "Bukas ang <strong>Emergency Triage</strong> 24 oras araw-araw. Ang <strong>Outpatient (OPD) clinics</strong> at telehealth ay bukas Lunes hanggang Biyernes mula <strong>8:00 AM hanggang 5:00 PM</strong>.",
        followUps: [
          "Paano masusuri ang availability ng doktor at iskedyul ng klinika?",
          "Ano ang cut-off time para sa same-day clinic queues?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?"
        ]
      },
      {
        id: "sdn-facility",
        question: "Saan ko makikita ang pinakamalapit na ospital ng MMWGH?",
        keywords: ["saan", "mmwgh", "ospital", "pasilidad", "sdn", "lokasyon", "rhu", "bhs", "mapa"],
        answer:
          "Maaari ninyong tingnan ang aming interactive na <strong>'Location Map'</strong> upang makita ang MMWGH, partner hospitals, RHU, at BHS stations. Maaari ring mag-asikaso ang staff ng inter-facility electronic referral.",
        followUps: [
          "Paano ko masusuri ang status ng aking appointment sa portal?",
          "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
          "Bakit kailangan ang Data Privacy Act (DPA) consent?"
        ]
      },
      {
        id: "consultation-requirements",
        question: "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
        keywords: ["dalhin", "dokumento", "id", "kailangan", "triage", "vitals", "emr"],
        answer:
          "Magdala ng valid ID, inyong aktibong cellphone number, at anumang lumang laboratory results o reseta. Itatala ng aming nursing staff ang inyong vitals sa inyong Electronic Medical Record (EMR).",
        followUps: [
          "Bakit kailangan ang Data Privacy Act (DPA) consent?",
          "Paano ko masusuri ang status ng aking appointment sa portal?",
          "Ano ang mangyayari kung hindi nasagot ang verification call?"
        ]
      },
      {
        id: "portal-status",
        question: "Paano ko masusuri ang status ng aking appointment sa portal?",
        keywords: ["portal", "status", "tingnan", "appointment", "iskedyul", "queue"],
        answer:
          "Mag-log in sa <strong>Patient Portal</strong> gamit ang inyong registered mobile number o reference number. Makikita rito ang petsa, nakatalagang doktor, at queue status.",
        followUps: [
          "Saan makikita ang clinical notes at reseta ng doktor?",
          "Ano ang mangyayari kung hindi nasagot ang verification call?",
          "Bakit kailangan ang Data Privacy Act (DPA) consent?"
        ]
      },
      {
        id: "portal-records",
        question: "Saan makikita ang clinical notes at reseta ng doktor?",
        keywords: ["notes", "reseta", "doktor", "rekord", "laboratory", "pdf"],
        answer:
          "Ang mga clinical summary, lab referral forms, at digital na reseta ay naka-archive sa <strong>Patient Portal &gt; Medical Records</strong> bilang mga nada-download na PDF.",
        followUps: [
          "Paano ko masusuri ang status ng aking appointment sa portal?",
          "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
          "Bakit kailangan ang Data Privacy Act (DPA) consent?"
        ]
      },
      {
        id: "dpa-consent",
        question: "Bakit kailangan ang Data Privacy Act (DPA) consent?",
        keywords: ["privacy", "dpa", "consent", "datos", "batas", "proteksyon"],
        answer:
          "Ayon sa <strong>Republic Act 10173 (Data Privacy Act ng 2012)</strong>, kailangan ang inyong pahintulot upang maiproseso ang inyong medical data nang may mataas na antas ng encryption at seguridad.",
        followUps: [
          "Anong impormasyon ang kinokolekta at paano pinoprotektahan?",
          "Paano ko masusuri ang status ng aking appointment sa portal?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?"
        ]
      },
      {
        id: "dpa-protection",
        question: "Anong impormasyon ang kinokolekta at paano pinoprotektahan?",
        keywords: ["kinokolekta", "protektado", "datos", "ingat", "seguridad"],
        answer:
          "Kinokolekta lamang ang pangalan, contact details, at triage vitals alinsunod sa mga pamantayan ng Department of Health (DOH).",
        followUps: [
          "Bakit kailangan ang Data Privacy Act (DPA) consent?",
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?"
        ]
      },
      {
        id: "same-day",
        question: "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        keywords: ["same-day", "walk-in", "ngayon", "urgent", "emergency"],
        answer:
          "Tumatanggap ng online same-day bookings hanggang <strong>10:00 AM</strong> depende sa natitirang slots. Para sa mga agarang emergency, dumiretso sa <strong>24/7 Emergency Triage</strong>.",
        followUps: [
          "Ano ang cut-off time para sa same-day clinic queues?",
          "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?"
        ]
      },
      {
        id: "cutoff-time",
        question: "Ano ang cut-off time para sa same-day clinic queues?",
        keywords: ["cut-off", "cutoff", "oras", "deadline", "pila"],
        answer:
          "Ipinapatupad ang <strong>10:00 AM cut-off</strong> sa pang-umaga at <strong>2:00 PM cut-off</strong> sa panghapon upang masiguro ang masusing konsultasyon.",
        followUps: [
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
          "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?"
        ]
      },
      {
        id: "schedules",
        question: "Paano masusuri ang availability ng doktor at iskedyul ng klinika?",
        keywords: ["iskedyul", "doktor", "availability", "duty", "slots"],
        answer:
          "Buksan ang <strong>Schedules</strong> tab upang makita ang talaan ng mga doktor sa iba't ibang klinika at ang natitirang consultation quota.",
        followUps: [
          "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
          "Paano ko masusuri ang status ng aking appointment sa portal?"
        ]
      },
      {
        id: "verification-call",
        question: "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
        keywords: ["tawag", "staff", "kumpirmahin", "verification", "telepono"],
        answer:
          "Tumatawag ang nurse o admissions officer upang tiyaking angkop ang departamento para sa inyong sintomas bago pinal na i-lock ang inyong schedule.",
        followUps: [
          "Ano ang mangyayari kung hindi nasagot ang verification call?",
          "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
          "Bakit kailangan ang Data Privacy Act (DPA) consent?"
        ]
      },
      {
        id: "missed-call",
        question: "Ano ang mangyayari kung hindi nasagot ang verification call?",
        keywords: ["hindi nasagot", "missed", "retry", "nakansela"],
        answer:
          "Magtatangkang tumawag ang staff nang <strong>dalawang beses</strong> sa loob ng 30 minuto. Kung walang sagot, ibabalik ang slot sa pangkalahatang queue.",
        followUps: [
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
          "Paano ko masusuri ang status ng aking appointment sa portal?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?"
        ]
      }
    ]
  };

  // State
  let currentLang = 'EN';
  let isOpen = false;
  let activeChibi = 'girl'; // Nurse Mika default
  let chatAssistant = 'girl';
  let isTyping = false;
  let messages = [];
  let showingMainTopics = true;
  let activeBotMsgId = 'initial';

  // Cycle Timers for walking animation
  let timerStep1 = null;
  let timerStep2 = null;
  let timerStep3 = null;
  let timerStep4 = null;

  function formatTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    const dateStr = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    return `${timeStr} | ${dateStr}`;
  }

  function initChibiChatbot() {
    if (document.getElementById('chibi-chatbot-root')) return;

    const root = document.createElement('div');
    root.id = 'chibi-chatbot-root';
    root.innerHTML = `
      <!-- Floating Chibi Widget in Bottom-Right -->
      <div class="chibi-widget-container" id="chibiWidget">
        <div class="chibi-stage" id="chibiStage" title="Click to ask me!" role="button" tabindex="0">
          <div class="chibi-character-actor" id="chibiActor"></div>
        </div>
      </div>

      <!-- Chat Conversation Window -->
      <div class="chibi-chat-window" id="chibiChatWindow" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="chibi-chat-header">
          <button type="button" class="chibi-close-btn" id="chibiCloseBtn" aria-label="Close Chat">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>

          <div class="chibi-header-top" id="headerInfoToggle">
            <div class="chibi-header-icon-wrap" id="chibiHeaderIconWrap">
              ${HEADER_ICONS[chatAssistant]}
            </div>
            <h3 id="chibiHeaderName">${ASSISTANTS[chatAssistant].name[currentLang]}</h3>
          </div>

          <div class="chibi-header-divider"></div>

          <div class="chibi-header-bottom">
            <p id="chibiHeaderRole">${ASSISTANTS[chatAssistant].role[currentLang]}</p>

            <div class="chibi-lang-dropdown">
              <button type="button" class="chibi-lang-trigger" id="chibiLangTrigger" aria-label="Switch Language">
                <span id="chibiLangCode">${currentLang}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div class="chibi-lang-menu" id="chibiLangMenu" hidden>
                <button type="button" class="chibi-lang-option active" data-lang="EN" id="btnLangEN">EN (English)</button>
                <button type="button" class="chibi-lang-option" data-lang="TL" id="btnLangTL">TL (Tagalog)</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat Conversation Body -->
        <div class="chibi-chat-body" id="chibiChatBody"></div>
      </div>
    `;

    document.body.appendChild(root);

    // Event Listeners
    const stage = document.getElementById('chibiStage');
    const closeBtn = document.getElementById('chibiCloseBtn');
    const langTrigger = document.getElementById('chibiLangTrigger');
    const langMenu = document.getElementById('chibiLangMenu');
    const btnEN = document.getElementById('btnLangEN');
    const btnTL = document.getElementById('btnLangTL');
    const headerInfo = document.getElementById('headerInfoToggle');

    stage.addEventListener('click', () => openChatWith(activeChibi));
    stage.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') openChatWith(activeChibi);
    });
    closeBtn.addEventListener('click', toggleChat);

    if (langTrigger && langMenu) {
      langTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = langMenu.hasAttribute('hidden');
        if (isHidden) {
          langMenu.removeAttribute('hidden');
        } else {
          langMenu.setAttribute('hidden', '');
        }
      });

      document.addEventListener('click', () => {
        if (!langMenu.hasAttribute('hidden')) {
          langMenu.setAttribute('hidden', '');
        }
      });
    }

    btnEN.addEventListener('click', (e) => {
      e.stopPropagation();
      if (langMenu) langMenu.setAttribute('hidden', '');
      switchLanguage('EN');
    });
    btnTL.addEventListener('click', (e) => {
      e.stopPropagation();
      if (langMenu) langMenu.setAttribute('hidden', '');
      switchLanguage('TL');
    });
    // Header assistant switching disabled per design

    if (document.body && document.body.classList.contains('splash-active')) {
      const widget = document.getElementById('chibiWidget');
      widget.classList.add('chatbot-hidden');

      const observer = new MutationObserver(() => {
        if (!document.body.classList.contains('splash-active')) {
          widget.classList.remove('chatbot-hidden');
          startWalkingCycle();
          observer.disconnect();
        }
      });
      observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    } else {
      startWalkingCycle();
    }
  }

  // --- Walking Loop ---
  function startWalkingCycle() {
    clearCycleTimers();
    runSingleWalkCycle(activeChibi);
  }

  function clearCycleTimers() {
    if (timerStep1) clearTimeout(timerStep1);
    if (timerStep2) clearTimeout(timerStep2);
    if (timerStep3) clearTimeout(timerStep3);
    if (timerStep4) clearTimeout(timerStep4);
  }

  function bindBubbleClick() {
    const bubble = document.getElementById('chibiAskMeBubble');
    if (bubble) {
      bubble.addEventListener('click', (e) => {
        e.stopPropagation();
        openChatWith(activeChibi);
      });
    }
  }

  function runSingleWalkCycle(chibi) {
    if (isOpen) return;

    const actor = document.getElementById('chibiActor');
    if (!actor) return;

    activeChibi = chibi;
    const spriteClass = chibi === 'girl' ? 'chibi-sprite-girl' : 'chibi-sprite-boy';
    const characterName = chibi === 'girl' ? 'Nurse Mika' : 'Dr. Leo';

    actor.className = 'chibi-character-actor state-walk-in';
    actor.innerHTML = `<div class="chibi-sprite ${spriteClass}" aria-label="${characterName}"></div>`;

    timerStep1 = setTimeout(() => {
      if (isOpen) return;

      actor.className = 'chibi-character-actor state-arrived';
      actor.innerHTML = `
        <div class="chibi-ask-me-bubble ${currentLang === 'TL' ? 'is-tl' : ''}" id="chibiAskMeBubble" title="${currentLang === 'TL' ? 'TANUNGIN MO AKO' : 'ASK ME'}"></div>
        <div class="chibi-sprite ${spriteClass}" aria-label="${characterName}"></div>
      `;
      bindBubbleClick();

      timerStep2 = setTimeout(() => {
        if (isOpen) return;

        actor.className = 'chibi-character-actor state-walk-out';
        actor.innerHTML = `<div class="chibi-sprite ${spriteClass}" aria-label="${characterName}"></div>`;

        timerStep3 = setTimeout(() => {
          if (isOpen) return;
          timerStep4 = setTimeout(() => {
            if (isOpen) return;
            const nextChibi = activeChibi === 'boy' ? 'girl' : 'boy';
            runSingleWalkCycle(nextChibi);
          }, 1200);
        }, 2400);

      }, 5000);
    }, 2400);
  }

  function openChatWith(chibi) {
    chatAssistant = chibi;
    updateHeaderAssistantUI();
    resetConversation();

    if (!isOpen) {
      toggleChat();
    }
  }

  function toggleChat() {
    isOpen = !isOpen;
    const windowEl = document.getElementById('chibiChatWindow');
    const widgetEl = document.getElementById('chibiWidget');

    if (isOpen) {
      clearCycleTimers();
      windowEl.classList.add('is-open');
      windowEl.setAttribute('aria-hidden', 'false');
      if (widgetEl) {
        widgetEl.style.opacity = '0';
        widgetEl.style.pointerEvents = 'none';
      }
      scrollToBottom();
    } else {
      windowEl.classList.remove('is-open');
      windowEl.setAttribute('aria-hidden', 'true');
      if (widgetEl) {
        widgetEl.style.opacity = '1';
        widgetEl.style.pointerEvents = 'auto';
      }
      startWalkingCycle();
    }
  }

  function toggleHeaderAssistant() {
    chatAssistant = (chatAssistant === 'boy') ? 'girl' : 'boy';
    updateHeaderAssistantUI();
    resetConversation();
  }

  function updateHeaderAssistantUI() {
    const prof = ASSISTANTS[chatAssistant];
    const iconWrap = document.getElementById('chibiHeaderIconWrap');
    const nameEl = document.getElementById('chibiHeaderName');
    const roleEl = document.getElementById('chibiHeaderRole');

    if (iconWrap) iconWrap.innerHTML = HEADER_ICONS[chatAssistant];
    if (nameEl) nameEl.textContent = prof.name[currentLang];
    if (roleEl) roleEl.textContent = prof.role[currentLang];
  }

  function switchLanguage(lang) {
    if (currentLang === lang) return;
    currentLang = lang;

    const langCode = document.getElementById('chibiLangCode');
    const btnEN = document.getElementById('btnLangEN');
    const btnTL = document.getElementById('btnLangTL');
    if (langCode) langCode.textContent = lang;
    if (btnEN) btnEN.classList.toggle('active', lang === 'EN');
    if (btnTL) btnTL.classList.toggle('active', lang === 'TL');

    updateHeaderAssistantUI();
    resetConversation();
  }

  function resetConversation() {
    isTyping = false;
    showingMainTopics = true;
    activeBotMsgId = 'initial';
    const prof = ASSISTANTS[chatAssistant];

    messages = [
      {
        id: 'initial',
        sender: 'bot',
        text: prof.welcome[currentLang],
        time: formatTime(),
        avatar: prof.avatar,
        name: prof.name[currentLang],
        isInitial: true
      }
    ];

    renderChatBody(true);
  }

  function renderChatBody(autoScrollBottom = true) {
    const chatBody = document.getElementById('chibiChatBody');
    if (!chatBody) return;

    chatBody.innerHTML = '';

    messages.forEach((m) => {
      if (m.sender === 'bot') {
        const row = document.createElement('div');
        row.className = 'chibi-msg-row bot';
        if (m.id) row.setAttribute('data-msg-id', m.id);
        row.innerHTML = `
          <div class="chibi-msg-avatar">
            <img src="${m.avatar}" alt="${m.name}" />
          </div>
          <div class="chibi-msg-box">
            <div class="chibi-msg-bubble bot">
              ${m.text}
            </div>
            <span class="chibi-msg-time">${m.time}</span>
          </div>
        `;
        chatBody.appendChild(row);

        // Initial 4 Topic Cards at original top area (only when showingMainTopics is true)
        if (m.isInitial && showingMainTopics) {
          const cardContainer = document.createElement('div');
          cardContainer.className = 'chibi-faq-container';
          cardContainer.id = 'initialTopicsList';
          cardContainer.innerHTML = `
            <div class="chibi-faq-label">${dict[currentLang].selectQuestion}</div>
            <div class="chibi-faq-list">
              ${topics[currentLang].map(topic => `
                <button type="button" class="chibi-faq-item" data-question="${topic.replace(/"/g, '&quot;')}">
                  <span class="chibi-faq-text">${topic}</span>
                  <svg class="chibi-faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
              `).join('')}
            </div>
          `;
          chatBody.appendChild(cardContainer);

          cardContainer.querySelectorAll('.chibi-faq-item').forEach(btn => {
            btn.addEventListener('click', () => {
              submitQuery(btn.dataset.question);
            });
          });
        }

        // Follow-up Question Chips under the active bot response (when showingMainTopics is false)
        if (!m.isInitial && !showingMainTopics && m.id === activeBotMsgId && m.followUps && m.followUps.length > 0) {
          const chipsContainer = document.createElement('div');
          chipsContainer.className = 'chibi-followup-chips';
          chipsContainer.innerHTML = `
            <div class="chibi-faq-label">${dict[currentLang].followUpLabel}</div>
            <div class="chibi-faq-list">
              ${m.followUps.map(chip => `
                <button type="button" class="chibi-chip-btn" data-question="${chip.replace(/"/g, '&quot;')}">
                  <span class="chibi-faq-text">${chip}</span>
                  <svg class="chibi-faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
              `).join('')}
            </div>
            <button type="button" class="chibi-back-topics-btn">
              ${dict[currentLang].backToTopics}
            </button>
          `;
          chatBody.appendChild(chipsContainer);

          chipsContainer.querySelectorAll('.chibi-chip-btn').forEach(chipBtn => {
            chipBtn.addEventListener('click', () => {
              submitQuery(chipBtn.dataset.question);
            });
          });

          const backBtn = chipsContainer.querySelector('.chibi-back-topics-btn');
          if (backBtn) {
            backBtn.addEventListener('click', () => {
              showingMainTopics = true;
              activeBotMsgId = 'initial';
              renderChatBody(false);
              chatBody.scrollTo({ top: 0, behavior: 'smooth' });
            });
          }
        }
      } else {
        // User message
        const userRow = document.createElement('div');
        userRow.className = 'chibi-msg-row user';
        if (m.id) userRow.setAttribute('data-msg-id', m.id);
        userRow.innerHTML = `
          <div class="chibi-msg-box">
            <div class="chibi-msg-bubble user">
              ${m.text}
            </div>
            <span class="chibi-msg-time">${m.time}</span>
          </div>
        `;
        chatBody.appendChild(userRow);
      }
    });

    if (autoScrollBottom) {
      scrollToBottom();
    }
  }

  function findBestMatch(queryText) {
    const list = faqs[currentLang];
    const clean = queryText.toLowerCase().trim();

    const direct = list.find(
      item =>
        clean === item.question.toLowerCase() ||
        clean.includes(item.question.toLowerCase()) ||
        item.question.toLowerCase().includes(clean)
    );
    if (direct) return direct;

    let bestScore = 0;
    let selected = null;

    for (const item of list) {
      let score = 0;
      for (const kw of item.keywords) {
        if (clean.includes(kw.toLowerCase())) score += 1;
      }
      if (score > bestScore) {
        bestScore = score;
        selected = item;
      }
    }

    return bestScore > 0 ? selected : null;
  }

  function submitQuery(queryText) {
    if (isTyping) return;

    const cleanKey = queryText.toLowerCase().trim();
    const existingBot = messages.find(
      m => m.sender === 'bot' && m.questionKey === cleanKey
    );

    // If this question was already asked earlier, scroll up to that earlier response
    if (existingBot) {
      showingMainTopics = false;
      activeBotMsgId = existingBot.id;
      renderChatBody(false);

      const chatBody = document.getElementById('chibiChatBody');
      if (chatBody) {
        const targetRow = chatBody.querySelector(`[data-msg-id="${existingBot.id}"]`);
        if (targetRow) {
          targetRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
          const bubble = targetRow.querySelector('.chibi-msg-bubble.bot');
          if (bubble) {
            bubble.classList.add('chibi-bubble-highlight');
            setTimeout(() => bubble.classList.remove('chibi-bubble-highlight'), 1400);
          }
        }
      }
      return;
    }

    // Vanish the topics / previous follow-ups immediately when a new question is clicked
    showingMainTopics = false;
    activeBotMsgId = null;

    messages.push({
      id: 'user-' + Date.now(),
      sender: 'user',
      text: queryText,
      time: formatTime()
    });
    renderChatBody(true);

    isTyping = true;

    // Show Typing Indicator
    const chatBody = document.getElementById('chibiChatBody');
    const prof = ASSISTANTS[chatAssistant];

    const typingRow = document.createElement('div');
    typingRow.className = 'chibi-typing-row';
    typingRow.id = 'chibiTyping';
    typingRow.innerHTML = `
      <div class="chibi-typing-bubble">
        <span class="chibi-dot"></span>
        <span class="chibi-dot"></span>
        <span class="chibi-dot"></span>
      </div>
    `;
    chatBody.appendChild(typingRow);
    scrollToBottom();

    setTimeout(() => {
      const typ = document.getElementById('chibiTyping');
      if (typ) typ.remove();
      isTyping = false;

      const match = findBestMatch(queryText);
      let replyText = '';
      let chips = [];

      if (match) {
        replyText = match.answer;
        chips = match.followUps || [];
      } else {
        replyText = dict[currentLang].defaultUnmatched;
        chips = dict[currentLang].defaultFollowUps;
      }

      const newBotId = 'bot-' + Date.now();
      messages.push({
        id: newBotId,
        questionKey: cleanKey,
        sender: 'bot',
        text: replyText,
        time: formatTime(),
        avatar: prof.avatar,
        name: prof.name[currentLang],
        followUps: chips
      });

      showingMainTopics = false;
      activeBotMsgId = newBotId;
      renderChatBody(true);
    }, 400);
  }

  function scrollToBottom() {
    const body = document.getElementById('chibiChatBody');
    if (body) {
      setTimeout(() => {
        body.scrollTop = body.scrollHeight;
      }, 25);
    }
  }

  // Bootstrap on Ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChibiChatbot);
  } else {
    initChibiChatbot();
  }
})();
