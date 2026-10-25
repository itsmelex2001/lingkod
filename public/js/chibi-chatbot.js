/**
 * CHIBI CHATBOT CONTROLLER - LINKod Assistants
 * 
 * Features:
 * - Walking slowly from the right side into position
 * - After walking in, "ASK ME" speech bubble pops up
 * - After staying, walks out to the right side
 * - Alternating sequence: Boy walks in/out -> Girl walks in/out
 * - Single head circle avatar in the chatbot conversation
 * - Contextual assistant: if asking the girl, she's in the conversation; if boy, he is
 * - Removed floating green dot and 'MMWGH' text badge
 */

  (function () {
    'use strict';

    // Assistant Profiles
    const ASSISTANTS = {
      girl: {
        name: { EN: "Nurse Mika", TL: "Nurse Mika" },
        role: { EN: "LINKod Nursing Assistant", TL: "LINKod Nursing Assistant" },
        avatar: "/sdn/public/assets/chibi_girl_avatar.png",
        welcome: {
          EN: "Hello! 👋 I'm Nurse Mika, your LINKod assistant.<br/>How can I help you today?",
          TL: "Kumusta! 👋 Ako si Nurse Mika, inyong LINKod assistant.<br/>Paano ko kayo matutulungan ngayon?"
        }
      },
      boy: {
        name: { EN: "Dr. Leo", TL: "Dr. Leo" },
        role: { EN: "LINKod Medical Assistant", TL: "LINKod Medical Assistant" },
        avatar: "/sdn/public/assets/chibi_boy_avatar.png",
        welcome: {
          EN: "Hello! 👋 I'm Dr. Leo, your LINKod healthcare guide.<br/>How can I assist you today?",
          TL: "Kumusta! 👋 Ako si Dr. Leo, inyong healthcare guide.<br/>Paano ko kayo matutulungan ngayon?"
        }
      }
    };

    const dict = {
      EN: {
        selectQuestion: "Choose a topic to ask:",
        askAnother: "Ask another question",
        limitReached: "You've reached the FAQ limit. Please rate your experience:",
        feedbackThanks: (stars) => `You rated us ${stars} star${stars > 1 ? 's' : ''}! Thank you for your feedback! ⭐`,
        bubble: "ASK ME"
      },
      TL: {
        selectQuestion: "Pumili ng tanong:",
        askAnother: "Magtanong muli",
        limitReached: "Naabot na ang limitasyon ng FAQ. Pakibigyan kami ng rating:",
        feedbackThanks: (stars) => `Binigyan mo kami ng ${stars} bituin! Maraming salamat sa iyong feedback! ⭐`,
        bubble: "TANUNGIN MO AKO"
      }
    };

    const faqs = {
      EN: [
        {
          question: "How do I request an appointment/schedule?",
          answer: "Click 'Book Appointment' on the homepage, select your department, and complete the registration. <strong>Staff Verification Protocol:</strong> Our clinic staff will call your contact number to verify your appointment before admitting you to the consultation queue. Please keep your phone line active!"
        },
        {
          question: "What are the hospital's operating hours & doctor schedules?",
          answer: "Our main emergency triage operates 24/7. Outpatient clinic consultations and telehealth services are open Monday to Friday from 8:00 AM to 5:00 PM. Real-time doctor availability and open slots can be tracked directly on our homepage live stats counter."
        },
        {
          question: "Where can I find the nearest Service Delivery Network (SDN) facility?",
          answer: "You can view all affiliated hospital, RHU, and BHS partner locations on our interactive 'Location Map'. Our attending staff can also initiate seamless inter-department referrals or transfers if specialized evaluation is required."
        },
        {
          question: "What should I bring for my consultation & EMR triage?",
          answer: "Please bring a valid government/student ID, your active contact phone number, and any prior medical records, lab results, or prescription lists. Our staff records triage vitals into your electronic medical record (EMR) and issues printable clinical notes."
        }
      ],
      TL: [
        {
          question: "Paano ako mag-request ng appointment/iskedyul?",
          answer: "Pindutin ang 'Book Appointment' sa homepage, piliin ang inyong departamento, at punan ang registration form. <strong>Staff Verification Protocol:</strong> Tatawagan po kayo ng clinic staff sa inyong numero upang kumpirmahin ang appointment bago kayo ipasok sa queue ng doktor. Pakisigurong aktibo ang inyong linya!"
        },
        {
          question: "Ano ang mga oras ng operasyon at iskedyul ng doktor?",
          answer: "Ang Emergency Department ay bukas 24/7. Ang outpatient clinics at telehealth ay bukas Lunes hanggang Biyernes, 8:00 AM hanggang 5:00 PM. Makikita rin ninyo ang live available doctor slots sa aming homepage live stats counter."
        },
        {
          question: "Saan ko makikita ang pinakamalapit na pasilidad ng SDN?",
          answer: "Makikita ang lahat ng kaakibat na partner hospitals, RHUs, at BHS facilities sa aming interactive 'Location Map'. Maaari ring mag-proseso ang aming staff ng electronic referral o transfer sa ibang departamento kung kinakailangan."
        },
        {
          question: "Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?",
          answer: "Mangyaring magdala ng valid ID, aktibong cellphone number, at anumang nakaraang laboratory results o reseta ng gamot. Itatala ng aming staff ang inyong triage vitals sa inyong electronic medical record (EMR) at magbibigay ng opisyal na printable clinical notes."
        }
      ]
    };

    // State
    let currentLang = 'EN';
    let isOpen = false;
    let activeChibi = 'boy'; // Current chibi walking on the stage ('boy' or 'girl')
    let chatAssistant = 'boy'; // Current assistant in the chat conversation ('boy' or 'girl')
    let questionCount = 0;
    let isTyping = false;
    let messages = [];

    // Cycle Timers
    let timerStep1 = null;
    let timerStep2 = null;
    let timerStep3 = null;
    let timerStep4 = null;
    let timerStep5 = null;

    function formatTime() {
      return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // --- Initialize UI ---
    function initChibiChatbot() {
      if (document.getElementById('chibi-chatbot-root')) return;

      const root = document.createElement('div');
      root.id = 'chibi-chatbot-root';
      root.innerHTML = `
      <!-- Floating Chibi Widget in Bottom-Right -->
      <div class="chibi-widget-container" id="chibiWidget">
        <div class="chibi-stage" id="chibiStage" title="Click to ask me!" role="button" tabindex="0">
          <div class="chibi-ground-shadow"></div>

          <!-- Dynamic Actor (Walking from right, popping bubble, walking out) -->
          <div class="chibi-character-actor" id="chibiActor">
            <!-- Injected dynamically -->
          </div>
        </div>
      </div>

      <!-- Chat Conversation Window -->
      <div class="chibi-chat-window" id="chibiChatWindow" role="dialog" aria-modal="true" aria-hidden="true">
        <!-- Header with SINGLE Head Circle -->
        <div class="chibi-chat-header">
          <div class="chibi-header-info">
            <!-- Single Head Circle Avatar -->
            <div class="chibi-avatar-single" id="headerAvatarCircle" data-assistant="${chatAssistant}" title="Click to switch assistant">
              <img id="headerAvatarImg" src="${ASSISTANTS[chatAssistant].avatar}" alt="${ASSISTANTS[chatAssistant].name[currentLang]}" />
            </div>
            <div class="chibi-header-text">
              <h3 id="chibiHeaderName">${ASSISTANTS[chatAssistant].name[currentLang]}</h3>
              <p id="chibiHeaderRole">${ASSISTANTS[chatAssistant].role[currentLang]}</p>
            </div>
          </div>

          <div class="chibi-header-actions">
            <div class="chibi-lang-switch">
              <button class="chibi-lang-btn active" data-lang="EN" id="btnLangEN">EN</button>
              <button class="chibi-lang-btn" data-lang="TL" id="btnLangTL">TL</button>
            </div>
            <button class="chibi-close-btn" id="chibiCloseBtn" aria-label="Close Chat">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
        </div>

        <!-- Chat Conversation Body -->
        <div class="chibi-chat-body" id="chibiChatBody">
          <!-- Messages will be rendered here -->
        </div>
      </div>
    `;

      document.body.appendChild(root);

      // Event Listeners
      const stage = document.getElementById('chibiStage');
      const closeBtn = document.getElementById('chibiCloseBtn');
      const btnEN = document.getElementById('btnLangEN');
      const btnTL = document.getElementById('btnLangTL');
      const avatarCircle = document.getElementById('headerAvatarCircle');

      stage.addEventListener('click', () => openChatWith(activeChibi));
      stage.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') openChatWith(activeChibi);
      });
      closeBtn.addEventListener('click', toggleChat);

      btnEN.addEventListener('click', () => switchLanguage('EN'));
      btnTL.addEventListener('click', () => switchLanguage('TL'));

      // Clicking single head circle in header switches assistant
      avatarCircle.addEventListener('click', toggleHeaderAssistant);

      // Coordinate with splash screen
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

    // --- Walking Loop: Walk In From Right -> Pop ASK ME -> Walk Out to Right -> Next Chibi ---
    function startWalkingCycle() {
      clearCycleTimers();
      runSingleWalkCycle(activeChibi);
    }

    function clearCycleTimers() {
      if (timerStep1) clearTimeout(timerStep1);
      if (timerStep2) clearTimeout(timerStep2);
      if (timerStep3) clearTimeout(timerStep3);
      if (timerStep4) clearTimeout(timerStep4);
      if (timerStep5) clearTimeout(timerStep5);
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
      if (isOpen) return; // Pause walking cycle while chat is open

      const actor = document.getElementById('chibiActor');
      if (!actor) return;

      activeChibi = chibi;

      if (chibi === 'girl') {
        // ====================================================
        // NURSE MIKA CYCLE:
        // 1. Walk in smoothly from the right (2.6s) with leg strides and walking eye-blinks
        // 2. Wave hello enthusiastically with pen (1.6s) as "ASK ME" pops up!
        // 3. Stand on both feet with speech bubble & periodic eye-blinking (3.6s)
        // 4. Wave bye-bye before leaving (1.4s)
        // 5. Turn around and walk out smoothly (2.2s)
        // ====================================================
        actor.className = 'chibi-character-actor actor-girl state-walk-in';
        actor.innerHTML = `
          <div class="chibi-sprite chibi-sprite-girl" aria-label="Nurse Mika"></div>
        `;

        // 2. After walking in (2.6s): Wave Hello with pen + pop up "ASK ME" bubble!
        timerStep1 = setTimeout(() => {
          if (isOpen) return;

          actor.className = 'chibi-character-actor actor-girl state-wave-hello';
          actor.innerHTML = `
            <div class="chibi-ask-me-bubble ${currentLang === 'TL' ? 'is-tl' : ''}" id="chibiAskMeBubble" title="${currentLang === 'TL' ? 'TANUNGIN MO AKO' : 'ASK ME'}"></div>
            <div class="chibi-sprite chibi-sprite-girl-wave" aria-label="Nurse Mika Waving Hello"></div>
          `;
          bindBubbleClick();

          // 3. After waving hello (1.6s): Stand on both feet with periodic eye-blinking & bubble (3.6s)
          timerStep2 = setTimeout(() => {
            if (isOpen) return;

            actor.className = 'chibi-character-actor actor-girl state-arrived';
            actor.innerHTML = `
              <div class="chibi-ask-me-bubble ${currentLang === 'TL' ? 'is-tl' : ''}" id="chibiAskMeBubble" title="${currentLang === 'TL' ? 'TANUNGIN MO AKO' : 'ASK ME'}"></div>
              <div class="chibi-sprite chibi-sprite-girl" aria-label="Nurse Mika Standing"></div>
            `;
            bindBubbleClick();

            // 4. After standing idle (3.6s): Wave Bye before walking out!
            timerStep3 = setTimeout(() => {
              if (isOpen) return;

              actor.className = 'chibi-character-actor actor-girl state-wave-bye';
              actor.innerHTML = `
                <div class="chibi-sprite chibi-sprite-girl-wave" aria-label="Nurse Mika Waving Bye"></div>
              `;

              // 5. After waving bye (1.4s): Turn around and walk out to the right (2.2s)
              timerStep4 = setTimeout(() => {
                if (isOpen) return;

                actor.className = 'chibi-character-actor actor-girl state-walk-out';
                actor.innerHTML = `
                  <div class="chibi-sprite chibi-sprite-girl" aria-label="Nurse Mika"></div>
                `;

                // 6. Once walked out (2.2s): Switch to Dr. Leo
                timerStep5 = setTimeout(() => {
                  if (isOpen) return;
                  runSingleWalkCycle('boy');
                }, 2200);

              }, 1400);

            }, 3600);

          }, 1600);

        }, 2600);

      } else {
        // ====================================================
        // DR. LEO CYCLE (Unchanged)
        // ====================================================
        // STEP 1: Walk in smoothly from the right side at natural pace (2.6s)
        actor.className = 'chibi-character-actor actor-boy state-walk-in';
        actor.innerHTML = `
          <div class="chibi-sprite chibi-sprite-boy" aria-label="Dr. Leo"></div>
        `;

        // STEP 2: After walking in (2.6s), stop cleanly on both feet and pop up bubble
        timerStep1 = setTimeout(() => {
          if (isOpen) return;

          actor.className = 'chibi-character-actor actor-boy state-arrived';
          actor.innerHTML = `
            <div class="chibi-ask-me-bubble ${currentLang === 'TL' ? 'is-tl' : ''}" id="chibiAskMeBubble" title="${currentLang === 'TL' ? 'TANUNGIN MO AKO' : 'ASK ME'}"></div>
            <div class="chibi-sprite chibi-sprite-boy" aria-label="Dr. Leo"></div>
          `;
          bindBubbleClick();

          // STEP 3: After staying with bubble (5.5s), walk out to right side (2.2s)
          timerStep2 = setTimeout(() => {
            if (isOpen) return;

            // Walk out to right side (2.2s)
            actor.className = 'chibi-character-actor actor-boy state-walk-out';
            actor.innerHTML = `
              <div class="chibi-sprite chibi-sprite-boy" aria-label="Dr. Leo"></div>
            `;

            // STEP 4: Once walked out, switch to Nurse Mika
            timerStep3 = setTimeout(() => {
              if (isOpen) return;
              runSingleWalkCycle('girl');
            }, 2200);

          }, 5500);

        }, 2600);
      }
    }

    // --- Open Chat with Targeted Assistant (Boy or Girl) ---
    function openChatWith(chibi) {
      chatAssistant = chibi;
      updateHeaderAssistantUI();
      resetConversationForAssistant();

      if (!isOpen) {
        toggleChat();
      }
    }

    // --- Toggle Chat Window ---
    function toggleChat() {
      isOpen = !isOpen;
      const windowEl = document.getElementById('chibiChatWindow');
      const widgetEl = document.getElementById('chibiWidget');

      if (isOpen) {
        clearCycleTimers();
        windowEl.classList.add('is-open');
        windowEl.setAttribute('aria-hidden', 'false');
        widgetEl.style.opacity = '0.35';
        scrollToBottom();
      } else {
        windowEl.classList.remove('is-open');
        windowEl.setAttribute('aria-hidden', 'true');
        widgetEl.style.opacity = '1';
        // Resume walking cycle
        startWalkingCycle();
      }
    }

    // --- Toggle Assistant Inside Chat Header ---
    function toggleHeaderAssistant() {
      chatAssistant = (chatAssistant === 'boy') ? 'girl' : 'boy';
      updateHeaderAssistantUI();
      resetConversationForAssistant();
    }

    function updateHeaderAssistantUI() {
      const prof = ASSISTANTS[chatAssistant];
      const imgEl = document.getElementById('headerAvatarImg');
      const nameEl = document.getElementById('chibiHeaderName');
      const roleEl = document.getElementById('chibiHeaderRole');
      const circleEl = document.getElementById('headerAvatarCircle');

      if (circleEl) {
        circleEl.setAttribute('data-assistant', chatAssistant);
      }
      if (imgEl) {
        imgEl.src = prof.avatar;
        imgEl.alt = prof.name[currentLang];
      }
      if (nameEl) nameEl.textContent = prof.name[currentLang];
      if (roleEl) roleEl.textContent = prof.role[currentLang];
    }

    // --- Switch Language ---
    function switchLanguage(lang) {
      if (currentLang === lang) return;
      currentLang = lang;

      document.getElementById('btnLangEN').classList.toggle('active', lang === 'EN');
      document.getElementById('btnLangTL').classList.toggle('active', lang === 'TL');

      // Dynamically update speech bubble on stage if present
      const bubble = document.getElementById('chibiAskMeBubble');
      if (bubble) {
        if (lang === 'TL') {
          bubble.classList.add('is-tl');
          bubble.title = 'TANUNGIN MO AKO';
        } else {
          bubble.classList.remove('is-tl');
          bubble.title = 'ASK ME';
        }
      }

      updateHeaderAssistantUI();
      resetConversationForAssistant();
    }

    // --- Reset & Render Conversation for Chosen Assistant ---
    function resetConversationForAssistant() {
      questionCount = 0;
      isTyping = false;
      const prof = ASSISTANTS[chatAssistant];

      messages = [
        {
          sender: 'bot',
          text: prof.welcome[currentLang],
          time: formatTime(),
          avatar: prof.avatar,
          name: prof.name[currentLang]
        }
      ];

      renderMessages();
    }

    function renderMessages() {
      const chatBody = document.getElementById('chibiChatBody');
      if (!chatBody) return;

      chatBody.innerHTML = `
      ${messages.map(m => `
        <div class="chibi-msg-row ${m.sender}">
          ${m.sender === 'bot' ? `
            <div class="chibi-msg-avatar" title="${m.name}">
              <img src="${m.avatar}" alt="${m.name}" />
            </div>
          ` : ''}
          <div class="chibi-msg-box">
            <div class="chibi-msg-bubble ${m.sender}">
              ${m.text}
            </div>
            <span class="chibi-msg-time">${m.time}</span>
          </div>
        </div>
      `).join('')}

      <div class="chibi-faq-container" id="faqOptionsList">
        <span class="chibi-faq-label">${dict[currentLang].selectQuestion}</span>
        ${faqs[currentLang].map((f, idx) => `
          <button class="chibi-faq-item" data-idx="${idx}">
            ${f.question}
          </button>
        `).join('')}
      </div>
    `;

      bindFaqClicks();
      scrollToBottom();
    }

    function bindFaqClicks() {
      const items = document.querySelectorAll('.chibi-faq-item');
      items.forEach(btn => {
        btn.addEventListener('click', () => {
          const idx = parseInt(btn.dataset.idx, 10);
          handleFaqSelection(idx);
        });
      });
    }

    function handleFaqSelection(idx) {
      if (isTyping) return;
      const faqItem = faqs[currentLang][idx];
      if (!faqItem) return;

      const chatBody = document.getElementById('chibiChatBody');
      const optionsEl = document.getElementById('faqOptionsList');
      if (optionsEl) optionsEl.remove();

      // 1. User Message
      const userMsg = {
        sender: 'user',
        text: faqItem.question,
        time: formatTime()
      };
      messages.push(userMsg);

      const userRow = document.createElement('div');
      userRow.className = 'chibi-msg-row user';
      userRow.innerHTML = `
      <div class="chibi-msg-box">
        <div class="chibi-msg-bubble user">
          ${userMsg.text}
        </div>
        <span class="chibi-msg-time">${userMsg.time}</span>
      </div>
    `;
      chatBody.appendChild(userRow);
      scrollToBottom();

      // 2. Typing Indicator with Current Assistant's Avatar
      isTyping = true;
      const prof = ASSISTANTS[chatAssistant];

      const typingRow = document.createElement('div');
      typingRow.className = 'chibi-typing-row';
      typingRow.id = 'chibiTyping';
      typingRow.innerHTML = `
      <div class="chibi-msg-avatar" title="${prof.name[currentLang]}">
        <img src="${prof.avatar}" alt="${prof.name[currentLang]}" />
      </div>
      <div class="chibi-typing-bubble">
        <span class="chibi-dot"></span>
        <span class="chibi-dot"></span>
        <span class="chibi-dot"></span>
      </div>
    `;
      chatBody.appendChild(typingRow);
      scrollToBottom();

      questionCount++;

      // 3. Bot Response from Current Assistant
      setTimeout(() => {
        const typ = document.getElementById('chibiTyping');
        if (typ) typ.remove();
        isTyping = false;

        const botMsg = {
          sender: 'bot',
          text: faqItem.answer,
          time: formatTime(),
          avatar: prof.avatar,
          name: prof.name[currentLang]
        };
        messages.push(botMsg);

        const botRow = document.createElement('div');
        botRow.className = 'chibi-msg-row bot';
        botRow.innerHTML = `
        <div class="chibi-msg-avatar" title="${botMsg.name}">
          <img src="${botMsg.avatar}" alt="${botMsg.name}" />
        </div>
        <div class="chibi-msg-box">
          <div class="chibi-msg-bubble bot">
            ${botMsg.text}
          </div>
          <span class="chibi-msg-time">${botMsg.time}</span>
        </div>
      `;
        chatBody.appendChild(botRow);

        if (questionCount >= 4) {
          showRatingWidget();
        } else {
          showAskAnotherButton();
        }
        scrollToBottom();
      }, 450);
    }

    function showAskAnotherButton() {
      const chatBody = document.getElementById('chibiChatBody');
      const box = document.createElement('div');
      box.className = 'chibi-reset-container';
      box.id = 'chibiResetContainer';
      box.innerHTML = `
      <button class="chibi-reset-btn" id="btnAskAnother">
        ${dict[currentLang].askAnother}
      </button>
    `;
      chatBody.appendChild(box);

      document.getElementById('btnAskAnother').addEventListener('click', () => {
        box.remove();
        const nextFaqContainer = document.createElement('div');
        nextFaqContainer.className = 'chibi-faq-container';
        nextFaqContainer.id = 'faqOptionsList';
        nextFaqContainer.innerHTML = `
        <span class="chibi-faq-label">${dict[currentLang].selectQuestion}</span>
        ${faqs[currentLang].map((f, idx) => `
          <button class="chibi-faq-item" data-idx="${idx}">
            ${f.question}
          </button>
        `).join('')}
      `;
        chatBody.appendChild(nextFaqContainer);
        bindFaqClicks();
        scrollToBottom();
      });
    }

    function showRatingWidget() {
      const chatBody = document.getElementById('chibiChatBody');
      const card = document.createElement('div');
      card.className = 'chibi-rating-card';
      card.id = 'chibiRatingCard';
      card.innerHTML = `
      <p>${dict[currentLang].limitReached}</p>
      <div class="chibi-stars">
        ${[1, 2, 3, 4, 5].map(s => `<button class="chibi-star-btn" data-star="${s}" title="${s} Stars">★</button>`).join('')}
      </div>
    `;
      chatBody.appendChild(card);

      card.querySelectorAll('.chibi-star-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const stars = parseInt(btn.dataset.star, 10);
          card.remove();

          const prof = ASSISTANTS[chatAssistant];
          const botRow = document.createElement('div');
          botRow.className = 'chibi-msg-row bot';
          botRow.innerHTML = `
          <div class="chibi-msg-avatar" title="${prof.name[currentLang]}">
            <img src="${prof.avatar}" alt="${prof.name[currentLang]}" />
          </div>
          <div class="chibi-msg-box">
            <div class="chibi-msg-bubble bot">
              ${dict[currentLang].feedbackThanks(stars)}
            </div>
            <span class="chibi-msg-time">${formatTime()}</span>
          </div>
        `;
          chatBody.appendChild(botRow);
          scrollToBottom();
        });
      });
    }

    function scrollToBottom() {
      const body = document.getElementById('chibiChatBody');
      if (body) {
        setTimeout(() => {
          body.scrollTop = body.scrollHeight;
        }, 30);
      }
    }

    // --- Bootstrap on Ready ---
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initChibiChatbot);
    } else {
      initChibiChatbot();
    }
  })();
