<script>
  import { buildQrMatrix, buildQrSvgPath } from '../../lib/qrMatrix.js';

  /** @type {'appointment' | 'followUp' | 'rescheduled'} */
  export let slipType = 'appointment';

  export let patientId = '';
  export let patientName = '';
  export let patientType = '';
  export let appointmentDate = '';
  export let appointmentTime = '';
  export let specialty = '';
  export let consultType = '';
  export let remarks = '';
  export let encodedBy = '';
  export let attendedDate = '';

  /** @type {Array<{ date?: string, time?: string, specialty?: string }>} */
  export let followUps = [];

  export let dohLogoUrl = '/sdn/public/assets/logos/doh%20logo.png';
  export let mmwghLogoUrl = '/sdn/public/assets/mmwgh_logo.png';
  export let qrCodeUrl = '';

  const SLIP_META = {
    appointment: { title: 'APPOINTMENT SLIP', docCode: 'MMH-OPM-04-02-00' },
    followUp: { title: 'FOLLOW-UP SLIP', docCode: 'MMH-OPM-04-03-00' },
    rescheduled: { title: 'RESCHEDULED (NEW) SLIP', docCode: 'MMH-NUR-04-204-01' }
  };

  $: meta = SLIP_META[slipType] ?? SLIP_META.appointment;
  $: appointmentDateTime = [appointmentDate, appointmentTime].filter(Boolean).join(' ');
  $: resolvedFollowUps = followUps.length
    ? followUps.map((f) => ({
        date: [f.date, f.time].filter(Boolean).join(' '),
        specialty: f.specialty || ''
      }))
    : [{ date: appointmentDateTime, specialty }];
  $: qrData = buildQrSvgPath(buildQrMatrix(patientId));
</script>

<article
  class="patient-slip w-full max-w-[440px] mx-auto bg-white text-black border border-neutral-400 shadow-sm print:shadow-none print:border-black print:max-w-full break-inside-avoid font-sans leading-snug"
  aria-label={meta.title}
>
  <!-- Hospital Header -->
  <header class="grid grid-cols-[52px_1fr_52px] items-center gap-2 px-4 py-3 border-b border-neutral-300 print:border-black">
    <div class="w-12 h-12 rounded-full border border-neutral-400 print:border-black flex items-center justify-center overflow-hidden shrink-0">
      {#if dohLogoUrl}
        <img src={dohLogoUrl} alt="Department of Health Logo" class="w-full h-full object-contain" />
      {:else}
        <span class="text-[9px] font-bold tracking-tight text-neutral-700 text-center leading-tight">DOH<br />LOGO</span>
      {/if}
    </div>

    <div class="text-center text-black space-y-0.5">
      <p class="text-[11px] font-medium leading-tight">Department of Health</p>
      <p class="text-[11px] font-bold leading-tight">Central Luzon Center for Health Development</p>
      <p class="text-[11px] font-bold uppercase tracking-tight leading-tight">
        MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL
      </p>
      <p class="text-[10px] leading-tight">
        P. Monroe Street, Poblacion, Mariveles, Bataan, Philippines, 2105
      </p>
    </div>

    <div class="w-12 h-12 rounded-full border border-neutral-400 print:border-black flex items-center justify-center overflow-hidden shrink-0">
      {#if mmwghLogoUrl}
        <img src={mmwghLogoUrl} alt="MMWGH Official Logo" class="w-full h-full object-contain" />
      {:else}
        <span class="text-[9px] font-bold tracking-tight text-neutral-700 text-center leading-tight">MMWGH<br />LOGO</span>
      {/if}
    </div>
  </header>

  <!-- Slip Body -->
  <div class="px-5 pt-4 pb-3">
    <h1 class="text-center text-sm font-bold uppercase tracking-wide mb-4">
      {meta.title}
    </h1>

    <div class="flex items-start justify-between gap-4">
      <div class="text-[12.5px] space-y-1.5 text-left min-w-0 flex-1">
        <p><span class="font-semibold">Name:</span> {patientName}</p>
        <p><span class="font-semibold">Type of Patient:</span> {patientType}</p>

        {#if slipType === 'followUp'}
          <div class="pt-0.5">
            <p class="font-semibold">Follow-Up/s:</p>
            <p class="text-[11px] leading-tight mt-0.5">
              <span class="font-semibold">Attended Date:</span> {attendedDate}
            </p>
          </div>

          <div class="space-y-3 pt-2">
            {#each resolvedFollowUps as item}
              <div class="space-y-1">
                <p><span class="font-semibold">Date:</span> {item.date}</p>
                <p><span class="font-semibold">Specialty:</span> {item.specialty}</p>
              </div>
            {/each}
          </div>

          <p class="pt-1"><span class="font-semibold">Encoded By:</span> {encodedBy}</p>
        {:else if slipType === 'rescheduled'}
          <p><span class="font-semibold">Appointment:</span> {appointmentDateTime}</p>
          <p><span class="font-semibold">Specialty:</span> {specialty}</p>
          <p><span class="font-semibold">Type of Consult:</span> {consultType}</p>
          <p><span class="font-semibold">Encoded by:</span> {encodedBy}</p>
        {:else}
          <p><span class="font-semibold">Appointment:</span> {appointmentDateTime}</p>
          <p><span class="font-semibold">Specialty:</span> {specialty}</p>
          <p><span class="font-semibold">Type of Consult:</span> {consultType}</p>
          <p><span class="font-semibold">Remarks:</span> {remarks}</p>
          <p><span class="font-semibold">Encoded By:</span> {encodedBy}</p>
        {/if}
      </div>

      {#if qrCodeUrl || qrData.path}
        <div class="shrink-0 flex flex-col items-center gap-1">
          <div class="w-[76px] h-[76px] border border-neutral-300 print:border-black bg-white p-0.5 flex items-center justify-center">
            {#if qrCodeUrl}
              <img src={qrCodeUrl} alt="Patient QR Code" class="w-full h-full object-contain" />
            {:else}
              <svg
                viewBox="0 0 {qrData.viewBox} {qrData.viewBox}"
                class="w-full h-full block"
                shape-rendering="crispEdges"
                role="img"
                aria-label="Patient ID QR Code"
              >
                <rect width={qrData.viewBox} height={qrData.viewBox} fill="#ffffff" />
                <path d={qrData.path} fill="#000000" />
              </svg>
            {/if}
          </div>
          {#if patientId}
            <span class="text-[8.5px] font-mono font-semibold tracking-tight text-black leading-none">{patientId}</span>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Static Footer Reminders -->
    <footer class="mt-6 text-center space-y-2.5">
      {#if slipType === 'appointment'}
        <p class="text-[10.5px] font-bold">
          To HIMU for creation of chart. Please bring valid ID / Birth Certificate.
        </p>
      {/if}

      <div class="text-[11px] leading-relaxed">
        <p class="uppercase">DALHIN SA ARAW NG KONSULTA</p>
        <p class="uppercase">OPU-MEDICAL CONTACT NUMBER:</p>
        <p class="font-bold tracking-wide">0917-125-8905</p>
      </div>

      <div class="pt-3 border-t border-neutral-200 print:border-black/40 text-right">
        <span class="text-[10.5px] font-bold tracking-tight">{meta.docCode}</span>
      </div>
    </footer>
  </div>
</article>

<style>
  @media print {
    :global(body) {
      background: #ffffff !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .patient-slip {
      page-break-inside: avoid;
      break-inside: avoid;
    }
  }
</style>
