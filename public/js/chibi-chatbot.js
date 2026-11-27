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
        EN: "Hello! I am Dr. Leo, your LINGKOD assistant. How can I help you today?",
        TL: "Kumusta! Ako si Dr. Leo, inyong LINGKOD assistant. Paano ko kayo matutulungan ngayon?"
      }
    }
  };

  const topics = {
    EN: [
      "How do I book an appointment?",
      "How does Teleconsultation work for returning patients?",
      "What departments and clinic hours are available?",
      "How do I track or reschedule my appointment?"
    ],
    TL: [
      "Paano ako mag-book ng appointment?",
      "Paano gumagana ang Teleconsultation para sa returning patients?",
      "Anong mga departamento at oras ng klinika ang bukas?",
      "Paano ko masusuri o mare-reschedule ang aking appointment?"
    ]
  };

  const dict = {
    EN: {
      selectQuestion: "TOPICS YOU CAN ASK",
      followUpLabel: "FOLLOW-UP QUESTIONS",
      backToTopics: "← Back to Main Topics",
      closeAria: "Close support chat",
      defaultUnmatched:
        "I could not find an exact match for that question. Choose one of the core LINGKOD appointment topics below:",
      defaultFollowUps: [
        "How do I book an appointment?",
        "How does Teleconsultation work for returning patients?",
        "How do I track or reschedule my appointment?"
      ]
    },
    TL: {
      selectQuestion: "MGA PAKSANG MAAARING ITANONG",
      followUpLabel: "MGA KAUGNAY NA TANONG",
      backToTopics: "← Bumalik sa Pangunahing Paksa",
      closeAria: "Isara ang support chat",
      defaultUnmatched:
        "Hindi nahanap ang eksaktong sagot para sa inyong tanong. Pumili sa mga pangunahing paksa sa ibaba:",
      defaultFollowUps: [
        "Paano ako mag-book ng appointment?",
        "Paano gumagana ang Teleconsultation para sa returning patients?",
        "Paano ko masusuri o mare-reschedule ang aking appointment?"
      ]
    }
  };

  const defaultFaqs = {
    EN: [
      {
        id: "request-appointment",
        persona: "both",
        question: "How do I book an appointment?",
        keywords: ["book", "request", "appointment", "schedule", "reserve", "slot", "register"],
        answer:
          "Click <strong>Book Appointment</strong>, review and accept the Data Privacy notice, then complete the 4-step form (Personal Info, Medical Info, Appointment Details, and Confirmation). Online appointments can be booked <strong>Monday to Saturday</strong> at least 1 day ahead for Morning or Afternoon sessions. Your request starts as <strong>For Approval</strong> while clinic staff verifies physician availability.",
        followUps: [
          "How does Teleconsultation work for returning patients?",
          "Can I book for a family member or just ask an inquiry?",
          "How do I track or reschedule my appointment?"
        ]
      },
      {
        id: "teleconsultation-flow",
        persona: "both",
        question: "How does Teleconsultation work for returning patients?",
        keywords: ["teleconsult", "teleconsultation", "online", "video", "returning", "remote", "google meet", "zoom"],
        answer:
          "First-time patients are required to complete an initial <strong>Face-to-Face</strong> visit. Once you have at least one <strong>completed</strong> hospital consultation, <strong>Teleconsultation</strong> unlocks in Step 3 of booking. After staff approves your visit, your session link (e.g., Google Meet/Zoom) or clinic call instructions appear directly on your appointment card in the <strong>Patient Portal</strong>.",
        followUps: [
          "How do I book an appointment?",
          "What should I prepare for my consultation?",
          "How do I track or reschedule my appointment?"
        ]
      },
      {
        id: "hospital-hours",
        persona: "both",
        question: "What departments and clinic hours are available?",
        keywords: ["department", "hours", "operating", "open", "opd", "general medical", "neurology", "psychiatry", "emergency"],
        answer:
          "MMWGH Outpatient Clinics serve <strong>General Medical</strong>, <strong>Neurology</strong>, and <strong>Psychiatry</strong> from <strong>Monday to Saturday, 8:00 AM – 5:00 PM</strong> (Morning: 8:00 AM – 12:00 PM; Afternoon: 1:00 PM – 5:00 PM; closed Sundays). For urgent emergencies, our <strong>Emergency Triage</strong> is open 24/7.",
        followUps: [
          "How do I check doctor schedules and open slots?",
          "Can I book for a family member or just ask an inquiry?",
          "Where is MMWGH and how can I contact the clinic?"
        ]
      },
      {
        id: "portal-status",
        persona: "both",
        question: "How do I track or reschedule my appointment?",
        keywords: ["portal", "track", "status", "reschedule", "slip", "qr", "login", "account"],
        answer:
          "Sign in to the <strong>Patient Portal</strong> using your username or email and password. You can check real-time status (<strong>For Approval</strong>, <strong>Confirmed</strong>, <strong>Checked In</strong>, <strong>Completed</strong>), download your <strong>Appointment/Teleconsultation Slip</strong> and <strong>Digital Patient ID</strong>, and reschedule directly while your visit is still <strong>For Approval</strong>.",
        followUps: [
          "What should I prepare for my consultation?",
          "How does Teleconsultation work for returning patients?",
          "How is my medical data protected?"
        ]
      },
      {
        id: "consultation-requirements",
        persona: "both",
        question: "What should I prepare for my consultation?",
        keywords: ["bring", "prepare", "requirements", "id", "slip", "triage", "documents", "prescription"],
        answer:
          "For <strong>Face-to-Face</strong> visits, bring a valid ID, your printable <strong>Appointment Slip</strong> or <strong>Digital Patient ID (QR)</strong> from the Patient Portal, and any prior prescriptions or lab results. For <strong>Teleconsultation</strong>, keep your active mobile number reachable and open your Patient Portal 5–10 minutes before your scheduled block.",
        followUps: [
          "How does Teleconsultation work for returning patients?",
          "Where is MMWGH and how can I contact the clinic?",
          "How is my medical data protected?"
        ]
      },
      {
        id: "schedules",
        persona: "both",
        question: "How do I check doctor schedules and open slots?",
        keywords: ["schedule", "doctor", "availability", "calendar", "slots", "physician", "clinic schedules"],
        answer:
          "Open <strong>Clinic Schedules</strong> from the homepage or Patient Portal. Filter the monthly calendar by <strong>All Clinics</strong>, <strong>General Medical</strong>, <strong>Neurology</strong>, or <strong>Psychiatry</strong>, and select any open date to view exact consultation hours before booking.",
        followUps: [
          "What departments and clinic hours are available?",
          "How do I book an appointment?",
          "Can I book for a family member or just ask an inquiry?"
        ]
      },
      {
        id: "other-patient-inquiry",
        persona: "both",
        question: "Can I book for a family member or just ask an inquiry?",
        keywords: ["family", "dependent", "other patient", "someone else", "inquiry", "question"],
        answer:
          "Yes. In Step 1 of <strong>Book Appointment</strong>, signed-in patients can book under their locked <strong>Verified Profile (Patient - Me)</strong> or select <strong>Other Patient</strong> for a spouse, child, or dependent. If you only have a question without reserving a clinic slot, check <strong>Inquiry Only</strong> and staff will contact you.",
        followUps: [
          "How do I book an appointment?",
          "How do I track or reschedule my appointment?",
          "Where is MMWGH and how can I contact the clinic?"
        ]
      },
      {
        id: "sdn-facility",
        persona: "both",
        question: "Where is MMWGH and how can I contact the clinic?",
        keywords: ["where", "location", "address", "map", "contact", "phone", "hotline", "sdn", "mmwgh"],
        answer:
          "<strong>Mariveles Mental Wellness and General Hospital</strong> is located at <strong>P. Monroe St., Poblacion, Mariveles, Bataan</strong>. Open the <strong>Location Map</strong> on the homepage to view directions and partner SDN facilities, or call OPU-Medical at <strong>(047) 935-4645</strong> for schedule assistance and verified record corrections.",
        followUps: [
          "What departments and clinic hours are available?",
          "What should I prepare for my consultation?",
          "How do I book an appointment?"
        ]
      },
      {
        id: "dpa-consent",
        persona: "both",
        question: "How is my medical data protected?",
        keywords: ["privacy", "dpa", "consent", "data", "security", "otp", "two-step", "protected", "10173"],
        answer:
          "LINGKOD complies with <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>. Clinical notes and diagnoses are restricted to your attending department, your verified hospital identity is protected from unauthorized edits, and you can enable <strong>Two-Step Verification (Email OTP)</strong> anytime in <strong>Patient Portal &gt; Account Settings</strong>.",
        followUps: [
          "How do I track or reschedule my appointment?",
          "How do I book an appointment?",
          "Where is MMWGH and how can I contact the clinic?"
        ]
      }
    ],
    TL: [
      {
        id: "request-appointment",
        persona: "both",
        question: "Paano ako mag-book ng appointment?",
        keywords: ["book", "request", "appointment", "iskedyul", "magpa-schedule", "slot"],
        answer:
          "Pindutin ang <strong>Book Appointment</strong>, tanggapin ang Data Privacy notice, at sagutan ang 4-step form (Personal Info, Medical Info, Appointment Details, at Confirmation). Maaaring mag-book mula <strong>Lunes hanggang Sabado</strong> (hindi bababa sa 1 araw bago ang konsulta) para sa Morning o Afternoon session. Magsisimula ito bilang <strong>For Approval</strong> habang bineberipika ng staff ang iskedyul ng doktor.",
        followUps: [
          "Paano gumagana ang Teleconsultation para sa returning patients?",
          "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
          "Paano ko masusuri o mare-reschedule ang aking appointment?"
        ]
      },
      {
        id: "teleconsultation-flow",
        persona: "both",
        question: "Paano gumagana ang Teleconsultation para sa returning patients?",
        keywords: ["teleconsult", "teleconsultation", "online", "video", "returning", "remote"],
        answer:
          "Ang mga first-time patients ay kinakailangan munang mag-<strong>Face-to-Face</strong> na konsultasyon. Kapag may kahit isang <strong>completed</strong> visit ka na, bubukas ang <strong>Teleconsultation</strong> sa Step 3 ng booking. Kapag naaprubahan ng staff, makikita ang iyong <strong>meeting link</strong> (Google Meet/Zoom) o tagubilin sa tawag nang direkta sa iyong <strong>Patient Portal</strong>.",
        followUps: [
          "Paano ako mag-book ng appointment?",
          "Ano ang dapat kong ihanda para sa aking konsultasyon?",
          "Paano ko masusuri o mare-reschedule ang aking appointment?"
        ]
      },
      {
        id: "hospital-hours",
        persona: "both",
        question: "Anong mga departamento at oras ng klinika ang bukas?",
        keywords: ["departamento", "oras", "operasyon", "bukas", "general medical", "neurology", "psychiatry", "emergency"],
        answer:
          "Bukas ang MMWGH Outpatient Clinics para sa <strong>General Medical</strong>, <strong>Neurology</strong>, at <strong>Psychiatry</strong> mula <strong>Lunes hanggang Sabado, 8:00 AM – 5:00 PM</strong> (Morning: 8:00 AM – 12:00 PM; Afternoon: 1:00 PM – 5:00 PM; sarado tuwing Linggo). Para sa agarang emergency, bukas 24/7 ang <strong>Emergency Triage</strong>.",
        followUps: [
          "Paano ko makikita ang iskedyul ng mga doktor?",
          "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?"
        ]
      },
      {
        id: "portal-status",
        persona: "both",
        question: "Paano ko masusuri o mare-reschedule ang aking appointment?",
        keywords: ["portal", "status", "reschedule", "slip", "qr", "login", "account"],
        answer:
          "Mag-sign in sa <strong>Patient Portal</strong> gamit ang iyong username o email at password. Makikita rito ang status (<strong>For Approval</strong>, <strong>Confirmed</strong>, <strong>Checked In</strong>, <strong>Completed</strong>), nada-download ang <strong>Appointment/Teleconsultation Slip</strong> at <strong>Digital Patient ID</strong>, at maaaring mag-reschedule habang <strong>For Approval</strong> pa ang iyong iskedyul.",
        followUps: [
          "Ano ang dapat kong ihanda para sa aking konsultasyon?",
          "Paano gumagana ang Teleconsultation para sa returning patients?",
          "Paano pinoprotektahan ang aking medical data?"
        ]
      },
      {
        id: "consultation-requirements",
        persona: "both",
        question: "Ano ang dapat kong ihanda para sa aking konsultasyon?",
        keywords: ["dalhin", "ihanda", "dokumento", "id", "slip", "reseta", "lab"],
        answer:
          "Para sa <strong>Face-to-Face</strong> na konsultasyon, magdala ng valid ID, printable <strong>Appointment Slip</strong> o <strong>Digital Patient ID (QR)</strong> mula sa Patient Portal, at mga nakaraang reseta o lab results. Para sa <strong>Teleconsultation</strong>, panatilihing bukas ang iyong cellphone at tingnan ang Patient Portal 5–10 minuto bago ang iyong iskedyul.",
        followUps: [
          "Paano gumagana ang Teleconsultation para sa returning patients?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
          "Paano pinoprotektahan ang aking medical data?"
        ]
      },
      {
        id: "schedules",
        persona: "both",
        question: "Paano ko makikita ang iskedyul ng mga doktor?",
        keywords: ["iskedyul", "doktor", "calendar", "slots", "availability"],
        answer:
          "Buksan ang <strong>Clinic Schedules</strong> sa homepage o Patient Portal. Maaari mong i-filter ang buwanang kalendaryo sa <strong>General Medical</strong>, <strong>Neurology</strong>, o <strong>Psychiatry</strong> at i-click ang bukas na petsa upang makita ang oras ng mga doktor.",
        followUps: [
          "Anong mga departamento at oras ng klinika ang bukas?",
          "Paano ako mag-book ng appointment?",
          "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?"
        ]
      },
      {
        id: "other-patient-inquiry",
        persona: "both",
        question: "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
        keywords: ["kapamilya", "dependent", "other patient", "inquiry", "magtanong"],
        answer:
          "Oo. Sa Step 1 ng <strong>Book Appointment</strong>, maaaring gamitin ng signed-in patient ang kanyang naka-lock na <strong>Verified Profile (Patient - Me)</strong> o piliin ang <strong>Other Patient</strong> para sa asawa, anak, o kaanak. Kung magtatanong lamang nang walang clinic slot, i-check ang <strong>Inquiry Only</strong>.",
        followUps: [
          "Paano ako mag-book ng appointment?",
          "Paano ko masusuri o mare-reschedule ang aking appointment?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?"
        ]
      },
      {
        id: "sdn-facility",
        persona: "both",
        question: "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
        keywords: ["saan", "lokasyon", "mapa", "contact", "telepono", "hotline", "mmwgh"],
        answer:
          "Matatagpuan ang <strong>Mariveles Mental Wellness and General Hospital</strong> sa <strong>P. Monroe St., Poblacion, Mariveles, Bataan</strong>. Tingnan ang <strong>Location Map</strong> sa homepage para sa direksyon at partner SDN facilities, o tumawag sa OPU-Medical sa <strong>(047) 935-4645</strong>.",
        followUps: [
          "Anong mga departamento at oras ng klinika ang bukas?",
          "Ano ang dapat kong ihanda para sa aking konsultasyon?",
          "Paano ako mag-book ng appointment?"
        ]
      },
      {
        id: "dpa-consent",
        persona: "both",
        question: "Paano pinoprotektahan ang aking medical data?",
        keywords: ["privacy", "dpa", "consent", "datos", "seguridad", "otp", "10173"],
        answer:
          "Sumusunod ang LINGKOD sa <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>. Ang iyong mga clinical notes ay nakahiwalay sa bawat departamento, protektado ang iyong verified hospital profile, at maaari kang mag-enable ng <strong>Two-Step Verification (Email OTP)</strong> sa <strong>Patient Portal &gt; Account Settings</strong>.",
        followUps: [
          "Paano ko masusuri o mare-reschedule ang aking appointment?",
          "Paano ako mag-book ng appointment?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?"
        ]
      }
    ]
  };

  const faqs = {
    EN: defaultFaqs.EN.slice(),
    TL: defaultFaqs.TL.slice()
  };

  const ALLOWED_CHAT_TAGS = new Set(['STRONG', 'B', 'EM', 'BR']);
  function escapeText(str) {
    return String(str ?? '').replace(/[<>&"']/g, (ch) => ({
      '<': '&lt;',
      '>': '&gt;',
      '&': '&amp;',
      '"': '&quot;',
      "'": '&#39;'
    }[ch]));
  }

  function sanitizeChatHtml(raw) {
    const str = String(raw ?? '');
    if (typeof DOMParser === 'undefined') return escapeText(str);
    const doc = new DOMParser().parseFromString(`<div>${str}</div>`, 'text/html');
    const root = doc.body.firstElementChild;
    if (!root) return '';
    const serialize = (node) => {
      if (node.nodeType === 3) return escapeText(node.textContent || '');
      if (node.nodeType !== 1) return '';
      const tag = node.tagName.toUpperCase();
      if (tag === 'SCRIPT' || tag === 'STYLE') return '';
      const inner = Array.from(node.childNodes).map(serialize).join('');
      if (!ALLOWED_CHAT_TAGS.has(tag)) return inner;
      if (tag === 'BR') return '<br>';
      const lower = tag.toLowerCase();
      return `<${lower}>${inner}</${lower}>`;
    };
    return Array.from(root.childNodes).map(serialize).join('');
  }

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
      <div class="chibi-chat-window" id="chibiChatWindow" data-assistant="girl" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="chibi-chat-header">
          <div class="chibi-header-identity" id="headerInfoToggle">
            <div class="chibi-header-icon-wrap" id="chibiHeaderIconWrap">
              ${HEADER_ICONS[chatAssistant]}
            </div>
            <div class="chibi-header-copy">
              <h3 id="chibiHeaderName">${ASSISTANTS[chatAssistant].name[currentLang]}</h3>
              <p id="chibiHeaderRole">${ASSISTANTS[chatAssistant].role[currentLang]}</p>
            </div>
          </div>

          <div class="chibi-header-actions">
            <div class="chibi-lang-dropdown">
              <button type="button" class="chibi-lang-trigger" id="chibiLangTrigger" aria-label="Switch Language" aria-haspopup="menu" aria-expanded="false">
                <span id="chibiLangCode">${currentLang}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div class="chibi-lang-menu" id="chibiLangMenu" role="menu" hidden>
                <button type="button" class="chibi-lang-option active" role="menuitem" data-lang="EN" id="btnLangEN">EN (English)</button>
                <button type="button" class="chibi-lang-option" role="menuitem" data-lang="TL" id="btnLangTL">TL (Tagalog)</button>
              </div>
            </div>
            <button type="button" class="chibi-close-btn" id="chibiCloseBtn" aria-label="Close Chat">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
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
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openChatWith(activeChibi);
      }
    });
    closeBtn.addEventListener('click', toggleChat);

    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape' || !isOpen) return;
      if (langMenu && !langMenu.hasAttribute('hidden')) {
        langMenu.setAttribute('hidden', '');
        if (langTrigger) {
          langTrigger.setAttribute('aria-expanded', 'false');
          langTrigger.focus();
        }
        return;
      }
      toggleChat();
    });

    if (langTrigger && langMenu) {
      langTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = langMenu.hasAttribute('hidden');
        if (isHidden) {
          langMenu.removeAttribute('hidden');
          langTrigger.setAttribute('aria-expanded', 'true');
        } else {
          langMenu.setAttribute('hidden', '');
          langTrigger.setAttribute('aria-expanded', 'false');
        }
      });

      document.addEventListener('click', () => {
        if (!langMenu.hasAttribute('hidden')) {
          langMenu.setAttribute('hidden', '');
          langTrigger.setAttribute('aria-expanded', 'false');
        }
      });
    }

    btnEN.addEventListener('click', (e) => {
      e.stopPropagation();
      if (langMenu) langMenu.setAttribute('hidden', '');
      if (langTrigger) langTrigger.setAttribute('aria-expanded', 'false');
      switchLanguage('EN');
    });
    btnTL.addEventListener('click', (e) => {
      e.stopPropagation();
      if (langMenu) langMenu.setAttribute('hidden', '');
      if (langTrigger) langTrigger.setAttribute('aria-expanded', 'false');
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

    hydrateLiveFaqs();
  }

  async function hydrateLiveFaqs() {
    try {
      const res = await fetch('/sdn/api/admin-settings-router.php?route=/public/chatbot-faqs', {
        headers: { Accept: 'application/json' }
      });
      if (!res.ok) return;
      const json = await res.json();
      const data = json && json.data;
      if (!data) return;

      ['EN', 'TL'].forEach((lang) => {
        if (Array.isArray(data[lang]) && data[lang].length > 0) {
          const liveItems = data[lang].map((item) => ({
            ...item,
            keywords: Array.isArray(item.keywords) ? item.keywords : [],
            followUps: Array.isArray(item.followUps) ? item.followUps : []
          }));
          if (liveItems.length < 4) {
            const seenIds = new Set(liveItems.map((i) => String(i.id || i.slug || '').toLowerCase()));
            for (const fallback of defaultFaqs[lang]) {
              if (!seenIds.has(fallback.id)) liveItems.push(fallback);
            }
          }
          faqs[lang] = liveItems;
          topics[lang] = liveItems.slice(0, 4).map((item) => item.question);
        }
      });

      if (isOpen && showingMainTopics) {
        renderChatBody(false);
      }
    } catch (_) {
      // Silent fallback to bundled EN/TL FAQs when API is offline
    }
  }

  function matchesAssistantPersona(item) {
    if (!item || !item.persona || item.persona === 'both') return true;
    return chatAssistant === 'boy' ? item.persona === 'dr_leo' : item.persona === 'nurse_mika';
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
    const windowEl = document.getElementById('chibiChatWindow');
    if (windowEl) windowEl.setAttribute('data-assistant', chatAssistant);
  }

  function getFaqSlug(item) {
    return item ? String(item.slug || item.id || '').toLowerCase() : '';
  }

  function findFaqBySlug(lang, slug) {
    if (!slug) return null;
    const key = String(slug).toLowerCase();
    return (faqs[lang] || []).find((item) => getFaqSlug(item) === key) || null;
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

    const prof = ASSISTANTS[chatAssistant];
    messages = messages.map((m) => {
      if (m.isInitial) {
        return { ...m, text: prof.welcome[currentLang], name: prof.name[currentLang] };
      }
      if (m.sender === 'user') {
        const translated = findFaqBySlug(currentLang, m.faqId);
        return translated ? { ...m, text: translated.question } : m;
      }
      const translated = findFaqBySlug(currentLang, m.faqId);
      if (translated) {
        return {
          ...m,
          name: prof.name[currentLang],
          questionKey: translated.question.toLowerCase().trim(),
          text: translated.answer,
          followUps: translated.followUps || []
        };
      }
      if (m.unmatched) {
        return {
          ...m,
          name: prof.name[currentLang],
          text: dict[currentLang].defaultUnmatched,
          followUps: dict[currentLang].defaultFollowUps
        };
      }
      return { ...m, name: prof.name[currentLang] };
    });

    const chatBody = document.getElementById('chibiChatBody');
    const prevScroll = chatBody ? chatBody.scrollTop : 0;
    renderChatBody(false);
    if (chatBody) chatBody.scrollTop = prevScroll;
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
            <img src="${escapeText(m.avatar)}" alt="${escapeText(m.name)}" />
          </div>
          <div class="chibi-msg-box">
            <div class="chibi-msg-bubble bot">
              ${sanitizeChatHtml(m.text)}
            </div>
            <span class="chibi-msg-time">${escapeText(m.time)}</span>
          </div>
        `;
        chatBody.appendChild(row);

        // Initial 4 Topic Cards at original top area (only when showingMainTopics is true)
        if (m.isInitial && showingMainTopics) {
          const personaTopics = (faqs[currentLang] || [])
            .filter(matchesAssistantPersona)
            .slice(0, 4)
            .map((item) => item.question);
          const displayTopics = personaTopics.length > 0 ? personaTopics : topics[currentLang];
          const cardContainer = document.createElement('div');
          cardContainer.className = 'chibi-faq-container';
          cardContainer.id = 'initialTopicsList';
          cardContainer.innerHTML = `
            <div class="chibi-faq-label">${escapeText(dict[currentLang].selectQuestion)}</div>
            <div class="chibi-faq-list">
              ${displayTopics.map(topic => `
                <button type="button" class="chibi-faq-item" data-question="${escapeText(topic)}">
                  <span class="chibi-faq-text">${escapeText(topic)}</span>
                  <svg class="chibi-faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
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
            <div class="chibi-faq-label">${escapeText(dict[currentLang].followUpLabel)}</div>
            <div class="chibi-faq-list">
              ${m.followUps.map(chip => `
                <button type="button" class="chibi-chip-btn" data-question="${escapeText(chip)}">
                  <span class="chibi-faq-text">${escapeText(chip)}</span>
                  <svg class="chibi-faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
              `).join('')}
            </div>
            <button type="button" class="chibi-back-topics-btn">
              ${escapeText(dict[currentLang].backToTopics)}
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
              ${escapeText(m.text)}
            </div>
            <span class="chibi-msg-time">${escapeText(m.time)}</span>
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
    const list = (faqs[currentLang] || []).filter(matchesAssistantPersona);
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
    const preMatch = findBestMatch(queryText);
    const matchSlug = getFaqSlug(preMatch);
    const existingBot = messages.find(
      m => m.sender === 'bot' && (m.questionKey === cleanKey || (matchSlug && m.faqId === matchSlug))
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
      faqId: matchSlug || null,
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

      const match = (matchSlug && findFaqBySlug(currentLang, matchSlug)) || findBestMatch(queryText);
      const resolvedSlug = getFaqSlug(match) || matchSlug || null;
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
        questionKey: match ? match.question.toLowerCase().trim() : cleanKey,
        faqId: resolvedSlug,
        unmatched: !match,
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
