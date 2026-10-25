import { writable } from 'svelte/store';
import { ASSISTANTS } from '../services/chatbotService.js';

export function createChatbotStore() {
  const isOpen = writable(false);
  const activeChibi = writable('boy');
  const chatAssistant = writable('boy');
  const lang = writable('EN');
  const messages = writable([]);
  const showBubble = writable(false);
  const isTyping = writable(false);

  return {
    isOpen,
    activeChibi,
    chatAssistant,
    lang,
    messages,
    showBubble,
    isTyping,
    openChat: () => isOpen.set(true),
    closeChat: () => isOpen.set(false),
    toggleChat: () => isOpen.update(v => !v),
    setLang: (l) => lang.set(l),
    setAssistant: (a) => {
      chatAssistant.set(a);
      activeChibi.set(a);
    },
    addMessage: (msg) => messages.update(m => [...m, msg]),
    clearMessages: () => messages.set([])
  };
}

export const chatbotStore = createChatbotStore();
