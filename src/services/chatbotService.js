/**
 * Chatbot Knowledge Base & Assistant Service for MMWGH LINGKOD
 */

export const ASSISTANTS = {
  girl: {
    name: { EN: "Nurse Mika", TL: "Nurse Mika" },
    role: { EN: "LINGKOD Nursing Assistant", TL: "LINGKOD Nursing Assistant" },
    avatar: "/sdn/public/assets/chibi_girl_avatar.png",
    welcome: {
      EN: "Hello! 👋 I'm Nurse Mika, your LINGKOD assistant.<br/>How can I help you today?",
      TL: "Kumusta! 👋 Ako si Nurse Mika, inyong LINGKOD assistant.<br/>Paano ko kayo matutulungan ngayon?",
    },
  },
  boy: {
    name: { EN: "Dr. Leo", TL: "Dr. Leo" },
    role: { EN: "LINGKOD Medical Assistant", TL: "LINGKOD Medical Assistant" },
    avatar: "/sdn/public/assets/chibi_boy_avatar.png",
    welcome: {
      EN: "Hello! 👋 I'm Dr. Leo, your LINGKOD healthcare guide.<br/>How can I assist you today?",
      TL: "Kumusta! 👋 Ako si Dr. Leo, inyong healthcare guide.<br/>Paano ko kayo matutulungan ngayon?",
    },
  },
};

export const CHAT_DICT = {
  EN: {
    selectQuestion: "Choose a topic to ask:",
    askAnother: "Ask another question",
    limitReached: "You've reached the FAQ limit. Please rate your experience:",
    feedbackThanks: (stars) =>
      `You rated us ${stars} star${stars > 1 ? "s" : ""}! Thank you for your feedback! ⭐`,
    bubble: "ASK ME",
  },
  TL: {
    selectQuestion: "Pumili ng tanong:",
    askAnother: "Magtanong muli",
    limitReached: "Naabot na ang limitasyon ng FAQ. Pakibigyan kami ng rating:",
    feedbackThanks: (stars) =>
      `Binigyan mo kami ng ${stars} bituin! Maraming salamat sa iyong feedback! ⭐`,
    bubble: "TANGUNGIN MO AKO",
  },
};

export const FAQS_DICT = {
  EN: [
    {
      question: "How do I request an appointment/schedule?",
      answer:
        "Click 'Book Appointment' on the homepage, select your department, and complete the registration. <strong>Staff Verification Protocol:</strong> Our clinic staff will call your contact number to verify your appointment before admitting you to the consultation queue. Please keep your phone line active!",
    },
    {
      question: "What are the hospital's operating hours & doctor schedules?",
      answer:
        "Our main emergency triage operates 24/7. Outpatient clinic consultations and telehealth services are open Monday to Friday from 8:00 AM to 5:00 PM. Real-time doctor availability and open slots can be tracked directly on our homepage live stats counter.",
    },
    {
      question: "Where can I find the nearest Service Delivery Network (SDN) facility?",
      answer:
        "You can view all affiliated hospital, RHU, and BHS partner locations on our interactive 'Location Map'. Our attending staff can also initiate seamless inter-department referrals or transfers if specialized evaluation is required.",
    },
    {
      question: "What should I bring for my consultation & EMR triage?",
      answer:
        "Please bring a valid government/student ID, your active contact phone number, and any prior medical records, lab results, or prescription lists. Our staff records triage vitals into your electronic medical record (EMR) and issues printable clinical notes.",
    },
  ],
  TL: [
    {
      question: "Paano ako mag-request ng appointment/iskedyul?",
      answer:
        "Pindutin ang 'Book Appointment' sa homepage, piliin ang inyong departamento, at punan ang registration form. <strong>Staff Verification Protocol:</strong> Tatawagan po kayo ng clinic staff sa inyong numero upang kumpirmahin ang appointment bago kayo ipasok sa queue ng doktor. Pakisigurong aktibo ang inyong linya!",
    },
    {
      question: "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
      answer:
        "Ang Emergency Department ay bukas 24/7. Ang outpatient clinics at telehealth ay bukas Lunes hanggang Biyernes, 8:00 AM hanggang 5:00 PM. Makikita rin ninyo ang live available doctor slots sa aming homepage live stats counter.",
    },
    {
      question: "Saan ko makikita ang pinakamalapit na pasilidad ng SDN?",
      answer:
        "Makikita ang lahat ng kaakibat na partner hospitals, RHUs, at BHS facilities sa aming interactive 'Location Map'. Maaari ring mag-proseso ang aming staff ng electronic referral o transfer sa ibang departamento kung kinakailangan.",
    },
    {
      question: "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
      answer:
        "Mangyaring magdala ng valid ID, aktibong cellphone number, at anumang nakaraang laboratory results o reseta ng gamot. Itatala ng aming staff ang inyong triage vitals sa inyong electronic medical record (EMR) at magbibigay ng opisyal na printable clinical notes.",
    },
  ],
};
