<script>
  import { tick } from "svelte";

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
      "How do I request an appointment/schedule?",
      "What are the hospital's operating hours & doctor schedules?",
      "Where I can find the nearest MMWGH hospital?",
      "What should I bring for my consultation & EMR triage?",
    ],
    TL: [
      "Paano ako mag-request ng appointment/iskedyul?",
      "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
      "Saan ko makikita ang pinakamalapit na ospital ng MMWGH?",
      "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
    ],
  };

  const faqs = {
    EN: [
      {
        id: "request-appointment",
        question: "How do I request an appointment/schedule?",
        keywords: ["request", "appointment", "schedule", "book", "reserve", "slot"],
        answer:
          "Click <strong>'Book Appointment'</strong> on the homepage, select your desired clinical department, and complete the registration. <strong>Staff Verification Protocol:</strong> Our clinic staff will call your active phone number to confirm your details before placing you in the physician queue.",
        followUps: [
          "Why does clinic staff call before confirming my appointment?",
          "Can I make a same-day appointment or walk-in booking?",
          "What should I bring for my consultation & EMR triage?",
        ],
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
          "Can I make a same-day appointment or walk-in booking?",
        ],
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
          "Why is Data Privacy Act (DPA) consent required?",
        ],
      },
      {
        id: "consultation-requirements",
        question: "What should I bring for my consultation & EMR triage?",
        keywords: ["bring", "consultation", "triage", "emr", "requirements", "documents", "id", "vitals"],
        answer:
          "Please present a valid government, company, or student ID, your mobile number, and prior laboratory tests, prescriptions, or medical records. Our triage team will record your vitals directly into your Electronic Medical Record (EMR).",
        followUps: [
          "Why is Data Privacy Act (DPA) consent required?",
          "How do I check my appointment status on the portal?",
          "What happens if I miss the phone verification call?",
        ],
      },
      {
        id: "portal-status",
        question: "How do I check my appointment status on the portal?",
        keywords: ["portal", "status", "check", "dashboard", "queue", "reference"],
        answer:
          "Navigate to the <strong>Patient Portal</strong> and sign in using your registered mobile number or reference code. Your live dashboard provides scheduled consultation dates, assigned doctor names, and current queue numbers.",
        followUps: [
          "Where can I view clinical notes and doctor prescriptions?",
          "What happens if I miss the phone verification call?",
          "Why is Data Privacy Act (DPA) consent required?",
        ],
      },
      {
        id: "portal-records",
        question: "Where can I view clinical notes and doctor prescriptions?",
        keywords: ["notes", "prescriptions", "records", "clinical", "pdf", "medical", "lab"],
        answer:
          "Consultation summaries, laboratory order slips, and digital physician prescriptions are stored under <strong>Patient Portal &gt; Medical Records</strong> as downloadable, printable PDFs.",
        followUps: [
          "How do I check my appointment status on the portal?",
          "What should I bring for my consultation & EMR triage?",
          "Why is Data Privacy Act (DPA) consent required?",
        ],
      },
      {
        id: "dpa-consent",
        question: "Why is Data Privacy Act (DPA) consent required?",
        keywords: ["dpa", "privacy", "consent", "data", "law", "act", "ra 10173"],
        answer:
          "In accordance with <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>, healthcare facilities must secure explicit consent before collecting and processing health records. All medical data is protected with end-to-end encryption accessible strictly by authorized medical staff.",
        followUps: [
          "What patient information is collected and how is it protected?",
          "How do I check my appointment status on the portal?",
          "Can I make a same-day appointment or walk-in booking?",
        ],
      },
      {
        id: "dpa-protection",
        question: "What patient information is collected and how is it protected?",
        keywords: ["collected", "protected", "security", "confidential", "storage", "doh"],
        answer:
          "We collect personal demographics, contact information, and initial triage symptoms. All records are securely maintained in compliance with Department of Health (DOH) security standards.",
        followUps: [
          "Why is Data Privacy Act (DPA) consent required?",
          "Why does clinic staff call before confirming my appointment?",
          "Can I make a same-day appointment or walk-in booking?",
        ],
      },
      {
        id: "same-day",
        question: "Can I make a same-day appointment or walk-in booking?",
        keywords: ["same-day", "same day", "walk-in", "walkin", "today", "urgent"],
        answer:
          "Same-day online bookings are accepted for morning clinics until <strong>10:00 AM</strong>, subject to remaining department capacity. For acute emergency needs, proceed directly to our <strong>24/7 Emergency Triage</strong>.",
        followUps: [
          "What is the cut-off time for same-day clinic queues?",
          "What are the hospital's operating hours & doctor schedules?",
          "Why does clinic staff call before confirming my appointment?",
        ],
      },
      {
        id: "cutoff-time",
        question: "What is the cut-off time for same-day clinic queues?",
        keywords: ["cut-off", "cutoff", "time", "deadline", "queue", "limit"],
        answer:
          "Outpatient departments observe a strict <strong>10:00 AM cut-off</strong> for morning clinics and a <strong>2:00 PM cut-off</strong> for afternoon sessions to guarantee sufficient consultation time for queued patients.",
        followUps: [
          "Can I make a same-day appointment or walk-in booking?",
          "What are the hospital's operating hours & doctor schedules?",
          "Why does clinic staff call before confirming my appointment?",
        ],
      },
      {
        id: "schedules",
        question: "How do I check live doctor availability and clinic schedules?",
        keywords: ["availability", "doctor", "roster", "duty", "slots", "schedules"],
        answer:
          "Click the <strong>Schedules</strong> module in the navigation menu to review daily on-duty specialists across Internal Medicine, Pediatrics, Surgery, and OB-GYN, along with available consultation quota.",
        followUps: [
          "What are the hospital's operating hours & doctor schedules?",
          "Can I make a same-day appointment or walk-in booking?",
          "How do I check my appointment status on the portal?",
        ],
      },
      {
        id: "verification-call",
        question: "Why does clinic staff call before confirming my appointment?",
        keywords: ["call", "phone", "verify", "verification", "confirm", "staff"],
        answer:
          "Clinical staff call to evaluate symptom urgency, ensure you are directed to the correct specialty department, and confirm slot availability before locking your spot in the queue.",
        followUps: [
          "What happens if I miss the phone verification call?",
          "What should I bring for my consultation & EMR triage?",
          "Why is Data Privacy Act (DPA) consent required?",
        ],
      },
      {
        id: "missed-call",
        question: "What happens if I miss the phone verification call?",
        keywords: ["missed", "unanswered", "retry", "released", "cancel"],
        answer:
          "Our clinic staff re-attempts calls up to <strong>two times</strong> within 30 minutes. If unreachable, the slot is released to accommodate urgent patients. You may re-book through the portal anytime.",
        followUps: [
          "Why does clinic staff call before confirming my appointment?",
          "How do I check my appointment status on the portal?",
          "Can I make a same-day appointment or walk-in booking?",
        ],
      },
    ],
    TL: [
      {
        id: "request-appointment",
        question: "Paano ako mag-request ng appointment/iskedyul?",
        keywords: ["request", "appointment", "iskedyul", "book", "magpa-schedule"],
        answer:
          "Pindutin ang <strong>'Book Appointment'</strong> sa homepage, piliin ang inyong departamento, at sagutan ang form. <strong>Staff Verification Protocol:</strong> Tatawagan po kayo ng clinic staff sa inyong numero upang kumpirmahin ang detalye bago ilagay sa queue ng doktor.",
        followUps: [
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
          "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
        ],
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
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        ],
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
          "Bakit kailangan ang Data Privacy Act (DPA) consent?",
        ],
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
          "Ano ang mangyayari kung hindi nasagot ang verification call?",
        ],
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
          "Bakit kailangan ang Data Privacy Act (DPA) consent?",
        ],
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
          "Bakit kailangan ang Data Privacy Act (DPA) consent?",
        ],
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
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        ],
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
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        ],
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
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
        ],
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
          "Bakit tumatawag ang clinic staff bago kumpirmahin ang appointment?",
        ],
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
          "Paano ko masusuri ang status ng aking appointment sa portal?",
        ],
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
          "Bakit kailangan ang Data Privacy Act (DPA) consent?",
        ],
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
          "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        ],
      },
    ],
  };

  const uiText = {
    EN: {
      role: "LINGKOD ASSISTANT",
      welcome: "Hello! I am Nurse Mika, your LINGKOD assistant. How can I help you today?",
      chooseTopic: "CHOOSE A TOPIC TO ASK:",
      followUpTitle: "CHOOSE A FOLLOW-UP QUESTION:",
      backToTopics: "← Back to Main Topics",
      closeAria: "Close support chat",
      openAria: "Open LINGKOD Assistant",
      badgeTitle: "LINGKOD • FAQs",
      defaultUnmatched:
        "I could not find an exact match for your inquiry. Please choose one of the core appointment workflow topics below:",
      defaultFollowUps: [
        "How do I check my appointment status on the portal?",
        "Can I make a same-day appointment or walk-in booking?",
        "What should I bring for my consultation & EMR triage?",
      ],
    },
    TL: {
      role: "LINGKOD ASSISTANT",
      welcome: "Kumusta! Ako si Nurse Mika, inyong LINGKOD assistant. Paano ko kayo matutulungan ngayon?",
      chooseTopic: "PUMILI NG TANONG:",
      followUpTitle: "MGA KAUGNAY NA TANONG:",
      backToTopics: "← Bumalik sa Pangunahing Tanong",
      closeAria: "Isara ang support chat",
      openAria: "Buksan ang LINGKOD Assistant",
      badgeTitle: "LINGKOD • FAQs",
      defaultUnmatched:
        "Hindi nahanap ang eksaktong sagot para sa inyong tanong. Pumili sa mga sumusunod na paksa:",
      defaultFollowUps: [
        "Paano ko masusuri ang status ng aking appointment sa portal?",
        "Maaari ba akong mag-book ng same-day appointment o mag-walk-in?",
        "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
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
  }

  let messages = [
    {
      id: "initial-welcome",
      sender: "bot",
      text: uiText.EN.welcome,
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

  async function submitQuery(queryText) {
    const cleanKey = queryText.toLowerCase().trim();
    const existingBot = messages.find(
      (m) => m.sender === "bot" && m.questionKey === cleanKey
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
        text: queryText,
        time: getCurrentTime(),
      },
    ];

    scrollToBottom();
    isTyping = true;

    setTimeout(() => {
      const match = findBestMatch(queryText);
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
          questionKey: cleanKey,
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

    if (messages.length === 1 && messages[0].isInitial) {
      showingMainTopics = true;
      activeBotMsgId = "initial-welcome";
      messages = [
        {
          id: "initial-welcome",
          sender: "bot",
          text: uiText[lang].welcome,
          time: getCurrentTime(),
          isInitial: true,
          followUps: [],
        },
      ];
    }
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

<div class="fixed bottom-6 right-6 z-50 flex flex-col items-end font-sans antialiased">
  {#if !isOpen}
    <button
      bind:this={launcherBtnRef}
      type="button"
      on:click={toggleChat}
      class="flex min-h-[48px] items-center gap-3 rounded-xl border border-[#14532d]/15 bg-white px-3.5 py-2 text-left text-gray-900 shadow-lg transition-colors duration-150 hover:border-[#166534] hover:bg-[#f0fdf4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] focus-visible:ring-offset-2 motion-reduce:transition-none"
      aria-label={uiText[lang].openAria}
    >
      <img
        src={assistants[assistant].avatar}
        alt={assistants[assistant].name}
        class="h-9 w-9 shrink-0 rounded-full border border-[#bbf7d0] bg-[#f0fdf4] object-cover"
      />
      <span class="flex flex-col">
        <span class="text-xs font-bold leading-tight text-[#14532d]">{assistants[assistant].name} &middot; {uiText[lang].badgeTitle}</span>
        <span class="text-[11px] font-medium leading-tight text-gray-600">{lang === "TL" ? "Magtanong tungkol sa LINGKOD" : "Ask LINGKOD FAQ & Triage"}</span>
      </span>
    </button>
  {:else}
    <div
      class="relative flex h-[555px] max-h-[calc(100vh-3rem)] w-[360px] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-[18px] border border-[#e5e7eb] bg-[#f9fafb] text-gray-900 shadow-[0_12px_36px_rgba(0,0,0,0.22)]"
      style="background-image: linear-gradient(to bottom, #ffffff 0%, #ffffff 63%, rgba(255,255,255,0.92) 70%, rgba(255,255,255,0.25) 82%, rgba(255,255,255,0) 100%), url('/sdn/public/assets/bg_login.jpg'); background-repeat: no-repeat; background-position: center top, center bottom; background-size: 100% 100%, 100% 190px;"
      role="region"
      aria-label="LINGKOD Chatbot"
    >
      <header class="relative flex shrink-0 flex-col items-center bg-[#14532d] px-[18px] pt-[14px] pb-[11px] text-white">
        <button
          bind:this={closeBtnRef}
          type="button"
          on:click={toggleChat}
          class="absolute top-[6px] right-[6px] flex h-11 w-11 items-center justify-center rounded-lg text-white transition-opacity hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white motion-reduce:transition-none"
          aria-label={uiText[lang].closeAria}
        >
          <svg
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>

        <button
          type="button"
          on:click={toggleAssistant}
          class="flex flex-col items-center rounded-lg px-2 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          title="Click to switch between Nurse Mika & Dr. Leo"
        >
          <div class="mb-0.5 flex h-[44px] items-center justify-center">
            {#if assistant === "girl"}
              <svg class="h-[35px] w-[50px]" viewBox="0 0 64 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 13C21 5.5 43 5.5 48 13" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" />
                <path d="M7 17.5C18 10.5 46 10.5 57 17.5L51.5 34.5C40 28 24 28 12.5 34.5L7 17.5Z" stroke="#ffffff" stroke-width="2.4" stroke-linejoin="round" />
                <path d="M30.2 17H33.8V20.8H37.6V24.2H33.8V28H30.2V24.2H26.4V20.8H30.2V17Z" stroke="#ffffff" stroke-width="1.9" stroke-linejoin="round" />
              </svg>
            {:else}
              <svg class="h-[42px] w-[44px]" viewBox="0 0 56 54" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20.5" cy="6.5" r="2.3" stroke="#ffffff" stroke-width="2.1" />
                <circle cx="35.5" cy="6.5" r="2.3" stroke="#ffffff" stroke-width="2.1" />
                <path d="M18.5 6.8C13.5 8 12.5 18 17.8 24C21.2 27.8 25.2 29.2 28 29.2C30.8 29.2 34.8 27.8 38.2 24C43.5 18 42.5 8 37.5 6.8" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round" />
                <path d="M15.2 18.8C19.8 25.5 36.2 25.5 40.8 18.8" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
                <path d="M28 29.2V35.5C28 43 21.5 49 16 48C11.2 47.2 10.8 41.2 16 39.2C21.2 37.2 28.2 43 34.2 47.2C38.5 50.2 43 49 44.5 45.5" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round" />
                <circle cx="45.5" cy="41.5" r="5" stroke="#ffffff" stroke-width="2.2" />
                <circle cx="45.5" cy="41.5" r="2.2" stroke="#ffffff" stroke-width="1.8" />
              </svg>
            {/if}
          </div>
          <h2 class="text-[16.5px] font-extrabold leading-tight tracking-wide text-white">
            {assistants[assistant].name}
          </h2>
        </button>

        <div class="my-[7px] h-px w-[88%] bg-white/85"></div>

        <div class="relative flex min-h-[20px] w-[88%] items-center justify-center">
          <p class="text-[13px] font-medium uppercase leading-tight tracking-[0.03em] text-white">
            {uiText[lang].role}
          </p>

          <div class="absolute right-0 top-1/2 -translate-y-1/2">
            <button
              bind:this={langBtnRef}
              type="button"
              on:click={() => (langMenuOpen = !langMenuOpen)}
              aria-expanded={langMenuOpen}
              aria-haspopup="menu"
              aria-label="Switch language"
              class="inline-flex min-h-[36px] items-center gap-[3px] rounded px-1.5 py-1 text-xs font-bold leading-none text-white hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
            >
              <span>{lang}</span>
              <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>

            {#if langMenuOpen}
              <div role="menu" class="absolute right-0 top-[calc(100%+5px)] z-50 min-w-[124px] overflow-hidden rounded-md border border-gray-300 bg-white shadow-md">
                <button
                  type="button"
                  role="menuitem"
                  on:click={() => {
                    langMenuOpen = false;
                    switchLang("EN");
                  }}
                  class="block min-h-[36px] w-full px-3 py-2 text-left text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#166534] {lang === 'EN'
                    ? 'bg-emerald-50 font-bold text-[#14532d]'
                    : 'font-medium text-gray-800 hover:bg-emerald-50/60'}"
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
                  class="block min-h-[36px] w-full px-3 py-2 text-left text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#166534] {lang === 'TL'
                    ? 'bg-emerald-50 font-bold text-[#14532d]'
                    : 'font-medium text-gray-800 hover:bg-emerald-50/60'}"
                >
                  TL (Tagalog)
                </button>
              </div>
            {/if}
          </div>
        </div>
      </header>

      <div
        bind:this={chatBodyRef}
        class="flex flex-1 flex-col gap-2.5 overflow-y-auto bg-transparent px-[18px] pt-[22px] pb-[18px]"
      >
        {#each messages as msg (msg.id)}
          {#if msg.sender === "bot"}
            <div class="flex w-full items-start gap-2.5" data-msg-id={msg.id}>
              <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#b8bfc6]">
                <img
                  src={assistants[assistant].avatar}
                  alt={assistants[assistant].name}
                  class="h-full w-full object-cover"
                />
              </div>

              <div class="flex max-w-[225px] flex-col items-start">
                <div
                  class="rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] px-3 py-2.5 text-xs leading-relaxed text-gray-900 {highlightedMsgId === msg.id ? 'outline outline-2 outline-[#166534]' : ''}"
                >
                  {@html msg.text}
                </div>
                <span class="mt-1 self-end text-[11px] text-gray-700">
                  {msg.time}
                </span>
              </div>
            </div>

            {#if msg.isInitial && showingMainTopics}
              <div
                data-topics-list
                class="mt-1 mr-1 ml-auto flex w-[225px] shrink-0 flex-col items-start"
              >
                <div class="mb-2 inline-block rounded bg-[#e5e7eb] px-2.5 py-1.5 text-[11px] font-bold uppercase leading-none text-gray-900">
                  {uiText[lang].chooseTopic}
                </div>

                <div class="flex w-full flex-col gap-2">
                  {#each topics[lang] as topic}
                    <button
                      type="button"
                      on:click={() => handleSelectQuestion(topic)}
                      class="group flex min-h-[44px] w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white/95 px-3 py-2 text-left text-xs leading-snug text-gray-900 transition-colors hover:border-[#166534] hover:bg-[#f0fdf4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] motion-reduce:transition-none"
                    >
                      <span class="flex-1">{topic}</span>
                      <svg
                        class="h-3.5 w-3.5 shrink-0 text-gray-600 transition-transform group-hover:translate-x-0.5 group-hover:text-[#166534] motion-reduce:transition-none"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
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
              <div class="mt-0.5 mr-auto ml-[46px] flex w-[225px] shrink-0 flex-col items-start">
                <div class="mb-2 inline-block rounded bg-[#e5e7eb] px-2.5 py-1.5 text-[11px] font-bold uppercase leading-none text-gray-900">
                  {uiText[lang].followUpTitle}
                </div>
                <div class="flex w-full flex-col gap-2">
                  {#each msg.followUps as chip}
                    <button
                      type="button"
                      on:click={() => handleSelectQuestion(chip)}
                      class="group flex min-h-[44px] w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white/95 px-3 py-2 text-left text-xs leading-snug text-gray-900 transition-colors hover:border-[#166534] hover:bg-[#f0fdf4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] motion-reduce:transition-none"
                    >
                      <span class="flex-1">{chip}</span>
                      <svg
                        class="h-3.5 w-3.5 shrink-0 text-gray-600 transition-transform group-hover:translate-x-0.5 group-hover:text-[#166534] motion-reduce:transition-none"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
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
                  class="mt-2 min-h-[44px] w-full rounded-lg border border-[#166534] bg-[#f0fdf4] px-3 py-2 text-center text-xs font-bold leading-tight text-[#14532d] transition-colors hover:bg-[#166534] hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] motion-reduce:transition-none"
                >
                  {uiText[lang].backToTopics}
                </button>
              </div>
            {/if}
          {:else}
            <div class="flex flex-col items-end">
              <div class="max-w-[220px] rounded-lg bg-[#166534] px-3 py-2 text-xs font-medium leading-relaxed text-white">
                {msg.text}
              </div>
              <span class="mt-1 text-[11px] text-gray-700">
                {msg.time}
              </span>
            </div>
          {/if}
        {/each}

        {#if isTyping}
          <div class="flex items-center pl-[46px]">
            <div class="inline-flex items-center gap-1 rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] px-3 py-2">
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#14532d] motion-reduce:animate-none"></span>
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#14532d] [animation-delay:0.15s] motion-reduce:animate-none"></span>
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#14532d] [animation-delay:0.3s] motion-reduce:animate-none"></span>
            </div>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>
