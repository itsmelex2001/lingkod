<script>
  import SettingsModal from './SettingsModal.svelte';

  export let faqs = [];
  export let loading = false;
  export let onCreate = async () => {};
  export let onUpdate = async () => {};
  export let onDelete = async () => {};
  export let onReorder = async () => {};

  let activeLocale = 'en';
  let activePersona = 'all';
  let search = '';
  let editing = null;
  let submitting = false;
  let formError = '';

  $: filteredFaqs = faqs
    .filter((f) => f.locale === activeLocale)
    .filter((f) => activePersona === 'all' || f.persona === activePersona)
    .filter((f) => {
      const q = search.trim().toLowerCase();
      if (!q) return true;
      return (
        f.question.toLowerCase().includes(q) ||
        f.answer_html.toLowerCase().includes(q) ||
        f.slug.toLowerCase().includes(q)
      );
    })
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));

  function openCreate() {
    formError = '';
    editing = {
      id: null,
      slug: '',
      locale: activeLocale,
      persona: 'both',
      question: '',
      answer_html: '',
      keywordsText: '',
      followUpsText: '',
      is_active: true,
    };
  }

  function openEdit(faq) {
    formError = '';
    editing = {
      id: faq.id,
      slug: faq.slug,
      locale: faq.locale,
      persona: faq.persona || 'both',
      question: faq.question,
      answer_html: faq.answer_html,
      keywordsText: (faq.keywords_json || []).join(', '),
      followUpsText: (faq.follow_ups_json || []).join('\n'),
      is_active: Boolean(faq.is_active),
    };
  }

  async function saveForm() {
    if (!editing) return;
    formError = '';
    submitting = true;
    const autoSlug = (editing.slug.trim() || editing.question.trim())
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 64);

    const payload = {
      slug: autoSlug,
      locale: editing.locale,
      persona: editing.persona,
      question: editing.question.trim(),
      answer_html: editing.answer_html.trim(),
      keywords: editing.keywordsText
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean),
      follow_ups: editing.followUpsText
        .split('\n')
        .map((s) => s.trim())
        .filter(Boolean)
        .slice(0, 3),
      is_active: editing.is_active,
    };

    try {
      if (editing.id) {
        await onUpdate(editing.id, payload);
      } else {
        await onCreate(payload);
      }
      editing = null;
    } catch (e) {
      formError = e?.message || 'Unable to save FAQ.';
    } finally {
      submitting = false;
    }
  }

  async function move(index, delta) {
    const target = index + delta;
    if (target < 0 || target >= filteredFaqs.length) return;
    const copy = [...filteredFaqs];
    const [moved] = copy.splice(index, 1);
    copy.splice(target, 0, moved);
    const items = copy.map((f, idx) => ({ id: f.id, sort_order: idx + 1 }));
    await onReorder(items);
  }

  function personaLabel(p) {
    if (p === 'dr_leo') return 'Dr. Leo';
    if (p === 'nurse_mika') return 'Nurse Mika';
    return 'Both Assistants';
  }
</script>

<div class="space-y-6">
  <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
    <div class="mb-5 flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-base font-bold text-gray-900">FAQ Management (Dr. Leo &amp; Nurse Mika)</h2>
        <p class="mt-0.5 text-sm text-gray-500">
          Manage bilingual patient portal FAQ responses in English and Tagalog.
        </p>
      </div>
      <button
        type="button"
        on:click={openCreate}
        class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-[#166534] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#14532d] active:scale-[0.98]"
      >
        + Add FAQ
      </button>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1" role="tablist">
          <button
            type="button"
            role="tab"
            aria-selected={activeLocale === 'en'}
            on:click={() => (activeLocale = 'en')}
            class="rounded-md px-3 py-1.5 text-xs font-semibold transition {activeLocale === 'en'
              ? 'bg-white text-[#166534] shadow-xs'
              : 'text-gray-600 hover:text-gray-900'}"
          >
            English (EN)
          </button>
          <button
            type="button"
            role="tab"
            aria-selected={activeLocale === 'tl'}
            on:click={() => (activeLocale = 'tl')}
            class="rounded-md px-3 py-1.5 text-xs font-semibold transition {activeLocale === 'tl'
              ? 'bg-white text-[#166534] shadow-xs'
              : 'text-gray-600 hover:text-gray-900'}"
          >
            Tagalog (TL)
          </button>
        </div>

        <select
          aria-label="Filter by assistant persona"
          bind:value={activePersona}
          class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 focus:border-[#166534] focus:outline-none"
        >
          <option value="all">All Assistants</option>
          <option value="both">Shared (Both)</option>
          <option value="dr_leo">Dr. Leo</option>
          <option value="nurse_mika">Nurse Mika</option>
        </select>
      </div>

      <input
        type="search"
        placeholder="Search FAQs..."
        aria-label="Search FAQs"
        bind:value={search}
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 sm:w-64 focus:border-[#166534] focus:outline-none"
      />
    </div>

    {#if loading}
      <p class="py-8 text-center text-sm text-gray-500">Loading chatbot FAQs...</p>
    {:else if filteredFaqs.length === 0}
      <p class="rounded-lg border border-dashed border-gray-200 py-8 text-center text-sm text-gray-500">
        No FAQs found for this filter.
      </p>
    {:else}
      <ul class="divide-y divide-gray-100">
        {#each filteredFaqs as faq, idx (faq.id)}
          <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-[#f0fdf4] px-2.5 py-0.5 text-[11px] font-semibold text-[#166534] border border-[#bbf7d0]">
                  {personaLabel(faq.persona)}
                </span>
                <span class="font-mono text-[11px] text-gray-400">{faq.slug}</span>
                {#if !faq.is_active}
                  <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">
                    Hidden
                  </span>
                {/if}
              </div>
              <p class="mt-1.5 text-sm font-semibold text-gray-900">{faq.question}</p>
              <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-gray-600">
                {faq.answer_html.replace(/<[^>]+>/g, '')}
              </p>
            </div>

            <div class="flex shrink-0 items-center gap-1.5">
              <button
                type="button"
                aria-label="Move up"
                disabled={idx === 0}
                on:click={() => move(idx, -1)}
                class="rounded-md border border-gray-200 px-2 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-40"
              >
                &uarr;
              </button>
              <button
                type="button"
                aria-label="Move down"
                disabled={idx === filteredFaqs.length - 1}
                on:click={() => move(idx, 1)}
                class="rounded-md border border-gray-200 px-2 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-40"
              >
                &darr;
              </button>
              <button
                type="button"
                on:click={() => openEdit(faq)}
                class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-[#166534] hover:text-[#166534]"
              >
                Edit
              </button>
              <button
                type="button"
                on:click={() => onDelete(faq)}
                class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
              >
                Delete
              </button>
            </div>
          </li>
        {/each}
      </ul>
    {/if}
  </section>

  <SettingsModal
    open={Boolean(editing)}
    size="lg"
    tone="primary"
    busy={submitting}
    title={editing?.id ? 'Edit FAQ Entry' : 'New FAQ Entry'}
    description="Updates appear immediately in Dr. Leo and Nurse Mika on the public patient portal."
    on:close={() => (editing = null)}
  >
    {#if editing}
      {#if formError}
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" role="alert">
          {formError}
        </p>
      {/if}

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label for="faq-slug" class="block text-xs font-semibold text-gray-700">Slug (Optional)</label>
          <input
            id="faq-slug"
            type="text"
            placeholder="clinic-hours"
            bind:value={editing.slug}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          />
        </div>
        <div>
          <label for="faq-locale" class="block text-xs font-semibold text-gray-700">Language</label>
          <select
            id="faq-locale"
            bind:value={editing.locale}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          >
            <option value="en">English (EN)</option>
            <option value="tl">Tagalog (TL)</option>
          </select>
        </div>
        <div>
          <label for="faq-persona" class="block text-xs font-semibold text-gray-700">Assistant</label>
          <select
            id="faq-persona"
            bind:value={editing.persona}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          >
            <option value="both">Both Assistants</option>
            <option value="dr_leo">Dr. Leo</option>
            <option value="nurse_mika">Nurse Mika</option>
          </select>
        </div>

        <div class="sm:col-span-3">
          <label for="faq-question" class="block text-xs font-semibold text-gray-700">Question</label>
          <input
            id="faq-question"
            type="text"
            placeholder="e.g. What are the hospital clinic hours?"
            bind:value={editing.question}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          />
        </div>

        <div class="sm:col-span-3">
          <label for="faq-answer" class="block text-xs font-semibold text-gray-700">
            Answer (supports &lt;strong&gt; and &lt;br&gt;)
          </label>
          <textarea
            id="faq-answer"
            rows="4"
            bind:value={editing.answer_html}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          ></textarea>
        </div>

        <div class="sm:col-span-2">
          <label for="faq-keywords" class="block text-xs font-semibold text-gray-700">
            Keywords (comma-separated)
          </label>
          <input
            id="faq-keywords"
            type="text"
            placeholder="hours, schedule, opd"
            bind:value={editing.keywordsText}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          />
        </div>

        <div class="flex items-end pb-2">
          <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" bind:checked={editing.is_active} class="h-4 w-4 rounded border-gray-300 text-[#166534]" />
            Visible in chatbot
          </label>
        </div>

        <div class="sm:col-span-3">
          <label for="faq-followups" class="block text-xs font-semibold text-gray-700">
            Follow-up Chips (up to 3, one per line)
          </label>
          <textarea
            id="faq-followups"
            rows="2"
            bind:value={editing.followUpsText}
            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
          ></textarea>
        </div>
      </div>
    {/if}

    <svelte:fragment slot="footer">
      <button
        type="button"
        disabled={submitting}
        on:click={() => (editing = null)}
        class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
      >
        Cancel
      </button>
      <button
        type="button"
        disabled={submitting}
        on:click={saveForm}
        class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#14532d] active:scale-[0.98] disabled:opacity-50"
      >
        {submitting ? 'Saving...' : 'Save FAQ'}
      </button>
    </svelte:fragment>
  </SettingsModal>
</div>
