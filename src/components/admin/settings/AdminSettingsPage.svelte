<script>
  import { onMount } from 'svelte';
  import { fade, fly } from 'svelte/transition';
  import { cubicOut } from 'svelte/easing';
  import { createSettingsService } from '../../../services/settingsService.js';
  import { createSettingsStore } from '../../../stores/settingsStore.js';
  import { toastStore } from '../../../stores/toastStore.js';
  import Toast from '../../ui/Toast.svelte';
  import SettingsModal from './SettingsModal.svelte';
  import GeneralSettingsForm from './GeneralSettingsForm.svelte';
  import SchedulingRulesForm from './SchedulingRulesForm.svelte';
  import ChatbotFaqManager from './ChatbotFaqManager.svelte';

  export let apiBase = '/sdn/api';

  const service = createSettingsService(apiBase);
  const store = createSettingsStore();
  const { draft, fieldErrors, loading, saving, dirtySections } = store;

  const tabs = [
    { id: 'general', label: 'General Configuration', icon: 'building' },
    { id: 'scheduling', label: 'Scheduling Rules', icon: 'calendar' },
    { id: 'content', label: 'FAQ Management', icon: 'chat' },
  ];

  let activeTab = 'general';
  let pendingTab = null;
  let faqToDelete = null;
  let deletingFaq = false;
  let faqs = [];
  let faqsLoading = false;
  let loadError = '';
  let contentPaneEl = null;

  function scrollContentTop() {
    if (contentPaneEl) contentPaneEl.scrollTop = 0;
  }

  $: isCurrentDirty = activeTab !== 'content' && Boolean($dirtySections[activeTab]);

  async function loadAll() {
    loading.set(true);
    loadError = '';
    try {
      const [settingsRes, faqsRes] = await Promise.all([
        service.fetchAll(),
        service.fetchFaqs(),
      ]);
      store.hydrate(settingsRes.data || {});
      faqs = faqsRes.data || [];
    } catch (e) {
      loadError = e?.message || 'Unable to load system settings.';
    } finally {
      loading.set(false);
    }
  }

  function requestTabSwitch(nextTab) {
    if (nextTab === activeTab) return;
    if (isCurrentDirty) {
      pendingTab = nextTab;
      return;
    }
    activeTab = nextTab;
    fieldErrors.set({});
    scrollContentTop();
  }

  function confirmDiscardAndSwitch() {
    store.discardSection(activeTab);
    activeTab = pendingTab;
    pendingTab = null;
    scrollContentTop();
  }

  async function saveCurrentSection() {
    if (activeTab === 'content') return;
    saving.set(true);
    fieldErrors.set({});
    try {
      let res;
      if (activeTab === 'general') res = await service.saveGeneral($draft.general);
      else if (activeTab === 'scheduling') res = await service.saveScheduling($draft.scheduling);

      store.commitSection(activeTab, res.data);
      toastStore.add(res.message || 'Settings saved.', 'success');
    } catch (e) {
      if (e?.status === 422 && e?.data?.errors) {
        fieldErrors.set(e.data.errors);
        toastStore.add('Please fix the highlighted validation errors.', 'error');
      } else {
        toastStore.add(e?.message || 'Failed to save settings.', 'error');
      }
    } finally {
      saving.set(false);
    }
  }

  async function refreshFaqs() {
    faqsLoading = true;
    try {
      const res = await service.fetchFaqs();
      faqs = res.data || [];
    } finally {
      faqsLoading = false;
    }
  }

  async function handleCreateFaq(payload) {
    await service.createFaq(payload);
    await refreshFaqs();
    toastStore.add('FAQ entry created.', 'success');
  }

  async function handleUpdateFaq(id, payload) {
    await service.updateFaq(id, payload);
    await refreshFaqs();
    toastStore.add('FAQ entry updated.', 'success');
  }

  function requestDeleteFaq(faq) {
    faqToDelete = faq;
  }

  async function confirmDeleteFaq() {
    if (!faqToDelete) return;
    deletingFaq = true;
    try {
      await service.deleteFaq(faqToDelete.id);
      await refreshFaqs();
      toastStore.add('FAQ entry deleted.', 'success');
      faqToDelete = null;
    } catch (e) {
      toastStore.add(e?.message || 'Failed to delete FAQ.', 'error');
    } finally {
      deletingFaq = false;
    }
  }

  async function handleReorderFaqs(items) {
    try {
      await service.reorderFaqs(items);
      await refreshFaqs();
      toastStore.add('FAQ order updated.', 'info', 2500);
    } catch (e) {
      toastStore.add(e?.message || 'Failed to reorder FAQs.', 'error');
    }
  }

  onMount(loadAll);
</script>

<div class="settings-root rounded-xl border border-gray-200/80 bg-[#f9fafb] text-gray-900">
  <Toast />

  <header class="settings-header shrink-0 border-b border-gray-200 bg-white px-6 py-5">
    <div class="mx-auto flex max-w-6xl flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-[#0f2e1c]">System Settings</h1>
        <p class="text-sm text-gray-500">
          Manage hospital operating hours, holidays, department scheduling limits, and FAQ content.
        </p>
      </div>
      {#if $draft.meta?.updated_at}
        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-500">
          <span class="h-1.5 w-1.5 rounded-full bg-[#166534]"></span>
          Last saved: {new Date($draft.meta.updated_at).toLocaleString()}
        </span>
      {/if}
    </div>
  </header>

  <div class="settings-body">
    <div class="settings-body-inner mx-auto max-w-6xl">
      {#if loadError}
        <div
          class="mb-4 flex shrink-0 items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
          transition:fade={{ duration: 150 }}
        >
          <div class="flex items-center gap-2.5">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <span>{loadError}</span>
          </div>
          <button type="button" on:click={loadAll} class="cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold text-red-800 hover:bg-red-100">
            Retry
          </button>
        </div>
      {/if}

      <div class="settings-shell">
        <nav aria-label="Settings sections" class="settings-rail">
          <div class="settings-tabs" role="tablist">
            {#each tabs as tab (tab.id)}
              {@const isSelected = activeTab === tab.id}
              {@const hasUnsaved = tab.id !== 'content' && Boolean($dirtySections[tab.id])}
              <button
                type="button"
                role="tab"
                aria-selected={isSelected}
                on:click={() => requestTabSwitch(tab.id)}
                class="flex shrink-0 cursor-pointer items-center justify-between gap-2.5 rounded-xl border px-3.5 py-2.5 text-left text-sm transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] active:scale-[0.99] {isSelected
                  ? 'border-[#bbf7d0] bg-[#f0fdf4] font-semibold text-[#166534] shadow-xs'
                  : 'border-transparent bg-transparent font-medium text-gray-600 hover:bg-gray-100/90 hover:text-gray-900'}"
              >
                <span class="flex items-center gap-2.5 whitespace-nowrap">
                  <svg class="h-4 w-4 shrink-0 {isSelected ? 'text-[#166534]' : 'text-gray-400'}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    {#if tab.icon === 'building'}
                      <rect x="4" y="2" width="16" height="20" rx="2" /><path d="M9 22v-4h6v4" /><path d="M8 6h.01" /><path d="M16 6h.01" /><path d="M12 6h.01" /><path d="M12 10h.01" /><path d="M12 14h.01" /><path d="M16 10h.01" /><path d="M16 14h.01" /><path d="M8 10h.01" /><path d="M8 14h.01" />
                    {:else if tab.icon === 'calendar'}
                      <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                    {:else}
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    {/if}
                  </svg>
                  <span>{tab.label}</span>
                </span>
                {#if hasUnsaved}
                  <span class="h-2 w-2 shrink-0 rounded-full bg-[#166534]" title="Unsaved changes"></span>
                {/if}
              </button>
            {/each}
          </div>
        </nav>

        <div class="settings-content-pane" bind:this={contentPaneEl}>
        {#if $loading}
          <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6" aria-busy="true" aria-label="Loading settings">
            <div class="skeleton-bar h-5 w-48 rounded"></div>
            <div class="skeleton-bar h-3.5 w-72 rounded"></div>
            <div class="grid grid-cols-1 gap-4 pt-3 sm:grid-cols-2">
              <div class="skeleton-bar h-10 rounded-lg sm:col-span-2"></div>
              <div class="skeleton-bar h-10 rounded-lg"></div>
              <div class="skeleton-bar h-10 rounded-lg"></div>
              <div class="skeleton-bar h-16 rounded-lg sm:col-span-2"></div>
            </div>
          </div>
        {:else}
          {#key activeTab}
            <div in:fade={{ duration: 150, easing: cubicOut }}>
              {#if activeTab === 'general'}
                <GeneralSettingsForm bind:data={$draft.general} errors={$fieldErrors} />
              {:else if activeTab === 'scheduling'}
                <SchedulingRulesForm bind:data={$draft.scheduling} errors={$fieldErrors} />
              {:else if activeTab === 'content'}
                <ChatbotFaqManager
                  {faqs}
                  loading={faqsLoading}
                  onCreate={handleCreateFaq}
                  onUpdate={handleUpdateFaq}
                  onDelete={requestDeleteFaq}
                  onReorder={handleReorderFaqs}
                />
              {/if}
            </div>
          {/key}
        {/if}

        {#if isCurrentDirty}
          <div
            class="sticky bottom-4 mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#bbf7d0] bg-white/95 px-4 py-3 shadow-lg backdrop-blur-xs"
            in:fly={{ y: 12, duration: 180, easing: cubicOut }}
            out:fade={{ duration: 120 }}
          >
            <div class="flex items-center gap-2.5">
              <span class="h-2 w-2 rounded-full bg-[#166534]"></span>
              <span class="text-sm font-medium text-[#14532d]">You have unsaved changes in this section.</span>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                disabled={$saving}
                on:click={() => store.discardSection(activeTab)}
                class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 active:scale-[0.98] disabled:opacity-50"
              >
                Discard
              </button>
              <button
                type="button"
                disabled={$saving}
                on:click={saveCurrentSection}
                class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#14532d] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
              >
                {#if $saving}
                  <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                  </svg>
                  <span>Saving...</span>
                {:else}
                  <span>Save Changes</span>
                {/if}
              </button>
            </div>
          </div>
        {/if}
        </div>
      </div>
    </div>
  </div>
</div>

<SettingsModal
  open={Boolean(pendingTab)}
  size="sm"
  tone="warning"
  title="Discard unsaved changes?"
  description="Switching sections now will revert your unsubmitted edits."
  on:close={() => (pendingTab = null)}
>
  <p class="text-sm leading-relaxed text-gray-600">
    Save your updates first if you want to keep changes made in this tab, or discard them to continue.
  </p>
  <svelte:fragment slot="footer">
    <button
      type="button"
      on:click={() => (pendingTab = null)}
      class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
    >
      Stay Here
    </button>
    <button
      type="button"
      on:click={confirmDiscardAndSwitch}
      class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#14532d]"
    >
      Discard &amp; Switch
    </button>
  </svelte:fragment>
</SettingsModal>

<SettingsModal
  open={Boolean(faqToDelete)}
  size="sm"
  tone="danger"
  busy={deletingFaq}
  title="Delete FAQ Entry?"
  description="This removes the response from Dr. Leo and Nurse Mika immediately."
  on:close={() => (faqToDelete = null)}
>
  {#if faqToDelete}
    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium text-gray-800">
      {faqToDelete.question}
    </div>
  {/if}
  <svelte:fragment slot="footer">
    <button
      type="button"
      disabled={deletingFaq}
      on:click={() => (faqToDelete = null)}
      class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
    >
      Cancel
    </button>
    <button
      type="button"
      disabled={deletingFaq}
      on:click={confirmDeleteFaq}
      class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
    >
      {deletingFaq ? 'Deleting...' : 'Delete FAQ'}
    </button>
  </svelte:fragment>
</SettingsModal>

<style>
  .settings-root {
    display: flex;
    flex-direction: column;
    height: calc(100dvh - 132px);
    min-height: 440px;
    overflow: hidden;
  }

  .settings-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    padding: 1.5rem 1.5rem 0;
  }

  .settings-body-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
  }

  .settings-shell {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    gap: 1rem;
  }

  .settings-rail {
    flex-shrink: 0;
    min-width: 0;
  }

  .settings-tabs {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }

  .settings-content-pane {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
    padding-right: 0.25rem;
    padding-bottom: 1.5rem;
  }

  .skeleton-bar {
    background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
    background-size: 200% 100%;
    animation: settingsShimmer 1.4s ease-in-out infinite;
  }

  @keyframes settingsShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }

  @media (prefers-reduced-motion: reduce) {
    .skeleton-bar {
      animation: none;
    }
  }

  @media (max-width: 960px) {
    .settings-root {
      height: calc(100dvh - 96px);
      min-height: 380px;
    }

    .settings-body {
      padding: 1rem 1rem 0;
    }
  }

  @media (min-width: 1024px) {
    .settings-shell {
      display: grid;
      grid-template-columns: 240px minmax(0, 1fr);
      gap: 1.5rem;
      height: 100%;
      align-items: stretch;
    }

    .settings-rail {
      height: 100%;
      overflow-y: auto;
      padding-bottom: 1.5rem;
    }

    .settings-tabs {
      flex-direction: column;
      overflow: visible;
      padding-bottom: 0;
    }

    .settings-content-pane {
      height: 100%;
    }
  }
</style>
