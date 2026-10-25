<script>
  import { formatTime12h, getCapacityPercent } from '../../utils/formatters.js';
  import { getDoctorDisplayStatus } from '../../types/status.js';

  export let doc;

  $: statusInfo = getDoctorDisplayStatus(doc);
  $: isAvailable = statusInfo.isBookable;
  $: percentage = getCapacityPercent(doc.booked_slots, doc.total_slots);
  $: link = doc.department.toLowerCase().includes('psychiatry')
    ? 'regis-psychiatry.php'
    : doc.department.toLowerCase().includes('neurology')
    ? 'regis-neurology.php'
    : 'regis-general.php';
</script>

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
      <span class="val">{doc.schedule_start ? `${formatTime12h(doc.schedule_start)} - ${formatTime12h(doc.schedule_end)}` : 'N/A'}</span>
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

<style>
  .minimal-card {
    background: #fff;
    border: 1px solid #eaeaea;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s ease;
  }
  .minimal-card:hover {
    border-color: #111;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  }
  .minimal-card.unavailable { opacity: 0.6; }
  .minimal-card.unavailable:hover { border-color: #eaeaea; transform: none; box-shadow: none; }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
  }
  .doc-meta h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 0.25rem 0; letter-spacing: -0.01em; }
  .doc-meta p { font-size: 0.85rem; color: #666; margin: 0; }
  
  .status-pill {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    letter-spacing: 0.02em;
  }
  .bg-avail { background: #ecfdf5; color: #059669; }
  .bg-inclinic { background: #eff6ff; color: #2563eb; }
  .bg-break { background: #fef3c7; color: #d97706; }
  .bg-full { background: #fffbeb; color: #d97706; }
  .bg-leave { background: #fef2f2; color: #dc2626; }
  .bg-emergency { background: #fee2e2; color: #dc2626; }
  
  .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 2rem;
  }
  .info-item { display: flex; justify-content: space-between; font-size: 0.85rem; }
  .info-item .lbl { color: #888; }
  .info-item .val { font-weight: 500; }
  
  .card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    padding-top: 1rem;
    border-top: 1px dashed #eaeaea;
  }
  
  .slots-minimal { flex: 1; }
  .slot-txt { display: block; font-size: 0.75rem; color: #666; margin-bottom: 0.35rem; }
  .slot-txt strong { color: #111; }
  .bar-bg { width: 100%; height: 4px; background: #eee; border-radius: 2px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 2px; transition: width 0.3s; }
  .fill-black { background: #111; }
  .fill-gray { background: #aaa; }
  
  .btn-action {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 500;
    background: #111; color: #fff; border-radius: 6px;
    text-decoration: none; border: none; cursor: pointer;
    transition: opacity 0.2s;
  }
  .btn-action:hover { opacity: 0.8; }
  .btn-action.disabled { background: #eaeaea; color: #888; cursor: not-allowed; }
</style>
