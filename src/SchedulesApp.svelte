<script>
  import { onMount } from 'svelte';
  
  let rawDoctors = [];
  let loading = true;
  let clockTime = '';
  
  let searchQuery = '';
  let selectedDept = 'All';
  let showOnlyAvailable = false;
  
  const depts = ['All', 'Psychiatry', 'Neurology', 'General Management'];
  
  const loadSchedule = async () => {
    try {
      const res = await fetch('/sdn/api/schedule.php').then(r => r.json());
      if (res.success && res.data) rawDoctors = res.data;
    } catch(e) { console.error(e); } finally { loading = false; }
  };
  
  onMount(() => {
    const updateTime = () => {
      clockTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);
    loadSchedule();
    return () => clearInterval(interval);
  });
  
  const formatTime = (t) => {
    if(!t) return 'N/A';
    const [h,m] = t.split(':');
    let hr = parseInt(h);
    const ampm = hr >= 12 ? 'PM' : 'AM';
    hr = hr % 12 || 12;
    return `${hr}:${m} ${ampm}`;
  };

  const getDoctorStatus = (doc) => {
    if (!doc) return { label: 'Available', pillClass: 'bg-avail', isBookable: true, buttonText: 'Book' };
    const booked = parseInt(doc.booked_slots || 0, 10);
    const total = parseInt(doc.total_slots || 20, 10);
    const isFull = total > 0 && booked >= total;
    const raw = (doc.status || 'available').toLowerCase().trim();

    if (isFull || raw === 'booked' || raw === 'fully booked' || raw === 'full') {
      return { label: 'Full', pillClass: 'bg-full', isBookable: false, buttonText: 'Waitlist' };
    }
    if (raw === 'leave' || raw === 'on_leave' || raw === 'on leave') {
      return { label: 'On Leave', pillClass: 'bg-leave', isBookable: false, buttonText: 'On Leave' };
    }
    if (raw === 'emergency') {
      return { label: 'Emergency', pillClass: 'bg-emergency', isBookable: false, buttonText: 'Emergency' };
    }
    if (raw === 'on_break' || raw === 'break') {
      return { label: 'On Break', pillClass: 'bg-break', isBookable: true, buttonText: 'Book' };
    }
    if (raw === 'in_clinic' || raw === 'in-clinic' || raw === 'in clinic') {
      return { label: 'In Clinic', pillClass: 'bg-inclinic', isBookable: true, buttonText: 'Book' };
    }
    return { label: 'Available', pillClass: 'bg-avail', isBookable: true, buttonText: 'Book' };
  };

  $: filteredDoctors = rawDoctors.filter(d => {
    const matchSearch = d.name.toLowerCase().includes(searchQuery.toLowerCase()) || d.specialization.toLowerCase().includes(searchQuery.toLowerCase());
    const matchDept = selectedDept === 'All' || d.department.toLowerCase().includes(selectedDept.toLowerCase().split(' ')[0]);
    const statusInfo = getDoctorStatus(d);
    const matchAvail = showOnlyAvailable ? statusInfo.isBookable : true;
    return matchSearch && matchDept && matchAvail;
  });
</script>

<main class="minimal-dashboard">
  <!-- Minimal Header -->
  <header class="top-nav">
    <div class="nav-brand">
      <a href="/sdn/index.php" class="btn-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      </a>
      <div class="brand-text">
        <h1>Directory</h1>
        <span class="live-clock"><span class="dot"></span> {clockTime}</span>
      </div>
    </div>
    
    <div class="nav-filters">
      <div class="search-wrapper">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" bind:value={searchQuery} placeholder="Search doctors..." class="input-search" />
      </div>
      <label class="toggle-minimal">
        <input type="checkbox" bind:checked={showOnlyAvailable}>
        <span class="toggle-box"></span>
        <span class="toggle-txt">Available Only</span>
      </label>
    </div>
  </header>

  <!-- Horizontal Department Tabs -->
  <nav class="dept-tabs">
    {#each depts as d}
      <button class="tab {selectedDept === d ? 'active' : ''}" on:click={() => selectedDept = d}>{d}</button>
    {/each}
  </nav>

  <section class="content-area">
    {#if loading}
       <div class="status-msg">Loading schedules...</div>
    {:else if filteredDoctors.length === 0}
       <div class="status-msg empty">
         No specialists found.
         <button class="btn-text" on:click={() => { searchQuery = ''; selectedDept = 'All'; showOnlyAvailable = false; }}>Clear filters</button>
       </div>
    {:else}
      <div class="doc-grid">
        {#each filteredDoctors as doc}
          {@const statusInfo = getDoctorStatus(doc)}
          {@const isAvailable = statusInfo.isBookable}
          {@const percentage = doc.total_slots > 0 ? (parseInt(doc.booked_slots) / parseInt(doc.total_slots)) * 100 : 0}
          {@const link = doc.department.toLowerCase().includes('psychiatry') ? 'regis-psychiatry.php' : doc.department.toLowerCase().includes('neurology') ? 'regis-neurology.php' : 'regis-general.php'}
          
          <div class="minimal-card {isAvailable ? '' : 'unavailable'}">
            <div class="card-header">
              <div class="doc-meta">
                <h2>{doc.name}</h2>
                <p>{doc.specialization}</p>
              </div>
              <div class="status-pill {statusInfo.pillClass}">
                {statusInfo.label}
              </div>
            </div>
            
            <div class="card-body">
              <div class="info-item">
                <span class="lbl">Dept</span>
                <span class="val">{doc.department}</span>
              </div>
              <div class="info-item">
                <span class="lbl">Time</span>
                <span class="val">{doc.schedule_start ? `${formatTime(doc.schedule_start)} - ${formatTime(doc.schedule_end)}` : 'N/A'}</span>
              </div>
            </div>
            
            <div class="card-footer">
              <div class="slots-minimal">
                <span class="slot-txt">Slots: <strong>{doc.booked_slots || 0}</strong> / {doc.total_slots || 20}</span>
                <div class="bar-bg"><div class="bar-fill {isAvailable ? 'fill-black' : 'fill-gray'}" style="width: {percentage}%"></div></div>
              </div>
              
              {#if isAvailable}
                <a href="/sdn/pages/{link}?source=schedules&doctor={encodeURIComponent(doc.name)}" class="btn-action">Book</a>
              {:else}
                <button class="btn-action disabled" disabled>{statusInfo.buttonText}</button>
              {/if}
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </section>
</main>

<style>
  :global(body) {
    background-color: #f9f9f9;
    margin: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #111;
  }
  
  .minimal-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    box-sizing: border-box;
  }

  /* Header */
  .top-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eaeaea;
    margin-bottom: 2rem;
  }
  
  .nav-brand {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }
  
  .btn-back {
    display: flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border: 1px solid #eaeaea; border-radius: 50%;
    color: #111; text-decoration: none; transition: all 0.2s ease;
  }
  .btn-back:hover { background: #111; color: #fff; border-color: #111; }
  
  .brand-text h1 { margin: 0; font-size: 1.8rem; font-weight: 700; letter-spacing: -0.03em; }
  .live-clock { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #666; font-weight: 500; margin-top: 0.25rem; }
  .dot { width: 6px; height: 6px; background: #10b981; border-radius: 50%; animation: blink 2s infinite; }
  @keyframes blink { 50% { opacity: 0; } }

  .nav-filters {
    display: flex;
    align-items: center;
    gap: 2rem;
  }
  
  .search-wrapper {
    display: flex; align-items: center; gap: 0.75rem;
    border-bottom: 1px solid #ccc; padding: 0.5rem 0; width: 250px;
    transition: border-color 0.2s;
  }
  .search-wrapper:focus-within { border-color: #111; }
  .input-search { border: none; outline: none; background: transparent; font-size: 0.95rem; width: 100%; color: #111;}
  
  .toggle-minimal { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-size: 0.9rem; font-weight: 500; }
  .toggle-minimal input { display: none; }
  .toggle-box { width: 16px; height: 16px; border: 1px solid #ccc; border-radius: 4px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
  .toggle-minimal input:checked + .toggle-box { background: #111; border-color: #111; }
  .toggle-minimal input:checked + .toggle-box::after { content: ''; width: 6px; height: 6px; background: #fff; border-radius: 1px; }

  /* Tabs */
  .dept-tabs {
    display: flex;
    gap: 2rem;
    margin-bottom: 2.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }
  .tab {
    background: transparent; border: none; font-size: 1.1rem; color: #888; font-weight: 500;
    cursor: pointer; padding: 0; transition: color 0.2s; position: relative;
  }
  .tab:hover { color: #111; }
  .tab.active { color: #111; font-weight: 600; }
  .tab.active::after {
    content: ''; position: absolute; bottom: -8px; left: 0; width: 100%; height: 2px; background: #111;
  }

  /* Grid */
  .doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
  }
  
  .minimal-card {
    background: #fff;
    border: 1px solid #eaeaea;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .minimal-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.04); border-color: #ddd; }
  .minimal-card.unavailable { opacity: 0.7; }
  
  .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
  .doc-meta h2 { margin: 0 0 0.25rem 0; font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; }
  .doc-meta p { margin: 0; color: #666; font-size: 0.9rem; }
  
  .status-pill { padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
  .bg-avail { background: #ecfdf5; color: #059669; }
  .bg-inclinic { background: #eff6ff; color: #2563eb; }
  .bg-break { background: #fef3c7; color: #d97706; }
  .bg-full { background: #fffbeb; color: #d97706; }
  .bg-leave { background: #fef2f2; color: #dc2626; }
  .bg-emergency { background: #fee2e2; color: #dc2626; }
  
  .card-body { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem; flex-grow: 1; border-top: 1px dashed #eaeaea; border-bottom: 1px dashed #eaeaea; padding: 1rem 0; }
  .info-item { display: flex; justify-content: space-between; font-size: 0.9rem; }
  .info-item .lbl { color: #888; }
  .info-item .val { font-weight: 500; text-align: right; }
  
  .card-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
  .slots-minimal { flex-grow: 1; }
  .slot-txt { display: block; font-size: 0.8rem; color: #666; margin-bottom: 0.4rem; }
  .slot-txt strong { color: #111; font-weight: 700; }
  .bar-bg { width: 100%; height: 4px; background: #f0f0f0; border-radius: 2px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }
  .fill-black { background: #111; } .fill-gray { background: #aaa; }
  
  .btn-action {
    background: #111; color: #fff; text-decoration: none; font-size: 0.9rem; font-weight: 600;
    padding: 0.6rem 1.2rem; border-radius: 6px; transition: background 0.2s; border: none; cursor: pointer;
  }
  .btn-action:hover { background: #333; }
  .btn-action.disabled { background: #f0f0f0; color: #999; cursor: not-allowed; }
  
  .status-msg { font-size: 1.1rem; color: #666; padding: 4rem 0; text-align: center; }
  .empty { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
  .btn-text { background: none; border: none; color: #111; font-weight: 600; text-decoration: underline; cursor: pointer; }

  @media (max-width: 768px) {
    .top-nav { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
    .nav-filters { width: 100%; justify-content: space-between; }
    .search-wrapper { width: 60%; }
  }
</style>
