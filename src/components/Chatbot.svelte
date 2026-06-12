<script>
  import { fly, fade } from 'svelte/transition';
  import { afterUpdate } from 'svelte';

  let isOpen = false;

  function getCurrentTime() {
    return new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
  }
  
  const faqs = [
    {
      question: "How do I request an appointment/schedule?",
      answer: "You can book a consultation by clicking the 'Book Appointment' button on the homepage, selecting your desired department, and filling out the registration form. Wait for the confirmation from our staff."
    },
    {
      question: "What are the hospital's operating hours?",
      answer: "Our main facility operates 24/7 for emergencies. Outpatient teleconsultations and physical consultations are generally available Monday to Friday, 8:00 AM to 5:00 PM."
    },
    {
      question: "Where can I find the nearest Service Delivery Network (SDN) facility?",
      answer: "You can view all affiliated SDN facilities and their locations in our 'Location' section on the main page."
    },
    {
      question: "What should I bring for my consultation?",
      answer: "Please bring a valid ID, any previous medical records, and your referral form if you have one."
    }
  ];

  let messages = [
    { sender: 'bot', text: 'Hello! 👋<br/>Welcome to LINKod. How can I assist you today?', time: getCurrentTime() }
  ];

  let showOptions = true;
  let chatBody;

  function toggleChat() {
    isOpen = !isOpen;
  }

  function handleQuestionClick(faq) {
    showOptions = false;
    messages = [...messages, { sender: 'user', text: faq.question, time: getCurrentTime() }];
    
    // Simulate realistic typing delay
    setTimeout(() => {
      messages = [...messages, { sender: 'bot', text: faq.answer, time: getCurrentTime() }];
    }, 400);
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

<!-- Floating Chat Button -->
{#if !isOpen}
  <button class="chat-fab" on:click={toggleChat} transition:fade>
    <!-- Custom Bot Icon for FAB -->
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 8V4H8"></path>
      <rect x="4" y="8" width="16" height="12" rx="2" ry="2"></rect>
      <path d="M2 14h2"></path>
      <path d="M20 14h2"></path>
      <path d="M15 13v2"></path>
      <path d="M9 13v2"></path>
    </svg>
  </button>
{/if}

<!-- Chat Window -->
{#if isOpen}
  <div class="chat-window" transition:fly="{{ y: 50, duration: 300 }}">
    <!-- Header -->
    <div class="chat-header">
      <div class="header-title">
        <div class="bot-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 8V4H8"></path>
            <rect x="4" y="8" width="16" height="12" rx="2" ry="2"></rect>
            <path d="M2 14h2"></path>
            <path d="M20 14h2"></path>
            <path d="M15 13v2"></path>
            <path d="M9 13v2"></path>
          </svg>
          <span class="status-dot"></span>
        </div>
        LINKod Assistant
      </div>
      <button class="close-btn" on:click={toggleChat} aria-label="Close Chat">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    <!-- Chat Log -->
    <div class="chat-body" bind:this={chatBody}>
      {#each messages as msg}
        <div class="message-wrapper {msg.sender}">
          {#if msg.sender === 'bot'}
            <div class="message-avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8V4H8"></path>
                <rect x="4" y="8" width="16" height="12" rx="2" ry="2"></rect>
                <path d="M2 14h2"></path>
                <path d="M20 14h2"></path>
                <path d="M15 13v2"></path>
                <path d="M9 13v2"></path>
              </svg>
              <span class="avatar-status-dot"></span>
            </div>
          {/if}
          <div class="message-content">
            <div class="message-bubble {msg.sender}">
              {@html msg.text}
            </div>
            <div class="message-time">{msg.time}</div>
          </div>
        </div>
      {/each}

      {#if showOptions}
        <div class="options-container" transition:fade>
          <p class="options-title">Select a question:</p>
          {#each faqs as faq}
            <button class="faq-btn" on:click={() => handleQuestionClick(faq)}>
              {faq.question}
            </button>
          {/each}
        </div>
      {:else}
        <div class="back-container" transition:fade>
          <button class="back-btn" on:click={resetOptions}>Ask another question</button>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  /* The Floating Action Button */
  .chat-fab {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    cursor: pointer;
    
    /* 3D Glassy/Sphere Effect */
    background: radial-gradient(circle at 30% 30%, #45b877 0%, var(--primary-green, #286b46) 60%, var(--dark-green, #1e4d34) 100%);
    box-shadow: 
      inset -4px -4px 10px rgba(0, 0, 0, 0.3), 
      inset 4px 4px 12px rgba(255, 255, 255, 0.5), 
      0 8px 20px rgba(30, 77, 52, 0.5);
    
    animation: pulse 2.5s infinite;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.2s;
  }

  .chat-fab:hover {
    transform: scale(1.05) translateY(-2px);
    background: radial-gradient(circle at 30% 30%, #51cf86 0%, #328557 60%, var(--dark-green, #1e4d34) 100%);
    box-shadow: 
      inset -4px -4px 10px rgba(0, 0, 0, 0.3), 
      inset 4px 4px 12px rgba(255, 255, 255, 0.6), 
      0 12px 25px rgba(30, 77, 52, 0.6);
  }

  @keyframes pulse {
    0% { 
      box-shadow: inset -4px -4px 10px rgba(0, 0, 0, 0.3), inset 4px 4px 12px rgba(255, 255, 255, 0.5), 0 0 0 0 rgba(40, 107, 70, 0.6); 
    }
    70% { 
      box-shadow: inset -4px -4px 10px rgba(0, 0, 0, 0.3), inset 4px 4px 12px rgba(255, 255, 255, 0.5), 0 0 0 18px rgba(40, 107, 70, 0); 
    }
    100% { 
      box-shadow: inset -4px -4px 10px rgba(0, 0, 0, 0.3), inset 4px 4px 12px rgba(255, 255, 255, 0.5), 0 0 0 0 rgba(40, 107, 70, 0); 
    }
  }

  /* The Chat Window */
  .chat-window {
    position: fixed;
    bottom: 5rem;
    right: 2rem;
    width: 380px;
    height: 520px; /* Slightly taller for the larger bubbles */
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
    font-family: 'Inter', -apple-system, sans-serif;
  }

  /* Header */
  .chat-header {
    background-color: var(--primary-green, #286b46);
    color: white;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
  }

  .header-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.05rem;
  }

  .bot-avatar {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 38px;
    height: 38px;
  }

  .status-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background-color: #4ade80; /* Bright active green */
    border-radius: 50%;
    border: 2px solid var(--primary-green, #286b46);
  }

  .close-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s;
  }

  .close-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
  }

  /* Chat Body */
  .chat-body {
    flex: 1;
    padding: 1.5rem 1.25rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background-color: #f8faf9;
    scroll-behavior: smooth;
  }

  /* Messages Wrapper */
  .message-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }

  .message-wrapper.user {
    justify-content: flex-end;
  }

  /* Message Avatar */
  .message-avatar {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--dark-green, #1e4d34);
    color: white;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(30, 77, 52, 0.15);
    margin-top: 2px;
  }

  .avatar-status-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 13px;
    height: 13px;
    background-color: #4ade80; 
    border-radius: 50%;
    border: 2px solid #f8faf9;
  }

  /* Message Content Block */
  .message-content {
    display: flex;
    flex-direction: column;
    max-width: 82%;
  }

  .message-wrapper.user .message-content {
    align-items: flex-end;
  }

  /* Actual Message Bubble */
  .message-bubble {
    padding: 12px 18px;
    border-radius: 22px;
    font-size: 1rem;
    line-height: 1.5;
    word-wrap: break-word;
    color: #1a1a1a;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
  }

  .message-bubble.bot {
    background-color: #ffffff;
    border: 1px solid #e0ece5;
    border-top-left-radius: 4px;
    color: #1f2937;
  }

  .message-bubble.user {
    background-color: var(--primary-green, #286b46);
    color: white;
    border-top-right-radius: 4px;
  }

  /* Message Timestamp */
  .message-time {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 6px;
    margin-left: 8px;
    font-weight: 500;
  }

  .message-wrapper.user .message-time {
    margin-right: 8px;
    margin-left: 0;
  }

  /* Options */
  .options-container {
    margin-top: 4px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-left: 50px; /* Align with bot text */
  }

  .options-title {
    font-size: 0.85rem;
    color: #6b7c6f;
    margin: 0 0 4px 0;
    font-weight: 500;
  }

  .faq-btn {
    background-color: white;
    border: 1.5px solid #e0ece5;
    color: var(--primary-green, #286b46);
    padding: 12px 16px;
    border-radius: 18px;
    text-align: left;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    line-height: 1.4;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  }

  .faq-btn:hover {
    background-color: #f0fdf4;
    border-color: var(--primary-green, #286b46);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 107, 70, 0.08);
  }

  .back-container {
    margin-top: 16px;
    display: flex;
    justify-content: center;
  }

  .back-btn {
    background: none;
    border: none;
    color: var(--primary-green, #286b46);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    padding: 8px;
  }

  .back-btn:hover {
    color: var(--dark-green, #1e4d34);
  }

  /* Scrollbar Styling for Webkit */
  .chat-body::-webkit-scrollbar {
    width: 6px;
  }
  .chat-body::-webkit-scrollbar-track {
    background: transparent;
  }
  .chat-body::-webkit-scrollbar-thumb {
    background-color: #b4ccbb;
    border-radius: 10px;
  }

  @media (max-width: 480px) {
    .chat-window {
      width: calc(100% - 2rem);
      right: 1rem;
      bottom: 5rem;
      height: 400px;
    }
  }
</style>
