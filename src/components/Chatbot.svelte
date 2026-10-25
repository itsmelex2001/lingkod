<script>
  import { fly, fade } from "svelte/transition";
  import { afterUpdate, onMount, onDestroy } from "svelte";

  let isOpen = false;
  let activeChibi = "boy"; // character on the walking stage
  let chatAssistant = "boy"; // assistant in the chat conversation
  let actorState = "state-walk-in"; // 'state-walk-in' | 'state-arrived' | 'state-walk-out'
  let showBubble = false;

  let timerStep1, timerStep2, timerStep3;

  function getCurrentTime() {
    return new Date().toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  let lang = "EN";

  const ASSISTANTS = {
    girl: {
      name: { EN: "Nurse Mika", TL: "Nurse Mika" },
      role: { EN: "LINKod Nursing Assistant", TL: "LINKod Nursing Assistant" },
      avatar: "/sdn/public/assets/chibi_girl_avatar.png",
      welcome: {
        EN: "Hello! 👋 I'm Nurse Mika, your LINKod assistant.<br/>How can I help you today?",
        TL: "Kumusta! 👋 Ako si Nurse Mika, inyong LINKod assistant.<br/>Paano ko kayo matutulungan ngayon?",
      },
    },
    boy: {
      name: { EN: "Dr. Leo", TL: "Dr. Leo" },
      role: { EN: "LINKod Medical Assistant", TL: "LINKod Medical Assistant" },
      avatar: "/sdn/public/assets/chibi_boy_avatar.png",
      welcome: {
        EN: "Hello! 👋 I'm Dr. Leo, your LINKod healthcare guide.<br/>How can I assist you today?",
        TL: "Kumusta! 👋 Ako si Dr. Leo, inyong healthcare guide.<br/>Paano ko kayo matutulungan ngayon?",
      },
    },
  };

  const dict = {
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

  const faqsDict = {
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
        question:
          "Where can I find the nearest Service Delivery Network (SDN) facility?",
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
        question:
          "Saan ko makikita ang pinakamalapit na pasilidad ng SDN?",
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

  $: currentDict = dict[lang];
  $: currentFaqs = faqsDict[lang];
  $: currentProfile = ASSISTANTS[chatAssistant];

  let messages = [];

  function initMessages() {
    messages = [
      {
        sender: "bot",
        text: ASSISTANTS[chatAssistant].welcome[lang],
        time: getCurrentTime(),
        avatar: ASSISTANTS[chatAssistant].avatar,
        name: ASSISTANTS[chatAssistant].name[lang],
      },
    ];
  }

  initMessages();

  let showOptions = true;
  let chatBody;
  let questionCount = 0;
  let showRating = false;
  let userRating = 0;
  let isTyping = false;
  let timerStep4 = null;
  let timerStep5 = null;

  function clearTimers() {
    if (timerStep1) clearTimeout(timerStep1);
    if (timerStep2) clearTimeout(timerStep2);
    if (timerStep3) clearTimeout(timerStep3);
    if (timerStep4) clearTimeout(timerStep4);
    if (timerStep5) clearTimeout(timerStep5);
  }

  function runWalkCycle(chibi) {
    if (isOpen) return;
    activeChibi = chibi;

    if (chibi === 'girl') {
      // NURSE MIKA CYCLE: Walk In -> Wave Hello -> Stand Idle Blink -> Wave Bye -> Walk Out
      actorState = "state-walk-in";
      showBubble = false;

      // 1. Walk in (2.6s) -> Wave Hello (1.6s) with bubble
      timerStep1 = setTimeout(() => {
        if (isOpen) return;
        actorState = "state-wave-hello";
        showBubble = true;

        // 2. After waving hello (1.6s) -> Stand Idle on both feet with periodic eye blinking (3.6s)
        timerStep2 = setTimeout(() => {
          if (isOpen) return;
          actorState = "state-arrived";
          showBubble = true;

          // 3. After standing idle (3.6s) -> Wave Bye before exiting (1.4s)
          timerStep3 = setTimeout(() => {
            if (isOpen) return;
            actorState = "state-wave-bye";
            showBubble = false;

            // 4. After wave bye (1.4s) -> Walk Out to the right (2.2s)
            timerStep4 = setTimeout(() => {
              if (isOpen) return;
              actorState = "state-walk-out";

              // 5. After walked out -> Switch to Dr. Leo
              timerStep5 = setTimeout(() => {
                if (isOpen) return;
                runWalkCycle("boy");
              }, 2200);
            }, 1400);

          }, 3600);

        }, 1600);

      }, 2600);

    } else {
      // DR. LEO CYCLE (Unchanged)
      actorState = "state-walk-in";
      showBubble = false;

      // After walking in (2.6s), pop up bubble
      timerStep1 = setTimeout(() => {
        if (isOpen) return;
        actorState = "state-arrived";
        showBubble = true;

        // After 5.5s with bubble, walk out to right side (2.2s)
        timerStep2 = setTimeout(() => {
          if (isOpen) return;
          showBubble = false;
          actorState = "state-walk-out";

          // After 2.2s walk out, switch to Nurse Mika
          timerStep3 = setTimeout(() => {
            if (isOpen) return;
            runWalkCycle("girl");
          }, 2200);
        }, 5500);
      }, 2600);
    }
  }

  onMount(() => {
    runWalkCycle("boy");
  });

  onDestroy(() => {
    clearTimers();
  });

  function openChatWith(chibi) {
    chatAssistant = chibi;
    questionCount = 0;
    showOptions = true;
    showRating = false;
    userRating = 0;
    initMessages();
    if (!isOpen) {
      isOpen = true;
      clearTimers();
    }
  }

  function toggleChat() {
    isOpen = !isOpen;
    if (isOpen) {
      clearTimers();
    } else {
      runWalkCycle(activeChibi);
    }
  }

  function toggleAssistant() {
    chatAssistant = chatAssistant === "boy" ? "girl" : "boy";
    initMessages();
  }

  function changeLang(newLang) {
    lang = newLang;
    questionCount = 0;
    showOptions = true;
    showRating = false;
    userRating = 0;
    initMessages();
  }

  function handleQuestionClick(faq) {
    if (questionCount >= 4 || isTyping) return;

    messages = [
      ...messages,
      { sender: "user", text: faq.question, time: getCurrentTime() },
    ];
    questionCount++;
    showOptions = false;
    isTyping = true;

    setTimeout(() => {
      isTyping = false;
      messages = [
        ...messages,
        {
          sender: "bot",
          text: faq.answer,
          time: getCurrentTime(),
          avatar: currentProfile.avatar,
          name: currentProfile.name[lang],
        },
      ];

      if (questionCount >= 4) {
        showRating = true;
      }

      setTimeout(() => {
        if (chatBody) {
          chatBody.scrollTop = chatBody.scrollHeight;
        }
      }, 50);
    }, 450);
  }

  function handleRating(star) {
    userRating = star;
    showRating = false;
    messages = [
      ...messages,
      {
        sender: "bot",
        text: currentDict.feedbackThanks(star),
        time: getCurrentTime(),
        avatar: currentProfile.avatar,
        name: currentProfile.name[lang],
      },
    ];
  }

  function resetOptions() {
    showOptions = true;
  }

  afterUpdate(() => {
    if (chatBody) {
      chatBody.scrollTop = chatBody.scrollHeight;
    }
  });
</script>

<!-- Floating Chibi Widget in Bottom-Right -->
{#if !isOpen}
  <div class="chibi-widget-container" transition:fade>
    <div
      class="chibi-stage"
      on:click={() => openChatWith(activeChibi)}
      role="button"
      tabindex="0"
      on:keydown={(e) => {
        if (e.key === "Enter" || e.key === " ") openChatWith(activeChibi);
      }}
      title="Click to ask me!"
    >
      <div class="chibi-ground-shadow"></div>

      <!-- Walking Actor -->
      <div class="chibi-character-actor actor-{activeChibi} {actorState}">
        {#if showBubble}
          <div
            class="chibi-ask-me-bubble"
            class:is-tl={lang === "TL"}
            on:click|stopPropagation={() => openChatWith(activeChibi)}
            role="button"
            tabindex="0"
            title={lang === "TL" ? "TANUNGIN MO AKO" : "ASK ME"}
          ></div>
        {/if}
        {#if activeChibi === "boy"}
          <div class="chibi-sprite chibi-sprite-boy" aria-label="Dr. Leo Chibi"></div>
        {:else if actorState === "state-wave-hello" || actorState === "state-wave-bye"}
          <div class="chibi-sprite chibi-sprite-girl-wave" aria-label="Nurse Mika Waving"></div>
        {:else}
          <div class="chibi-sprite chibi-sprite-girl" aria-label="Nurse Mika Chibi"></div>
        {/if}
      </div>
    </div>
  </div>
{/if}

<!-- Chat Window -->
{#if isOpen}
  <div class="chibi-chat-window is-open" transition:fly={{ y: 50, duration: 300 }}>
    <!-- Header with SINGLE Head Circle -->
    <div class="chibi-chat-header">
      <div class="chibi-header-info">
        <!-- Single Head Circle Avatar -->
        <div
          class="chibi-avatar-single"
          on:click={toggleAssistant}
          role="button"
          tabindex="0"
          title="Click to switch assistant"
        >
          <img src={currentProfile.avatar} alt={currentProfile.name[lang]} />
        </div>
        <div class="chibi-header-text">
          <h3>{currentProfile.name[lang]}</h3>
          <p>{currentProfile.role[lang]}</p>
        </div>
      </div>

      <div class="chibi-header-actions">
        <div class="chibi-lang-switch">
          <button
            class="chibi-lang-btn"
            class:active={lang === "EN"}
            on:click={() => changeLang("EN")}
          >
            EN
          </button>
          <button
            class="chibi-lang-btn"
            class:active={lang === "TL"}
            on:click={() => changeLang("TL")}
          >
            TL
          </button>
        </div>
        <button
          class="chibi-close-btn"
          on:click={toggleChat}
          aria-label="Close Chat"
        >
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
          >
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <!-- Chat Log -->
    <div class="chibi-chat-body" bind:this={chatBody}>
      {#each messages as msg}
        <div class="chibi-msg-row {msg.sender}">
          {#if msg.sender === "bot"}
            <div class="chibi-msg-avatar" title={msg.name}>
              <img src={msg.avatar} alt={msg.name} />
            </div>
          {/if}
          <div class="chibi-msg-box">
            <div class="chibi-msg-bubble {msg.sender}">
              {@html msg.text}
            </div>
            <div class="chibi-msg-time">{msg.time}</div>
          </div>
        </div>
      {/each}

      {#if isTyping}
        <div class="chibi-typing-row">
          <div class="chibi-msg-avatar">
            <img src={currentProfile.avatar} alt={currentProfile.name[lang]} />
          </div>
          <div class="chibi-typing-bubble">
            <span class="chibi-dot"></span>
            <span class="chibi-dot"></span>
            <span class="chibi-dot"></span>
          </div>
        </div>
      {/if}

      {#if showOptions && !isTyping}
        <div class="chibi-faq-container" transition:fade>
          <span class="chibi-faq-label">{currentDict.selectQuestion}</span>
          {#each currentFaqs as faq}
            <button
              class="chibi-faq-item"
              on:click={() => handleQuestionClick(faq)}
            >
              {faq.question}
            </button>
          {/each}
        </div>
      {:else if showRating && userRating === 0}
        <div class="chibi-rating-card" transition:fade>
          <p>{currentDict.limitReached}</p>
          <div class="chibi-stars">
            {#each [1, 2, 3, 4, 5] as star}
              <button
                class="chibi-star-btn"
                on:click={() => handleRating(star)}
                title="{star} Star"
              >
                ★
              </button>
            {/each}
          </div>
        </div>
      {:else if userRating === 0 && !isTyping}
        <div class="chibi-reset-container" transition:fade>
          <button class="chibi-reset-btn" on:click={resetOptions}>
            {currentDict.askAnother}
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .chibi-widget-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 99999;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    pointer-events: auto;
    font-family: 'Outfit', 'Inter', sans-serif;
  }

  .chibi-stage {
    position: relative;
    width: 140px;
    height: 140px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    cursor: pointer;
    background: transparent;
    padding: 0;
    border: none;
    overflow: visible;
  }

  .chibi-ground-shadow {
    position: absolute;
    bottom: 0px;
    width: 65px;
    height: 14px;
    background: radial-gradient(ellipse at center, rgba(16, 70, 36, 0.35) 0%, rgba(16, 70, 36, 0.08) 55%, transparent 75%);
    border-radius: 50%;
    pointer-events: none;
  }

  .chibi-character-actor {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
  }

  /* ------------------- BOY: Dr. Leo Walk Cycles ------------------- */
  .chibi-character-actor.actor-boy.state-walk-in,
  .chibi-character-actor.state-walk-in {
    animation: boySlowWalkIn 2.6s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
  }

  @keyframes boySlowWalkIn {
    0% { transform: translateX(115px); opacity: 0; }
    10% { opacity: 1; }
    100% { transform: translateX(0); opacity: 1; }
  }

  .chibi-character-actor.actor-boy.state-arrived {
    transform: translateX(0);
    opacity: 1;
    animation: chibiGentleBreathe 2.6s ease-in-out infinite;
  }

  .chibi-character-actor.actor-boy.state-walk-out,
  .chibi-character-actor.state-walk-out {
    animation: boySlowWalkOut 2.2s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
  }

  @keyframes boySlowWalkOut {
    0% { transform: translateX(0) scaleX(-1); opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateX(130px) scaleX(-1); opacity: 0; }
  }

  /* ------------------- GIRL: Nurse Mika Walk & Wave Cycles ------------------- */
  .chibi-character-actor.actor-girl.state-walk-in {
    animation: girlStraightWalkIn 2.6s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
  }

  @keyframes girlStraightWalkIn {
    0% { transform: translateX(115px); opacity: 0; }
    10% { opacity: 1; }
    100% { transform: translateX(0); opacity: 1; }
  }

  .chibi-character-actor.actor-girl.state-wave-hello {
    transform: translateX(0);
    opacity: 1;
    animation: chibiGentleBreathe 2.4s ease-in-out infinite;
  }

  .chibi-character-actor.actor-girl.state-arrived {
    transform: translateX(0);
    opacity: 1;
    animation: chibiGentleBreathe 2.4s ease-in-out infinite;
  }

  .chibi-character-actor.actor-girl.state-wave-bye {
    transform: translateX(0);
    opacity: 1;
    animation: chibiGentleBreathe 2.4s ease-in-out infinite;
  }

  .chibi-character-actor.actor-girl.state-walk-out {
    animation: girlStraightWalkOut 2.2s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
  }

  @keyframes girlStraightWalkOut {
    0% { transform: translateX(0) scaleX(-1); opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateX(130px) scaleX(-1); opacity: 0; }
  }

  @keyframes chibiGentleBreathe {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-2.5px); }
  }

  .chibi-character-actor.state-arrived {
    transform: translateX(0);
    opacity: 1;
  }

  .chibi-sprite {
    display: block;
    image-rendering: pixelated;
    image-rendering: -moz-crisp-edges;
    image-rendering: crisp-edges;
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.16));
  }

  /* Boy Chibi Sprite: 16 frames, 380x655 per frame (height 124px, width 72px) */
  .chibi-sprite-boy {
    width: 72px;
    height: 124px;
    background-image: url('/sdn/public/assets/boy_walk_spritesheet.png');
    background-size: 1152px 124px;
    background-repeat: no-repeat;
    animation: boyStep 1.0s steps(16) infinite;
  }

  .chibi-character-actor.state-arrived .chibi-sprite-boy {
    animation: none !important;
    background-position: 0 0 !important;
  }

  @keyframes boyStep {
    from { background-position: 0 0; }
    to { background-position: -1152px 0; }
  }

  /* Girl Chibi Walking Sprite: 16 frames natural leg walk cycle with walking eye blinks, 360x635 per frame (height 120px, width 68px) */
  .chibi-sprite-girl {
    width: 68px;
    height: 120px;
    background-image: url('/sdn/public/assets/girl_walk_spritesheet.png');
    background-size: 1088px 120px;
    background-repeat: no-repeat;
    animation: girlStep 1.0s steps(16) infinite;
  }

  /* When girl is arrived, stand cleanly on both feet and blink eyes smoothly!
     girl_idle_spritesheet.png has 4 frames:
     Frame 0 (0px): Open eyes (normal resting face)
     Frame 1 (-68px): Half-closed eyes (natural eyelid drop transition)
     Frame 2 (-136px): Fully closed eyes (sweet smiling anime curved lashes)
     Frame 3 (-204px): Half-open eyes (natural eyelid opening transition) */
  .chibi-character-actor.actor-girl.state-arrived .chibi-sprite-girl {
    background-image: url('/sdn/public/assets/girl_idle_spritesheet.png') !important;
    background-size: 272px 120px !important;
    animation: girlIdleBlink 3.6s steps(1) infinite !important;
  }

  @keyframes girlIdleBlink {
    0%, 91.5%, 98.5%, 100% {
      background-position: 0 0;
    }
    92%, 97.5% {
      background-position: -68px 0;
    }
    93.5%, 96% {
      background-position: -136px 0;
    }
  }

  /* Girl Waving Sprite: 4 frames, 480x635 per frame (height 120px, width 91px)
     Raised arm holding pen, swinging back and forth with smiling/blinking face! */
  .chibi-sprite-girl-wave {
    width: 91px;
    height: 120px;
    margin-left: -11px;
    background-image: url('/sdn/public/assets/girl_wave_spritesheet.png');
    background-size: 364px 120px;
    background-repeat: no-repeat;
    animation: girlWave 0.75s steps(4) infinite;
  }

  @keyframes girlWave {
    from { background-position: 0 0; }
    to { background-position: -364px 0; }
  }

  .chibi-ask-me-bubble {
    position: absolute;
    top: -46px;
    left: -86px;
    width: 96px;
    height: 52px;
    background-image: url('/sdn/public/assets/chibi_ask_me.png');
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
    image-rendering: pixelated;
    filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.2));
    cursor: pointer;
    z-index: 20;
    animation: askMePopIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s both,
               askMeFloat 3s ease-in-out infinite 0.6s;
    transition: transform 0.2s ease, filter 0.2s ease, width 0.2s ease, height 0.2s ease, top 0.2s ease, left 0.2s ease;
  }

  .chibi-ask-me-bubble.is-tl {
    width: 124px;
    height: 68px;
    top: -62px;
    left: -114px;
    background-image: url('/sdn/public/assets/chibi_ask_me_tl.png') !important;
  }

  @keyframes askMePopIn {
    0% { opacity: 0; transform: scale(0.2) translateY(18px) rotate(-6deg); }
    70% { opacity: 1; transform: scale(1.1) translateY(-3px) rotate(1deg); }
    100% { opacity: 1; transform: scale(1) translateY(0) rotate(0deg); }
  }

  @keyframes askMeFloat {
    0% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
    100% { transform: translateY(0); }
  }

  .chibi-chat-window {
    position: fixed;
    bottom: 5rem;
    right: 2rem;
    width: 390px;
    height: 560px;
    max-height: calc(100vh - 7rem);
    background: #fafcfb;
    border-radius: 20px;
    box-shadow: 0 16px 45px rgba(0, 0, 0, 0.22);
    display: flex;
    flex-direction: column;
    z-index: 100000;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
    border: 1px solid #e0ece5;
  }

  .chibi-chat-header {
    background: linear-gradient(135deg, #1b532e 0%, #286b46 50%, #1f5634 100%);
    color: white;
    padding: 1.15rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .chibi-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
  }

  .chibi-avatar-single {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #ffffff;
    border: 2.5px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.22);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    cursor: pointer;
  }

  .chibi-avatar-single img {
    width: 88%;
    height: 88%;
    object-fit: contain;
    object-position: center bottom;
    transform: translateY(2px);
    image-rendering: pixelated;
  }

  .chibi-header-text h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #ffffff;
  }

  .chibi-header-text p {
    margin: 2px 0 0 0;
    font-size: 0.75rem;
    color: #a7f3d0;
    font-weight: 500;
  }

  .chibi-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .chibi-lang-switch {
    display: flex;
    background: rgba(0, 0, 0, 0.22);
    border-radius: 20px;
    padding: 2px;
  }

  .chibi-lang-btn {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 14px;
    cursor: pointer;
  }

  .chibi-lang-btn.active {
    background: #ffffff;
    color: #1e5a32;
  }

  .chibi-close-btn {
    background: rgba(255, 255, 255, 0.12);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .chibi-chat-body {
    flex: 1;
    padding: 1.25rem 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background-color: #f7faf8;
  }

  .chibi-msg-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
  }

  .chibi-msg-row.user {
    justify-content: flex-end;
  }

  .chibi-msg-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ffffff;
    border: 1.5px solid #d1fae5;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
    overflow: hidden;
    margin-bottom: 2px;
  }

  .chibi-msg-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    image-rendering: pixelated;
  }

  .chibi-msg-box {
    display: flex;
    flex-direction: column;
    max-width: 80%;
  }

  .chibi-msg-row.user .chibi-msg-box {
    align-items: flex-end;
  }

  .chibi-msg-bubble {
    padding: 12px 16px;
    border-radius: 20px;
    font-size: 0.92rem;
    line-height: 1.5;
    word-wrap: break-word;
    color: #1e293b;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
  }

  .chibi-msg-bubble.bot {
    background: #ffffff;
    border: 1px solid #e2ece6;
    border-bottom-left-radius: 4px;
  }

  .chibi-msg-bubble.user {
    background: linear-gradient(135deg, #1b532e 0%, #286b46 100%);
    color: #ffffff;
    border-bottom-right-radius: 4px;
  }

  .chibi-msg-time {
    font-size: 0.68rem;
    color: #94a3b8;
    margin-top: 4px;
    margin-left: 6px;
    font-weight: 500;
  }

  .chibi-typing-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
  }

  .chibi-typing-bubble {
    background: #ffffff;
    border: 1px solid #e2ece6;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .chibi-dot {
    width: 7px;
    height: 7px;
    background-color: #10b981;
    border-radius: 50%;
    animation: typingBlink 1.4s infinite ease-in-out both;
  }
  .chibi-dot:nth-child(1) { animation-delay: -0.32s; }
  .chibi-dot:nth-child(2) { animation-delay: -0.16s; }

  @keyframes typingBlink {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1.1); opacity: 1; }
  }

  .chibi-faq-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 6px;
    padding-left: 48px;
  }

  .chibi-faq-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #4b6b55;
  }

  .chibi-faq-item {
    background: #ffffff;
    border: 1.5px solid #d5e7dc;
    color: #1e5a32;
    padding: 10px 14px;
    border-radius: 16px;
    text-align: left;
    font-size: 0.86rem;
    font-weight: 600;
    cursor: pointer;
    line-height: 1.4;
    transition: all 0.2s ease;
  }

  .chibi-faq-item:hover {
    background: #f0fdf4;
    border-color: #10b981;
    transform: translateY(-2px);
  }

  .chibi-reset-container {
    display: flex;
    justify-content: center;
    margin-top: 10px;
  }

  .chibi-reset-btn {
    background: #ffffff;
    border: 1.5px solid #10b981;
    color: #10b981;
    font-size: 0.86rem;
    font-weight: 700;
    padding: 8px 18px;
    border-radius: 20px;
    cursor: pointer;
  }

  .chibi-rating-card {
    background: #ffffff;
    border: 1px solid #d5e7dc;
    border-radius: 16px;
    padding: 16px;
    text-align: center;
  }

  .chibi-stars {
    display: flex;
    justify-content: center;
    gap: 8px;
  }

  .chibi-star-btn {
    background: none;
    border: none;
    font-size: 1.8rem;
    color: #cbd5e1;
    cursor: pointer;
  }
  .chibi-star-btn:hover {
    color: #f59e0b;
    transform: scale(1.25);
  }

  @media (max-width: 600px) {
    .chibi-widget-container { bottom: 0.85rem; right: 0.85rem; }
    .chibi-stage {
      transform: scale(0.85);
      transform-origin: bottom right;
    }
    .chibi-chat-window { width: calc(100vw - 1.5rem); right: 0.75rem; bottom: 4.8rem; height: 490px; }
  }

  @media (max-width: 380px) {
    .chibi-stage {
      transform: scale(0.78);
      transform-origin: bottom right;
    }
  }
</style>
