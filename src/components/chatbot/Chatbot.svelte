<script>
  import { tick } from "svelte";

  const ALLOWED_CHAT_TAGS = new Set(["STRONG", "B", "EM", "BR"]);
  function sanitizeChatHtml(raw) {
    const str = String(raw ?? "");
    if (typeof DOMParser === "undefined") {
      return str.replace(/[<>&"']/g, (ch) => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;", '"': "&quot;", "'": "&#39;" }[ch]));
    }
    const doc = new DOMParser().parseFromString(`<div>${str}</div>`, "text/html");
    const root = doc.body.firstElementChild;
    if (!root) return "";
    const serialize = (node) => {
      if (node.nodeType === 3) {
        return (node.textContent || "").replace(/[<>&"']/g, (ch) => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;", '"': "&quot;", "'": "&#39;" }[ch]));
      }
      if (node.nodeType !== 1) return "";
      const tag = node.tagName.toUpperCase();
      if (tag === "SCRIPT" || tag === "STYLE") return "";
      const inner = Array.from(node.childNodes).map(serialize).join("");
      if (!ALLOWED_CHAT_TAGS.has(tag)) return inner;
      if (tag === "BR") return "<br>";
      const lower = tag.toLowerCase();
      return `<${lower}>${inner}</${lower}>`;
    };
    return Array.from(root.childNodes).map(serialize).join("");
  }

  let isOpen = true;
  let lang = "EN";
  let assistant = "girl";
  let langMenuOpen = false;
  let isTyping = false;
  let showingMainTopics = true;
  let activeBotMsgId = "initial-welcome";
  let highlightedMsgId = null;
  let chatBodyRef;
  let launcherBtnRef;
  let closeBtnRef;
  let langBtnRef;

  const assistants = {
    girl: {
      name: "Nurse Mika",
      avatar: "/sdn/public/assets/chibi_girl_avatar.png",
    },
    boy: {
      name: "Dr. Leo",
      avatar: "/sdn/public/assets/chibi_boy_avatar.png",
    },
  };

  const topics = {
    EN: [
      "How do I book an appointment?",
      "How does Teleconsultation work for returning patients?",
      "What departments and clinic hours are available?",
      "How do I track or reschedule my appointment?",
    ],
    TL: [
      "Paano ako mag-book ng appointment?",
      "Paano gumagana ang Teleconsultation para sa returning patients?",
      "Anong mga departamento at oras ng klinika ang bukas?",
      "Paano ko masusuri o mare-reschedule ang aking appointment?",
    ],
  };

  const faqs = {
    EN: [
      {
        id: "request-appointment",
        question: "How do I book an appointment?",
        keywords: ["book", "request", "appointment", "schedule", "reserve", "slot", "register"],
        answer:
          "Click <strong>Book Appointment</strong>, review and accept the Data Privacy notice, then complete the 4-step form (Personal Info, Medical Info, Appointment Details, and Confirmation). Online appointments can be booked <strong>Monday to Saturday</strong> at least 1 day ahead for Morning or Afternoon sessions. Your request starts as <strong>For Approval</strong> while clinic staff verifies physician availability.",
        followUps: [
          "How does Teleconsultation work for returning patients?",
          "Can I book for a family member or just ask an inquiry?",
          "How do I track or reschedule my appointment?",
        ],
      },
      {
        id: "teleconsultation-flow",
        question: "How does Teleconsultation work for returning patients?",
        keywords: ["teleconsult", "teleconsultation", "online", "video", "returning", "remote", "google meet", "zoom"],
        answer:
          "First-time patients are required to complete an initial <strong>Face-to-Face</strong> visit. Once you have at least one <strong>completed</strong> hospital consultation, <strong>Teleconsultation</strong> unlocks in Step 3 of booking. After staff approves your visit, your session link (e.g., Google Meet/Zoom) or clinic call instructions appear directly on your appointment card in the <strong>Patient Portal</strong>.",
        followUps: [
          "How do I book an appointment?",
          "What should I prepare for my consultation?",
          "How do I track or reschedule my appointment?",
        ],
      },
      {
        id: "hospital-hours",
        question: "What departments and clinic hours are available?",
        keywords: ["department", "hours", "operating", "open", "opd", "general medical", "neurology", "psychiatry", "emergency"],
        answer:
          "MMWGH Outpatient Clinics serve <strong>General Medical</strong>, <strong>Neurology</strong>, and <strong>Psychiatry</strong> from <strong>Monday to Saturday, 8:00 AM – 5:00 PM</strong> (Morning: 8:00 AM – 12:00 PM; Afternoon: 1:00 PM – 5:00 PM; closed Sundays). For urgent emergencies, our <strong>Emergency Triage</strong> is open 24/7.",
        followUps: [
          "How do I check doctor schedules and open slots?",
          "Can I book for a family member or just ask an inquiry?",
          "Where is MMWGH and how can I contact the clinic?",
        ],
      },
      {
        id: "portal-status",
        question: "How do I track or reschedule my appointment?",
        keywords: ["portal", "track", "status", "reschedule", "slip", "qr", "login", "account"],
        answer:
          "Sign in to the <strong>Patient Portal</strong> using your username or email and password. You can check real-time status (<strong>For Approval</strong>, <strong>Confirmed</strong>, <strong>Checked In</strong>, <strong>Completed</strong>), download your <strong>Appointment/Teleconsultation Slip</strong> and <strong>Digital Patient ID</strong>, and reschedule directly while your visit is still <strong>For Approval</strong>.",
        followUps: [
          "What should I prepare for my consultation?",
          "How does Teleconsultation work for returning patients?",
          "How is my medical data protected?",
        ],
      },
      {
        id: "consultation-requirements",
        question: "What should I prepare for my consultation?",
        keywords: ["bring", "prepare", "requirements", "id", "slip", "triage", "documents", "prescription"],
        answer:
          "For <strong>Face-to-Face</strong> visits, bring a valid ID, your printable <strong>Appointment Slip</strong> or <strong>Digital Patient ID (QR)</strong> from the Patient Portal, and any prior prescriptions or lab results. For <strong>Teleconsultation</strong>, keep your active mobile number reachable and open your Patient Portal 5–10 minutes before your scheduled block.",
        followUps: [
          "How does Teleconsultation work for returning patients?",
          "Where is MMWGH and how can I contact the clinic?",
          "How is my medical data protected?",
        ],
      },
      {
        id: "schedules",
        question: "How do I check doctor schedules and open slots?",
        keywords: ["schedule", "doctor", "availability", "calendar", "slots", "physician", "clinic schedules"],
        answer:
          "Open <strong>Clinic Schedules</strong> from the homepage or Patient Portal. Filter the monthly calendar by <strong>All Clinics</strong>, <strong>General Medical</strong>, <strong>Neurology</strong>, or <strong>Psychiatry</strong>, and select any open date to view exact consultation hours before booking.",
        followUps: [
          "What departments and clinic hours are available?",
          "How do I book an appointment?",
          "Can I book for a family member or just ask an inquiry?",
        ],
      },
      {
        id: "other-patient-inquiry",
        question: "Can I book for a family member or just ask an inquiry?",
        keywords: ["family", "dependent", "other patient", "someone else", "inquiry", "question"],
        answer:
          "Yes. In Step 1 of <strong>Book Appointment</strong>, signed-in patients can book under their locked <strong>Verified Profile (Patient - Me)</strong> or select <strong>Other Patient</strong> for a spouse, child, or dependent. If you only have a question without reserving a clinic slot, check <strong>Inquiry Only</strong> and staff will contact you.",
        followUps: [
          "How do I book an appointment?",
          "How do I track or reschedule my appointment?",
          "Where is MMWGH and how can I contact the clinic?",
        ],
      },
      {
        id: "sdn-facility",
        question: "Where is MMWGH and how can I contact the clinic?",
        keywords: ["where", "location", "address", "map", "contact", "phone", "hotline", "sdn", "mmwgh"],
        answer:
          "<strong>Mariveles Mental Wellness and General Hospital</strong> is located at <strong>P. Monroe St., Poblacion, Mariveles, Bataan</strong>. Open the <strong>Location Map</strong> on the homepage to view directions and partner SDN facilities, or call OPU-Medical at <strong>(047) 935-4645</strong> for schedule assistance and verified record corrections.",
        followUps: [
          "What departments and clinic hours are available?",
          "What should I prepare for my consultation?",
          "How do I book an appointment?",
        ],
      },
      {
        id: "dpa-consent",
        question: "How is my medical data protected?",
        keywords: ["privacy", "dpa", "consent", "data", "security", "otp", "two-step", "protected", "10173"],
        answer:
          "LINGKOD complies with <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>. Clinical notes and diagnoses are restricted to your attending department, your verified hospital identity is protected from unauthorized edits, and you can enable <strong>Two-Step Verification (Email OTP)</strong> anytime in <strong>Patient Portal &gt; Account Settings</strong>.",
        followUps: [
          "How do I track or reschedule my appointment?",
          "How do I book an appointment?",
          "Where is MMWGH and how can I contact the clinic?",
        ],
      },
    ],
    TL: [
      {
        id: "request-appointment",
        question: "Paano ako mag-book ng appointment?",
        keywords: ["book", "request", "appointment", "iskedyul", "magpa-schedule", "slot"],
        answer:
          "Pindutin ang <strong>Book Appointment</strong>, tanggapin ang Data Privacy notice, at sagutan ang 4-step form (Personal Info, Medical Info, Appointment Details, at Confirmation). Maaaring mag-book mula <strong>Lunes hanggang Sabado</strong> (hindi bababa sa 1 araw bago ang konsulta) para sa Morning o Afternoon session. Magsisimula ito bilang <strong>For Approval</strong> habang bineberipika ng staff ang iskedyul ng doktor.",
        followUps: [
          "Paano gumagana ang Teleconsultation para sa returning patients?",
          "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
          "Paano ko masusuri o mare-reschedule ang aking appointment?",
        ],
      },
      {
        id: "teleconsultation-flow",
        question: "Paano gumagana ang Teleconsultation para sa returning patients?",
        keywords: ["teleconsult", "teleconsultation", "online", "video", "returning", "remote"],
        answer:
          "Ang mga first-time patients ay kinakailangan munang mag-<strong>Face-to-Face</strong> na konsultasyon. Kapag may kahit isang <strong>completed</strong> visit ka na, bubukas ang <strong>Teleconsultation</strong> sa Step 3 ng booking. Kapag naaprubahan ng staff, makikita ang iyong <strong>meeting link</strong> (Google Meet/Zoom) o tagubilin sa tawag nang direkta sa iyong <strong>Patient Portal</strong>.",
        followUps: [
          "Paano ako mag-book ng appointment?",
          "Ano ang dapat kong ihanda para sa aking konsultasyon?",
          "Paano ko masusuri o mare-reschedule ang aking appointment?",
        ],
      },
      {
        id: "hospital-hours",
        question: "Anong mga departamento at oras ng klinika ang bukas?",
        keywords: ["departamento", "oras", "operasyon", "bukas", "general medical", "neurology", "psychiatry", "emergency"],
        answer:
          "Bukas ang MMWGH Outpatient Clinics para sa <strong>General Medical</strong>, <strong>Neurology</strong>, at <strong>Psychiatry</strong> mula <strong>Lunes hanggang Sabado, 8:00 AM – 5:00 PM</strong> (Morning: 8:00 AM – 12:00 PM; Afternoon: 1:00 PM – 5:00 PM; sarado tuwing Linggo). Para sa agarang emergency, bukas 24/7 ang <strong>Emergency Triage</strong>.",
        followUps: [
          "Paano ko makikita ang iskedyul ng mga doktor?",
          "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
        ],
      },
      {
        id: "portal-status",
        question: "Paano ko masusuri o mare-reschedule ang aking appointment?",
        keywords: ["portal", "status", "reschedule", "slip", "qr", "login", "account"],
        answer:
          "Mag-sign in sa <strong>Patient Portal</strong> gamit ang iyong username o email at password. Makikita rito ang status (<strong>For Approval</strong>, <strong>Confirmed</strong>, <strong>Checked In</strong>, <strong>Completed</strong>), nada-download ang <strong>Appointment/Teleconsultation Slip</strong> at <strong>Digital Patient ID</strong>, at maaaring mag-reschedule habang <strong>For Approval</strong> pa ang iyong iskedyul.",
        followUps: [
          "Ano ang dapat kong ihanda para sa aking konsultasyon?",
          "Paano gumagana ang Teleconsultation para sa returning patients?",
          "Paano pinoprotektahan ang aking medical data?",
        ],
      },
      {
        id: "consultation-requirements",
        question: "Ano ang dapat kong ihanda para sa aking konsultasyon?",
        keywords: ["dalhin", "ihanda", "dokumento", "id", "slip", "reseta", "lab"],
        answer:
          "Para sa <strong>Face-to-Face</strong> na konsultasyon, magdala ng valid ID, printable <strong>Appointment Slip</strong> o <strong>Digital Patient ID (QR)</strong> mula sa Patient Portal, at mga nakaraang reseta o lab results. Para sa <strong>Teleconsultation</strong>, panatilihing bukas ang iyong cellphone at tingnan ang Patient Portal 5–10 minuto bago ang iyong iskedyul.",
        followUps: [
          "Paano gumagana ang Teleconsultation para sa returning patients?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
          "Paano pinoprotektahan ang aking medical data?",
        ],
      },
      {
        id: "schedules",
        question: "Paano ko makikita ang iskedyul ng mga doktor?",
        keywords: ["iskedyul", "doktor", "calendar", "slots", "availability"],
        answer:
          "Buksan ang <strong>Clinic Schedules</strong> sa homepage o Patient Portal. Maaari mong i-filter ang buwanang kalendaryo sa <strong>General Medical</strong>, <strong>Neurology</strong>, o <strong>Psychiatry</strong> at i-click ang bukas na petsa upang makita ang oras ng mga doktor.",
        followUps: [
          "Anong mga departamento at oras ng klinika ang bukas?",
          "Paano ako mag-book ng appointment?",
          "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
        ],
      },
      {
        id: "other-patient-inquiry",
        question: "Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?",
        keywords: ["kapamilya", "dependent", "other patient", "inquiry", "magtanong"],
        answer:
          "Oo. Sa Step 1 ng <strong>Book Appointment</strong>, maaaring gamitin ng signed-in patient ang kanyang naka-lock na <strong>Verified Profile (Patient - Me)</strong> o piliin ang <strong>Other Patient</strong> para sa asawa, anak, o kaanak. Kung magtatanong lamang nang walang clinic slot, i-check ang <strong>Inquiry Only</strong>.",
        followUps: [
          "Paano ako mag-book ng appointment?",
          "Paano ko masusuri o mare-reschedule ang aking appointment?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
        ],
      },
      {
        id: "sdn-facility",
        question: "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
        keywords: ["saan", "lokasyon", "mapa", "contact", "telepono", "hotline", "mmwgh"],
        answer:
          "Matatagpuan ang <strong>Mariveles Mental Wellness and General Hospital</strong> sa <strong>P. Monroe St., Poblacion, Mariveles, Bataan</strong>. Tingnan ang <strong>Location Map</strong> sa homepage para sa direksyon at partner SDN facilities, o tumawag sa OPU-Medical sa <strong>(047) 935-4645</strong>.",
        followUps: [
          "Anong mga departamento at oras ng klinika ang bukas?",
          "Ano ang dapat kong ihanda para sa aking konsultasyon?",
          "Paano ako mag-book ng appointment?",
        ],
      },
      {
        id: "dpa-consent",
        question: "Paano pinoprotektahan ang aking medical data?",
        keywords: ["privacy", "dpa", "consent", "datos", "seguridad", "otp", "10173"],
        answer:
          "Sumusunod ang LINGKOD sa <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>. Ang iyong mga clinical notes ay nakahiwalay sa bawat departamento, protektado ang iyong verified hospital profile, at maaari kang mag-enable ng <strong>Two-Step Verification (Email OTP)</strong> sa <strong>Patient Portal &gt; Account Settings</strong>.",
        followUps: [
          "Paano ko masusuri o mare-reschedule ang aking appointment?",
          "Paano ako mag-book ng appointment?",
          "Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?",
        ],
      },
    ],
  };

  const uiText = {
    EN: {
      role: "LINGKOD ASSISTANT",
      welcome: (name) => `Hello! I am ${name}, your LINGKOD assistant. How can I help you today?`,
      chooseTopic: "TOPICS YOU CAN ASK",
      followUpTitle: "FOLLOW-UP QUESTIONS",
      backToTopics: "← Back to Main Topics",
      closeAria: "Close support chat",
      openAria: "Open LINGKOD Assistant",
      badgeTitle: "LINGKOD • FAQs",
      defaultUnmatched:
        "I could not find an exact match for that question. Choose one of the core LINGKOD appointment topics below:",
      defaultFollowUps: [
        "How do I book an appointment?",
        "How does Teleconsultation work for returning patients?",
        "How do I track or reschedule my appointment?",
      ],
    },
    TL: {
      role: "LINGKOD ASSISTANT",
      welcome: (name) => `Kumusta! Ako si ${name}, inyong LINGKOD assistant. Paano ko kayo matutulungan ngayon?`,
      chooseTopic: "MGA PAKSANG MAAARING ITANONG",
      followUpTitle: "MGA KAUGNAY NA TANONG",
      backToTopics: "← Bumalik sa Pangunahing Paksa",
      closeAria: "Isara ang support chat",
      openAria: "Buksan ang LINGKOD Assistant",
      badgeTitle: "LINGKOD • FAQs",
      defaultUnmatched:
        "Hindi nahanap ang eksaktong sagot para sa inyong tanong. Pumili sa mga pangunahing paksa sa ibaba:",
      defaultFollowUps: [
        "Paano ako mag-book ng appointment?",
        "Paano gumagana ang Teleconsultation para sa returning patients?",
        "Paano ko masusuri o mare-reschedule ang aking appointment?",
      ],
    },
  };

  function getCurrentTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
    const dateStr = now.toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    return `${timeStr} | ${dateStr}`;
  }

  function toggleAssistant() {
    assistant = assistant === "girl" ? "boy" : "girl";
    if (messages.length === 1 && messages[0].isInitial) {
      messages = [
        {
          ...messages[0],
          text: uiText[lang].welcome(assistants[assistant].name),
        },
      ];
    }
  }

  let messages = [
    {
      id: "initial-welcome",
      sender: "bot",
      text: uiText.EN.welcome(assistants.girl.name),
      time: getCurrentTime(),
      isInitial: true,
      followUps: [],
    },
  ];

  async function scrollToBottom() {
    await tick();
    if (chatBodyRef) {
      chatBodyRef.scrollTop = chatBodyRef.scrollHeight;
    }
  }

  function findBestMatch(queryText) {
    const list = faqs[lang];
    const clean = queryText.toLowerCase().trim();

    const direct = list.find(
      (item) =>
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
        if (clean.includes(kw.toLowerCase())) {
          score += 1;
        }
      }
      if (score > bestScore) {
        bestScore = score;
        selected = item;
      }
    }

    return bestScore > 0 ? selected : null;
  }

  function handleSelectQuestion(questionText) {
    if (isTyping) return;
    submitQuery(questionText);
  }

  function findFaqById(targetLang, faqId) {
    if (!faqId) return null;
    return (faqs[targetLang] || []).find((item) => item.id === faqId) || null;
  }

  async function submitQuery(queryText) {
    const cleanKey = queryText.toLowerCase().trim();
    const preMatch = findBestMatch(queryText);
    const matchId = preMatch ? preMatch.id : null;
    const existingBot = messages.find(
      (m) => m.sender === "bot" && (m.questionKey === cleanKey || (matchId && m.faqId === matchId))
    );

    if (existingBot) {
      showingMainTopics = false;
      activeBotMsgId = existingBot.id;
      highlightedMsgId = existingBot.id;
      await tick();

      if (chatBodyRef) {
        const targetRow = chatBodyRef.querySelector(
          `[data-msg-id="${existingBot.id}"]`
        );
        if (targetRow) {
          targetRow.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }
      setTimeout(() => {
        highlightedMsgId = null;
      }, 1400);
      return;
    }

    showingMainTopics = false;
    activeBotMsgId = null;

    messages = [
      ...messages,
      {
        id: "user-" + Date.now(),
        sender: "user",
        faqId: matchId,
        text: queryText,
        time: getCurrentTime(),
      },
    ];

    scrollToBottom();
    isTyping = true;

    setTimeout(() => {
      const match = (matchId && findFaqById(lang, matchId)) || findBestMatch(queryText);
      const resolvedId = match ? match.id : matchId;
      let replyText = "";
      let chips = [];

      if (match) {
        replyText = match.answer;
        chips = match.followUps || [];
      } else {
        replyText = uiText[lang].defaultUnmatched;
        chips = uiText[lang].defaultFollowUps;
      }

      const newBotId = "bot-" + Date.now();
      messages = [
        ...messages,
        {
          id: newBotId,
          questionKey: match ? match.question.toLowerCase().trim() : cleanKey,
          faqId: resolvedId,
          unmatched: !match,
          sender: "bot",
          text: replyText,
          time: getCurrentTime(),
          followUps: chips,
        },
      ];

      showingMainTopics = false;
      activeBotMsgId = newBotId;
      isTyping = false;
      scrollToBottom();
    }, 350);
  }

  async function handleBackToTopics() {
    showingMainTopics = true;
    activeBotMsgId = "initial-welcome";
    await tick();
    if (chatBodyRef) {
      chatBodyRef.scrollTo({ top: 0, behavior: "smooth" });
    }
  }

  function switchLang(newLang) {
    if (lang === newLang) return;
    lang = newLang;

    messages = messages.map((m) => {
      if (m.isInitial) {
        return { ...m, text: uiText[lang].welcome(assistants[assistant].name) };
      }
      if (m.sender === "user") {
        const translated = findFaqById(lang, m.faqId);
        return translated ? { ...m, text: translated.question } : m;
      }
      const translated = findFaqById(lang, m.faqId);
      if (translated) {
        return {
          ...m,
          questionKey: translated.question.toLowerCase().trim(),
          text: translated.answer,
          followUps: translated.followUps || [],
        };
      }
      if (m.unmatched) {
        return {
          ...m,
          text: uiText[lang].defaultUnmatched,
          followUps: uiText[lang].defaultFollowUps,
        };
      }
      return m;
    });
  }

  async function toggleChat() {
    isOpen = !isOpen;
    await tick();
    if (isOpen) {
      scrollToBottom();
      closeBtnRef?.focus();
    } else {
      langMenuOpen = false;
      launcherBtnRef?.focus();
    }
  }

  async function handleKeydown(event) {
    if (event.key !== "Escape" || !isOpen) return;
    if (langMenuOpen) {
      langMenuOpen = false;
      await tick();
      langBtnRef?.focus();
      return;
    }
    await toggleChat();
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<div class="fixed bottom-6 right-6 z-50 flex flex-col items-end font-sans antialiased max-sm:right-3 max-sm:bottom-3 max-sm:left-3">
  {#if !isOpen}
    <button
      bind:this={launcherBtnRef}
      type="button"
      on:click={toggleChat}
      class="flex min-h-[48px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-left text-slate-900 shadow-[0_6px_20px_rgba(15,23,42,0.1)] transition-colors duration-150 hover:border-[#15803d] hover:bg-[#f0fdf4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#15803d] focus-visible:ring-offset-2 motion-reduce:transition-none"
      aria-label={uiText[lang].openAria}
    >
      <img
        src={assistants[assistant].avatar}
        alt={assistants[assistant].name}
        class="h-9 w-9 shrink-0 rounded-full border border-slate-200 bg-white object-cover"
      />
      <span class="flex flex-col">
        <span class="text-xs font-bold leading-tight text-[#15803d]">{assistants[assistant].name} &middot; {uiText[lang].badgeTitle}</span>
        <span class="text-[11px] font-medium leading-tight text-slate-600">{lang === "TL" ? "Magtanong tungkol sa LINGKOD" : "Ask LINGKOD FAQ & Triage"}</span>
      </span>
    </button>
  {:else}
    <div
      class="relative flex h-[580px] max-h-[calc(100dvh-3rem)] w-[380px] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-[0_12px_32px_rgba(15,23,42,0.12),0_2px_6px_rgba(15,23,42,0.06)] max-sm:h-[82dvh] max-sm:max-h-[680px] max-sm:w-full max-sm:max-w-none"
      role="dialog"
      aria-modal="true"
      aria-label="LINGKOD Chatbot"
    >
      <header class="relative flex shrink-0 items-center justify-between gap-2 border-b border-[#166534] bg-[#15803d] px-4 py-3.5 text-white">
        <button
          type="button"
          on:click={toggleAssistant}
          class="flex min-w-0 items-center gap-2.5 rounded-lg text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          title="Click to switch between Nurse Mika & Dr. Leo"
        >
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-white/15">
            {#if assistant === "girl"}
              <svg class="h-5 w-6" viewBox="0 0 64 42" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M16 13C21 5.5 43 5.5 48 13" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" />
                <path d="M7 17.5C18 10.5 46 10.5 57 17.5L51.5 34.5C40 28 24 28 12.5 34.5L7 17.5Z" stroke="#ffffff" stroke-width="2.6" stroke-linejoin="round" />
                <path d="M30.2 17H33.8V20.8H37.6V24.2H33.8V28H30.2V24.2H26.4V20.8H30.2V17Z" stroke="#ffffff" stroke-width="2.1" stroke-linejoin="round" />
              </svg>
            {:else}
              <svg class="h-5 w-5" viewBox="0 0 56 54" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="20.5" cy="6.5" r="2.3" stroke="#ffffff" stroke-width="2.4" />
                <circle cx="35.5" cy="6.5" r="2.3" stroke="#ffffff" stroke-width="2.4" />
                <path d="M18.5 6.8C13.5 8 12.5 18 17.8 24C21.2 27.8 25.2 29.2 28 29.2C30.8 29.2 34.8 27.8 38.2 24C43.5 18 42.5 8 37.5 6.8" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" />
                <path d="M28 29.2V35.5C28 43 21.5 49 16 48C11.2 47.2 10.8 41.2 16 39.2C21.2 37.2 28.2 43 34.2 47.2C38.5 50.2 43 49 44.5 45.5" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" />
                <circle cx="45.5" cy="41.5" r="5" stroke="#ffffff" stroke-width="2.4" />
              </svg>
            {/if}
          </div>
          <div class="min-w-0">
            <h2 class="truncate text-[15px] font-bold leading-tight text-white">
              {assistants[assistant].name}
            </h2>
            <p class="mt-0.5 text-[10.5px] font-semibold uppercase tracking-[0.06em] text-white/85">
              {uiText[lang].role}
            </p>
          </div>
        </button>

        <div class="flex shrink-0 items-center gap-1.5">
          <div class="relative">
            <button
              bind:this={langBtnRef}
              type="button"
              on:click={() => (langMenuOpen = !langMenuOpen)}
              aria-expanded={langMenuOpen}
              aria-haspopup="menu"
              aria-label="Switch language"
              class="inline-flex h-[34px] items-center gap-1 rounded-lg border border-white/25 bg-white/15 px-2.5 text-xs font-bold leading-none text-white transition-colors hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white motion-reduce:transition-none"
            >
              <span>{lang}</span>
              <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>

            {#if langMenuOpen}
              <div role="menu" class="absolute right-0 top-[calc(100%+6px)] z-50 min-w-[132px] overflow-hidden rounded-[10px] border border-slate-200 bg-white p-1 shadow-[0_8px_20px_rgba(15,23,42,0.12)]">
                <button
                  type="button"
                  role="menuitem"
                  on:click={() => {
                    langMenuOpen = false;
                    switchLang("EN");
                  }}
                  class="block min-h-[36px] w-full rounded-md px-2.5 py-2 text-left text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#15803d] {lang === 'EN'
                    ? 'bg-[#f0fdf4] font-bold text-[#15803d]'
                    : 'font-medium text-slate-700 hover:bg-slate-50'}"
                >
                  EN (English)
                </button>
                <button
                  type="button"
                  role="menuitem"
                  on:click={() => {
                    langMenuOpen = false;
                    switchLang("TL");
                  }}
                  class="block min-h-[36px] w-full rounded-md px-2.5 py-2 text-left text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#15803d] {lang === 'TL'
                    ? 'bg-[#f0fdf4] font-bold text-[#15803d]'
                    : 'font-medium text-slate-700 hover:bg-slate-50'}"
                >
                  TL (Tagalog)
                </button>
              </div>
            {/if}
          </div>

          <button
            bind:this={closeBtnRef}
            type="button"
            on:click={toggleChat}
            class="flex h-[34px] w-[34px] items-center justify-center rounded-lg text-white transition-colors hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white motion-reduce:transition-none"
            aria-label={uiText[lang].closeAria}
          >
            <svg
              class="h-[18px] w-[18px]"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>
      </header>

      <div
        bind:this={chatBodyRef}
        class="flex flex-1 flex-col gap-3.5 overflow-y-auto bg-[#f8fafc] px-4 py-4"
      >
        {#each messages as msg (msg.id)}
          {#if msg.sender === "bot"}
            <div class="flex w-full items-start gap-2.5" data-msg-id={msg.id}>
              <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white">
                <img
                  src={assistants[assistant].avatar}
                  alt={assistants[assistant].name}
                  class="h-full w-full object-cover"
                />
              </div>

              <div class="flex min-w-0 max-w-[82%] flex-col items-start">
                <div
                  class="rounded-[14px] rounded-tl-[4px] border border-slate-200 bg-white px-3.5 py-2.5 text-[13.5px] leading-[1.52] text-slate-900 shadow-[0_1px_2px_rgba(15,23,42,0.04)] {highlightedMsgId === msg.id ? 'bg-[#f0fdf4] outline outline-2 outline-[#15803d]' : ''}"
                >
                  {@html sanitizeChatHtml(msg.text)}
                </div>
                <span class="mt-1 px-0.5 text-[11px] text-slate-500">
                  {msg.time}
                </span>
              </div>
            </div>

            {#if msg.isInitial && showingMainTopics}
              <div data-topics-list class="mt-0.5 flex w-full shrink-0 flex-col items-start">
                <div class="mb-2 pl-0.5 text-[11px] font-bold uppercase tracking-[0.06em] text-slate-500">
                  {uiText[lang].chooseTopic}
                </div>

                <div class="flex w-full flex-col gap-2">
                  {#each topics[lang] as topic}
                    <button
                      type="button"
                      on:click={() => handleSelectQuestion(topic)}
                      class="group flex min-h-[44px] w-full items-center justify-between gap-2.5 rounded-[10px] border border-slate-200 bg-white px-3.5 py-2.5 text-left text-[13px] font-medium leading-[1.4] text-slate-900 transition-colors hover:border-[#86efac] hover:bg-[#f0fdf4] hover:text-[#14532d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#15803d] motion-reduce:transition-none"
                    >
                      <span class="flex-1">{topic}</span>
                      <svg
                        class="h-[15px] w-[15px] shrink-0 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-[#15803d] motion-reduce:transition-none"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                      >
                        <polyline points="9 18 15 12 9 6" />
                      </svg>
                    </button>
                  {/each}
                </div>
              </div>
            {/if}

            {#if !msg.isInitial && !showingMainTopics && msg.id === activeBotMsgId && msg.followUps && msg.followUps.length > 0}
              <div class="mt-0.5 flex w-full shrink-0 flex-col items-start">
                <div class="mb-2 pl-0.5 text-[11px] font-bold uppercase tracking-[0.06em] text-slate-500">
                  {uiText[lang].followUpTitle}
                </div>
                <div class="flex w-full flex-col gap-2">
                  {#each msg.followUps as chip}
                    <button
                      type="button"
                      on:click={() => handleSelectQuestion(chip)}
                      class="group flex min-h-[44px] w-full items-center justify-between gap-2.5 rounded-[10px] border border-slate-200 bg-white px-3.5 py-2.5 text-left text-[13px] font-medium leading-[1.4] text-slate-900 transition-colors hover:border-[#86efac] hover:bg-[#f0fdf4] hover:text-[#14532d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#15803d] motion-reduce:transition-none"
                    >
                      <span class="flex-1">{chip}</span>
                      <svg
                        class="h-[15px] w-[15px] shrink-0 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-[#15803d] motion-reduce:transition-none"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                      >
                        <polyline points="9 18 15 12 9 6" />
                      </svg>
                    </button>
                  {/each}
                </div>

                <button
                  type="button"
                  on:click={handleBackToTopics}
                  class="mt-2.5 min-h-[40px] w-full rounded-[10px] border border-slate-300 bg-white px-3.5 py-2 text-center text-[12.5px] font-semibold leading-tight text-slate-700 transition-colors hover:border-[#15803d] hover:bg-[#f0fdf4] hover:text-[#15803d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#15803d] motion-reduce:transition-none"
                >
                  {uiText[lang].backToTopics}
                </button>
              </div>
            {/if}
          {:else}
            <div class="flex flex-col items-end">
              <div class="max-w-[82%] rounded-[14px] rounded-tr-[4px] bg-[#15803d] px-3.5 py-2.5 text-[13.5px] font-medium leading-[1.45] text-white">
                {msg.text}
              </div>
              <span class="mt-1 px-0.5 text-[11px] text-slate-500">
                {msg.time}
              </span>
            </div>
          {/if}
        {/each}

        {#if isTyping}
          <div class="flex items-center pl-11">
            <div class="inline-flex items-center gap-1 rounded-[14px] rounded-tl-[4px] border border-slate-200 bg-white px-3.5 py-2.5">
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-500 motion-reduce:animate-none"></span>
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-500 [animation-delay:0.15s] motion-reduce:animate-none"></span>
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-500 [animation-delay:0.3s] motion-reduce:animate-none"></span>
            </div>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>
